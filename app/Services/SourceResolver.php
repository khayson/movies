<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SourceResolver
{
    /** @var array<string, int> Provider reliability scores (higher = better) */
    private const PROVIDER_SCORES = [
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

    /**
     * @return array<int, array{type: string, url: string, quality: string, provider: string}>
     */
    public function resolve(int $tmdbId, string $mediaType = 'movie', ?int $season = null, ?int $episode = null): array
    {
        $cacheKey = "sources.{$mediaType}.{$tmdbId}.{$season}.{$episode}";

        /** @var array<int, array{type: string, url: string, quality: string, provider: string}> */
        return Cache::remember($cacheKey, now()->addMinutes(config('sources.cache_ttl')), function () use ($tmdbId, $mediaType, $season, $episode): array {
            /** @var array<int, array{driver: string, name?: string, movie_url?: string, tv_url?: string}> $providers */
            $providers = config('sources.providers', []);
            $sources = [];

            foreach ($providers as $provider) {
                $resolved = match ($provider['driver']) {
                    'embed' => $this->resolveEmbed($tmdbId, $mediaType, $provider, $season, $episode),
                    'trailer' => $this->resolveTrailer($tmdbId, $mediaType),
                    default => [],
                };

                $sources = [...$sources, ...$resolved];
            }

            return $sources;
        });
    }

    /**
     * Select the best server index based on user history, provider reliability, and error tracking.
     */
    public function recommendServer(int $tmdbId, string $mediaType, ?int $season, ?int $episode): int
    {
        $sources = $this->resolve($tmdbId, $mediaType, $season, $episode);
        if (count($sources) === 0) {
            return 0;
        }

        $userLastServer = null;
        if (auth()->check()) {
            $history = auth()->user()->watchHistory()
                ->where('tmdb_id', $tmdbId)
                ->where('media_type', $mediaType)
                ->first();
            $userLastServer = $history?->last_server;
        }

        $failedProviders = Cache::get('failed_providers', []);

        $bestIndex = 0;
        $bestScore = -1;

        foreach ($sources as $i => $source) {
            if ($source['type'] !== 'embed') {
                continue;
            }

            $provider = $source['provider'] ?? '';
            $score = self::PROVIDER_SCORES[$provider] ?? 50;

            if ($provider === $userLastServer) {
                $score += 20;
            }

            if (isset($failedProviders[$provider])) {
                $failedAt = $failedProviders[$provider];
                $minutesAgo = (time() - $failedAt) / 60;
                $score -= max(0, (int) (30 - $minutesAgo));
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
            // Fallback silently
        }

        return [];
    }
}
