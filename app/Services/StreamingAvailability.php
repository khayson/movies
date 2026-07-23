<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class StreamingAvailability
{
    /**
     * @return array<string, mixed>|null
     */
    public function getUserCountry(): string
    {
        $user = auth()->user();

        return $user?->preferences['streaming_country'] ?? 'us';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByTmdbId(string $type, int $tmdbId, ?string $country = null): ?array
    {
        $country ??= $this->getUserCountry();
        $showType = $type === 'tv' ? 'series' : 'movie';
        $cacheKey = "streaming.{$showType}.{$tmdbId}.{$country}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($showType, $tmdbId): ?array {
            if (RateLimiter::tooManyAttempts('rapidapi', 450) || RateLimiter::tooManyAttempts('rapidapi-per-user', 30)) {
                return null;
            }

            RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);
            RateLimiter::hit('rapidapi-per-user', 60 * 60);

            try {
                $response = Http::withHeaders([
                    'X-RapidAPI-Key' => config('services.rapidapi.key'),
                    'X-RapidAPI-Host' => 'streaming-availability.p.rapidapi.com',
                ])
                    ->baseUrl('https://streaming-availability.p.rapidapi.com')
                    ->get("/shows/{$showType}/{$tmdbId}", [
                        'output_language' => 'en',
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> */
                    return $response->json();
                }

                return null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $showData
     * @return array<int, array{service: string, service_id: string, type: string, link: string, quality: string|null, price: array<string, mixed>|null, logo: string, dark_logo: string}>
     */
    public function getStreamingOptions(array $showData, string $country = 'us'): array
    {
        $options = [];
        $streamingOptions = $showData['streamingOptions'][$country] ?? [];

        foreach ($streamingOptions as $option) {
            $service = $option['service'] ?? [];
            $options[] = [
                'service' => $service['name'] ?? $service['id'] ?? 'Unknown',
                'service_id' => $service['id'] ?? '',
                'type' => $option['type'] ?? 'unknown',
                'link' => $option['link'] ?? '#',
                'quality' => $option['quality'] ?? null,
                'price' => $option['price'] ?? null,
                'logo' => $service['imageSet']['lightThemeImage'] ?? $service['imageSet']['darkThemeImage'] ?? '',
                'dark_logo' => $service['imageSet']['darkThemeImage'] ?? '',
            ];
        }

        $seen = [];
        $unique = [];
        foreach ($options as $opt) {
            $key = $opt['service_id'].'-'.$opt['type'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $opt;
            }
        }

        usort($unique, function (array $a, array $b): int {
            $order = ['subscription' => 0, 'free' => 1, 'addon' => 2, 'rent' => 3, 'buy' => 4];

            return ($order[$a['type']] ?? 5) <=> ($order[$b['type']] ?? 5);
        });

        return $unique;
    }
}
