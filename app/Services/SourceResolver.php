<?php

namespace App\Services;

use App\Models\ProviderAnalytic;
use App\Support\UserPreferences;
use Illuminate\Support\Facades\Cache;

class SourceResolver
{
    /** @var array<string, int> Provider reliability scores (higher = better) */
    private const PROVIDER_SCORES = [
        'CineSrc Direct' => 98,
        'CineSrc' => 95,
        'VidCore' => 85,
        'VidPhantom' => 80,
        'VidSrc' => 82,
        'EzVidAPI' => 75,
        'VidLink' => 78,
        'SuperEmbed' => 70,
        'Embed API' => 65,
        'AutoEmbed' => 72,
        'MoviesAPI' => 68,
        'VidBinge' => 74,
        'VikingEmbed' => 60,
    ];

    /** @var array<int, string> */
    private const PLAYABLE_TYPES = ['embed', 'hls'];

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool}>
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
            [...$this->resolveCineSrc($tmdbId, $mediaType, $season, $episode), ...$sources],
        );
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
     * Select the best server index based on user history, provider reliability, time-of-day analytics, and error tracking.
     */
    public function recommendServer(int $tmdbId, string $mediaType, ?int $season, ?int $episode): int
    {
        $sources = $this->resolve($tmdbId, $mediaType, $season, $episode);
        if (count($sources) === 0) {
            return 0;
        }

        $prefs = auth()->check() ? (auth()->user()->preferences ?? []) : [];
        $rememberLastServer = UserPreferences::bool($prefs, 'remember_last_server', true);
        $useProviderScores = UserPreferences::bool($prefs, 'use_provider_scores', true);
        $preferHlsDirect = UserPreferences::bool($prefs, 'prefer_hls_direct', false);

        $userLastServer = null;
        $defaultSource = null;

        if (auth()->check()) {
            $user = auth()->user();
            if ($rememberLastServer) {
                $history = $user->watchHistory()
                    ->where('tmdb_id', $tmdbId)
                    ->where('media_type', $mediaType)
                    ->first();
                $userLastServer = $history?->last_server;
            }
            $defaultSource = $user->preferences['default_source'] ?? null;
        }

        $failedProviders = Cache::get('failed_providers', []);
        $hourlyScores = $useProviderScores ? $this->getHourlyScores() : [];
        $regionScores = $useProviderScores ? $this->getRegionScores() : [];

        $bestIndex = 0;
        $bestScore = -1;

        foreach ($sources as $i => $source) {
            if (! in_array($source['type'], self::PLAYABLE_TYPES, true)) {
                continue;
            }

            $provider = $source['provider'] ?? '';

            if (! $useProviderScores) {
                $score = 100 - $i;
            } else {
                $score = self::PROVIDER_SCORES[$provider] ?? 50;

                if ($provider === $userLastServer) {
                    $score += 20;
                }

                if ($provider === $defaultSource) {
                    $score += 15;
                }

                if ($preferHlsDirect && $provider === 'CineSrc Direct') {
                    $score += 25;
                }

                if (isset($hourlyScores[$provider])) {
                    $score += $hourlyScores[$provider];
                }

                if (isset($regionScores[$provider])) {
                    $score += $regionScores[$provider];
                }

                if (isset($failedProviders[$provider])) {
                    $failedAt = $failedProviders[$provider];
                    $minutesAgo = (time() - $failedAt) / 60;
                    $score -= max(0, (int) (30 - $minutesAgo));
                }
            }

            if ($preferHlsDirect && ! $useProviderScores && $provider === 'CineSrc Direct') {
                $score += 50;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $i;
            }
        }

        return $bestIndex;
    }

    public function reportFailure(string $provider): void
    {
        $failed = Cache::get('failed_providers', []);
        $failed[$provider] = time();
        Cache::put('failed_providers', $failed, now()->addMinutes(30));

        $this->recordAnalytic($provider, 'failure');
    }

    public function reportSuccess(string $provider): void
    {
        $this->recordAnalytic($provider, 'success');
    }

    public function reportBuffering(string $provider, int $loadTimeMs = 0): void
    {
        $this->recordAnalytic($provider, 'buffer', $loadTimeMs);
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
        $failed = Cache::get('failed_providers', []);
        $health = [];

        foreach (array_keys(self::PROVIDER_SCORES) as $provider) {
            if (! isset($failed[$provider])) {
                $health[$provider] = 100;

                continue;
            }

            $minutesAgo = (time() - $failed[$provider]) / 60;
            $health[$provider] = $minutesAgo > 15 ? 75 : ($minutesAgo > 5 ? 50 : 25);
        }

        return $health;
    }

    /**
     * @return array<string, int>
     */
    private function getHourlyScores(): array
    {
        $hour = (int) now()->format('G');

        return Cache::remember("provider_hourly_scores.{$hour}", now()->addMinutes(15), function () use ($hour): array {
            $analytics = ProviderAnalytic::where('hour_bucket', $hour)
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->selectRaw('provider, SUM(success_count) as wins, SUM(failure_count) as fails, AVG(avg_load_ms) as load_ms')
                ->groupBy('provider')
                ->get();

            $scores = [];
            foreach ($analytics as $row) {
                $total = $row->wins + $row->fails;
                if ($total < 5) {
                    continue;
                }

                $successRate = $row->wins / $total;
                $loadPenalty = min(10, (int) ($row->load_ms / 1000));
                $scores[$row->provider] = (int) round($successRate * 15) - $loadPenalty;
            }

            return $scores;
        });
    }

    /**
     * @return array<string, int>
     */
    private function getRegionScores(): array
    {
        $region = $this->detectRegion();

        return Cache::remember("provider_region_scores.{$region}", now()->addMinutes(30), function () use ($region): array {
            $analytics = ProviderAnalytic::where('region', $region)
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->selectRaw('provider, SUM(success_count) as wins, SUM(failure_count) as fails, SUM(buffer_count) as buffers')
                ->groupBy('provider')
                ->get();

            $scores = [];
            foreach ($analytics as $row) {
                $total = $row->wins + $row->fails;
                if ($total < 5) {
                    continue;
                }

                $successRate = $row->wins / $total;
                $bufferRate = $total > 0 ? $row->buffers / $total : 0;
                $scores[$row->provider] = (int) round($successRate * 10) - (int) round($bufferRate * 8);
            }

            return $scores;
        });
    }

    private function recordAnalytic(string $provider, string $event, int $loadTimeMs = 0): void
    {
        $hour = (int) now()->format('G');
        $region = $this->detectRegion();
        $date = now()->toDateString();

        $record = ProviderAnalytic::firstOrCreate(
            ['provider' => $provider, 'region' => $region, 'hour_bucket' => $hour, 'date' => $date],
            ['success_count' => 0, 'failure_count' => 0, 'buffer_count' => 0, 'avg_load_ms' => 0],
        );

        match ($event) {
            'success' => $record->increment('success_count'),
            'failure' => $record->increment('failure_count'),
            'buffer' => $record->increment('buffer_count'),
            default => null,
        };

        if ($loadTimeMs > 0 && $record->success_count > 0) {
            $currentAvg = $record->avg_load_ms;
            $newAvg = (int) round(($currentAvg * ($record->success_count - 1) + $loadTimeMs) / $record->success_count);
            $record->update(['avg_load_ms' => $newAvg]);
        }
    }

    private function detectRegion(): string
    {
        $ip = request()->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'local';
        }

        return Cache::remember("geo_region.{$ip}", now()->addHours(24), function (): string {
            $timezone = config('app.timezone', 'UTC');

            return match (true) {
                str_contains($timezone, 'America') => 'NA',
                str_contains($timezone, 'Europe') => 'EU',
                str_contains($timezone, 'Asia') => 'AS',
                str_contains($timezone, 'Africa') => 'AF',
                str_contains($timezone, 'Australia'), str_contains($timezone, 'Pacific') => 'OC',
                default => 'unknown',
            };
        });
    }

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string, supports_postmessage?: bool}>
     */
    private function resolveCineSrc(int $tmdbId, string $mediaType, ?int $season, ?int $episode): array
    {
        $options = $this->cineSrcOptions($tmdbId, $mediaType);

        $embedUrl = app(CineSrcEmbed::class)->buildUrl($tmdbId, $mediaType, $season, $episode, $options);

        $sources = [[
            'type' => 'embed',
            'url' => $embedUrl,
            'quality' => is_string($options['quality'] ?? null) ? $options['quality'] : 'auto',
            'provider' => 'CineSrc',
            'supports_postmessage' => true,
        ]];

        $direct = app(CineSrcStreamResolver::class)->resolve($tmdbId, $mediaType, $season, $episode);

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
     * @return array<int, array{type: string, url: string, quality: string, provider: string}>
     */
    private function resolveTrailer(int $tmdbId, string $mediaType): array
    {
        $tmdb = app(Tmdb::class);

        try {
            $details = $tmdb->details($mediaType, $tmdbId);
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
