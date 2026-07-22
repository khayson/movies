<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SourceResolver
{
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
