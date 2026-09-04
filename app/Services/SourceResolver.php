<?php

namespace App\Services;

use App\Support\UserPreferences;
use Illuminate\Support\Facades\Cache;

class SourceResolver
{
    public function __construct(
        private ProviderScorer $scorer,
        private ProviderAnalyticsTracker $analytics,
        private EmbedUrlBuilder $embedUrlBuilder,
        private CineSrcEmbed $cineSrcEmbed,
        private CineSrcStreamResolver $cineSrcStreamResolver,
        private Tmdb $tmdb,
    ) {}

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool, postmessage?: array<string, mixed>}>
     */
    public function resolve(int $tmdbId, string $mediaType = 'movie', ?int $season = null, ?int $episode = null): array
    {
        $cacheKey = "sources.{$mediaType}.{$tmdbId}.{$season}.{$episode}";

        /** @var array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool}> */
        $sources = Cache::remember($cacheKey, now()->addMinutes(config('sources.cache_ttl')), function () use ($tmdbId, $mediaType, $season, $episode): array {
            /** @var array<int, array{driver: string, name?: string, movie_url?: string, tv_url?: string, supports_postmessage?: bool}> $providers */
            $providers = config('sources.providers', []);
            $sources = [];

            foreach ($providers as $provider) {
                if (($provider['driver'] ?? '') === 'cinesrc') {
                    continue;
                }

                $resolved = match ($provider['driver']) {
                    'embed' => $this->resolveEmbed($tmdbId, $mediaType, $provider, $season, $episode),
                    'trailer' => $this->resolveTrailer($tmdbId, $mediaType),
                    default => [],
                };

                $sources = [...$sources, ...$resolved];
            }

            return $sources;
        });

        return $this->applyUserSourceFilters(
            $this->applyEmbedPersonalization(
                [...$sources, ...$this->resolveCineSrc($tmdbId, $mediaType, $season, $episode)],
                $tmdbId,
                $mediaType,
            ),
        );
    }

    /**
     * Select the best server index based on user history, provider reliability, analytics, and health.
     */
    public function recommendServer(int $tmdbId, string $mediaType, ?int $season, ?int $episode, ?string $excludeProvider = null): int
    {
        return $this->scorer->recommend(
            $this->resolve($tmdbId, $mediaType, $season, $episode),
            $tmdbId,
            $mediaType,
            $season,
            $episode,
            $excludeProvider,
        );
    }

    public function reportFailure(string $provider): void
    {
        $this->analytics->reportFailure($provider);
    }

    public function reportSuccess(string $provider): void
    {
        $this->analytics->reportSuccess($provider);
    }

    public function reportBuffering(string $provider, int $loadTimeMs = 0): void
    {
        $this->analytics->reportBuffering($provider, $loadTimeMs);
    }

    /**
     * @return array{embed: array<int, array{type: string, url: string, quality: string, provider: string}>, external: array<int, array{name: string, url: string}>}
     */
    public function resolveAdult(int $tmdbId): array
    {
        $cacheKey = "sources.adult.{$tmdbId}";

        /** @var array{embed: array<int, array{type: string, url: string, quality: string, provider: string}>, external: array<int, array{name: string, url: string}>} */
        return Cache::remember($cacheKey, now()->addMinutes(config('sources.cache_ttl')), function () use ($tmdbId): array {
            /** @var array<int, array{driver: string, name?: string, movie_url?: string, url?: string}> $providers */
            $providers = config('sources.adult_providers', []);
            $embed = [];
            $external = [];

            foreach ($providers as $provider) {
                if (($provider['driver'] ?? '') === 'embed') {
                    $template = $provider['movie_url'] ?? '';
                    if ($template !== '') {
                        $url = str_replace('{id}', (string) $tmdbId, $template);
                        $embed[] = [
                            'type' => 'embed',
                            'url' => $url,
                            'quality' => 'auto',
                            'provider' => $provider['name'] ?? 'Adult Embed',
                        ];
                    }
                } elseif (($provider['driver'] ?? '') === 'external') {
                    $external[] = [
                        'name' => $provider['name'] ?? 'External',
                        'url' => $provider['url'] ?? '',
                    ];
                }
            }

            return ['embed' => $embed, 'external' => $external];
        });
    }

    /**
     * Pre-warm source cache for a list of TMDB IDs.
     *
     * @param  array<int, array{id: int, type: string}>  $items
     */
    public function preWarm(array $items): int
    {
        $warmed = 0;

        foreach ($items as $item) {
            $cacheKey = "sources.{$item['type']}.{$item['id']}..";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $this->resolve($item['id'], $item['type']);
            $warmed++;
        }

        return $warmed;
    }

    /**
     * @return array<string, int>
     */
    public function getProviderHealth(): array
    {
        return $this->analytics->healthMap(array_keys(ProviderScorer::PROVIDER_SCORES));
    }

    /**
     * @param  array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool}>  $sources
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool}>
     */
    private function applyUserSourceFilters(array $sources): array
    {
        if (! auth()->check()) {
            return $sources;
        }

        $prefs = auth()->user()->preferences ?? [];
        $excluded = UserPreferences::get($prefs, 'excluded_providers', []);
        $excluded = is_array($excluded) ? $excluded : [];

        if ($excluded !== []) {
            $sources = array_values(array_filter(
                $sources,
                fn (array $source): bool => ! in_array($source['provider'] ?? '', $excluded, true),
            ));
        }

        if (! UserPreferences::bool($prefs, 'show_trailer_in_servers', true)) {
            $sources = array_values(array_filter(
                $sources,
                fn (array $source): bool => ($source['type'] ?? '') !== 'youtube',
            ));
        }

        return $sources;
    }

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool, postmessage?: array<string, mixed>}>
     */
    private function resolveCineSrc(int $tmdbId, string $mediaType, ?int $season, ?int $episode): array
    {
        $options = $this->cineSrcOptions($tmdbId, $mediaType);

        $embedUrl = $this->cineSrcEmbed->buildUrl($tmdbId, $mediaType, $season, $episode, $options);

        $sources = [[
            'type' => 'embed',
            'url' => $embedUrl,
            'quality' => is_string($options['quality'] ?? null) ? $options['quality'] : 'auto',
            'provider' => 'CineSrc',
            'supports_postmessage' => true,
            'postmessage' => $this->postMessageConfig('CineSrc'),
        ]];

        $direct = $this->cineSrcStreamResolver->resolve($tmdbId, $mediaType, $season, $episode);

        if ($direct !== null) {
            $sources[] = [
                'type' => 'hls',
                'url' => $direct['url'],
                'quality' => $direct['quality'],
                'provider' => $direct['provider'],
            ];
        }

        return $sources;
    }

    /**
     * @return array{
     *     progress_seconds?: int,
     *     cinesrc_server_id?: string,
     *     quality?: string,
     *     autoskip?: bool,
     *     autonext?: bool,
     *     autoplay?: bool,
     *     muted?: bool,
     *     continue_prompt?: bool,
     *     seek?: int,
     *     back?: string|null,
     * }
     */
    private function cineSrcOptions(int $tmdbId, string $mediaType): array
    {
        $options = [];

        if (! auth()->check()) {
            return $options;
        }

        $user = auth()->user();
        $prefs = $user->preferences ?? [];
        $rememberLastServer = UserPreferences::bool($prefs, 'remember_last_server', true);

        $history = $user->watchHistory()
            ->where('tmdb_id', $tmdbId)
            ->where('media_type', $mediaType)
            ->first();

        if ($history !== null) {
            if ($history->progress_seconds > 30) {
                $options['progress_seconds'] = $history->progress_seconds;
            }

            if ($rememberLastServer && is_string($history->cinesrc_server_id) && $history->cinesrc_server_id !== '') {
                $options['cinesrc_server_id'] = $history->cinesrc_server_id;
            }
        }

        $quality = $prefs['stream_quality'] ?? config('sources.cinesrc.default_quality');
        if (is_string($quality) && $quality !== '') {
            $options['quality'] = $quality;
        }

        if (array_key_exists('cinesrc_autoskip', $prefs)) {
            $options['autoskip'] = (bool) $prefs['cinesrc_autoskip'];
        }

        if (array_key_exists('cinesrc_autonext', $prefs)) {
            $options['autonext'] = (bool) $prefs['cinesrc_autonext'];
        }

        $options['autoplay'] = UserPreferences::bool($prefs, 'autoplay_on_watch', true);
        $options['muted'] = UserPreferences::bool($prefs, 'start_muted', false);
        $options['continue_prompt'] = UserPreferences::bool($prefs, 'resume_prompt', true);

        $seek = (int) UserPreferences::get($prefs, 'cinesrc_seek', 10);
        if (in_array($seek, [5, 10, 15, 30], true)) {
            $options['seek'] = $seek;
        }

        if (array_key_exists('cinesrc_back', $prefs)) {
            $back = $prefs['cinesrc_back'];
            $options['back'] = is_string($back) ? $back : 'close';
        }

        return $options;
    }

    /**
     * @param  array{driver: string, name?: string, movie_url?: string, tv_url?: string}  $provider
     * @return array<int, array{type: string, url: string, quality: string, provider: string}>
     */
    private function resolveEmbed(int $tmdbId, string $mediaType, array $provider, ?int $season, ?int $episode): array
    {
        $template = $mediaType === 'tv' && $season !== null && $episode !== null
            ? ($provider['tv_url'] ?? '')
            : ($provider['movie_url'] ?? '');

        if (empty($template)) {
            return [];
        }

        $url = str_replace(
            ['{id}', '{season}', '{episode}'],
            [(string) $tmdbId, (string) ($season ?? 1), (string) ($episode ?? 1)],
            $template,
        );

        return [
            [
                'type' => 'embed',
                'url' => $url,
                'quality' => 'auto',
                'provider' => $provider['name'] ?? 'Embed',
            ],
        ];
    }

    /**
     * @param  array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool, postmessage?: array<string, mixed>}>  $sources
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool, postmessage?: array<string, mixed>}>
     */
    private function applyEmbedPersonalization(array $sources, int $tmdbId, string $mediaType): array
    {
        $context = $this->playbackContext($tmdbId, $mediaType);

        return array_map(function (array $source) use ($context): array {
            if (($source['type'] ?? '') !== 'embed') {
                return $source;
            }

            $providerConfig = $this->findProviderConfig($source['provider'] ?? '');

            if ($providerConfig === null) {
                return $source;
            }

            if (isset($providerConfig['embed_options'])) {
                $source['url'] = $this->embedUrlBuilder->enrich($source['url'], $providerConfig, $context);
            }

            $postMessage = $this->postMessageConfig($source['provider'] ?? '');

            if ($postMessage !== null) {
                $source['supports_postmessage'] = true;
                $source['postmessage'] = $postMessage;
            }

            return $source;
        }, $sources);
    }

    /**
     * @return array{
     *     progress_seconds?: int,
     *     autoplay: bool,
     *     muted: bool,
     *     continue_prompt: bool,
     *     media_type: string,
     *     subtitle?: string,
     * }
     */
    private function playbackContext(int $tmdbId, string $mediaType): array
    {
        $context = [
            'autoplay' => filter_var(config('sources.cinesrc.autoplay', true), FILTER_VALIDATE_BOOL),
            'muted' => false,
            'continue_prompt' => true,
            'media_type' => $mediaType,
        ];

        if (! auth()->check()) {
            return $context;
        }

        $prefs = auth()->user()->preferences ?? [];
        $history = auth()->user()->watchHistory()
            ->where('tmdb_id', $tmdbId)
            ->where('media_type', $mediaType)
            ->first();

        if ($history !== null && $history->progress_seconds > 30) {
            $context['progress_seconds'] = $history->progress_seconds;
        }

        $context['autoplay'] = UserPreferences::bool($prefs, 'autoplay_on_watch', true);
        $context['muted'] = UserPreferences::bool($prefs, 'start_muted', false);
        $context['continue_prompt'] = UserPreferences::bool($prefs, 'resume_prompt', true);

        $subtitle = UserPreferences::get($prefs, 'content_language', 'en');
        if (is_string($subtitle) && $subtitle !== '') {
            $context['subtitle'] = $subtitle;
        }

        return $context;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function postMessageConfig(string $providerName): ?array
    {
        $provider = $this->findProviderConfig($providerName);

        if ($provider === null || ! isset($provider['postmessage'])) {
            return null;
        }

        /** @var array<string, mixed> $config */
        $config = $provider['postmessage'];
        $protocolKey = $config['protocol'] ?? null;

        if (is_string($protocolKey)) {
            $template = config("sources.postmessage_protocols.{$protocolKey}", []);
            $config = array_merge(is_array($template) ? $template : [], $config);
        }

        $origins = $config['origins'] ?? [];
        $config['origins'] = array_values(array_unique(array_map(
            fn (string $origin): string => rtrim($origin, '/'),
            array_filter($origins, fn ($origin): bool => is_string($origin) && $origin !== ''),
        )));

        return $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findProviderConfig(string $providerName): ?array
    {
        /** @var array<int, array<string, mixed>> $providers */
        $providers = config('sources.providers', []);

        foreach ($providers as $provider) {
            if (($provider['name'] ?? '') === $providerName) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string}>
     */
    private function resolveTrailer(int $tmdbId, string $mediaType): array
    {
        try {
            $details = $this->tmdb->details($mediaType, $tmdbId);
            $videos = $details['videos']['results'] ?? [];

            foreach ($videos as $video) {
                if ($video['site'] === 'YouTube' && in_array($video['type'], ['Trailer', 'Teaser'])) {
                    return [
                        [
                            'type' => 'youtube',
                            'url' => "https://www.youtube.com/embed/{$video['key']}",
                            'quality' => 'auto',
                            'provider' => 'YouTube Trailer',
                        ],
                    ];
                }
            }
        } catch (\Throwable) {
        }

        return [];
    }
}
