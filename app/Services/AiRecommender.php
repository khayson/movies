<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class AiRecommender
{
    /**
     * @return array<string, mixed>
     */
    public function search(string $query): array
    {
        $cacheKey = 'ai_rec.search.'.md5($query);

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, now()->addDays(3), function () use ($query): array {
            if (RateLimiter::tooManyAttempts('rapidapi', 450) || RateLimiter::tooManyAttempts('rapidapi-per-user', 30)) {
                return ['success' => false, 'movies' => [], 'rate_limited' => true];
            }

            RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);
            RateLimiter::hit('rapidapi-per-user', 60 * 60);

            try {
                $response = Http::withHeaders([
                    'X-RapidAPI-Key' => config('services.rapidapi.key'),
                    'X-RapidAPI-Host' => 'ai-movie-recommender.p.rapidapi.com',
                ])
                    ->get('https://ai-movie-recommender.p.rapidapi.com/api/search', [
                        'q' => $query,
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> */
                    return $response->json();
                }

                return ['success' => false, 'movies' => []];
            } catch (\Throwable) {
                return ['success' => false, 'movies' => []];
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function trending(): array
    {
        /** @var array<string, mixed> */
        return Cache::remember('ai_rec.trending', now()->addDays(1), function (): array {
            if (RateLimiter::tooManyAttempts('rapidapi', 450)) {
                return ['success' => false, 'movies' => [], 'rate_limited' => true];
            }

            RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);

            try {
                $response = Http::withHeaders([
                    'X-RapidAPI-Key' => config('services.rapidapi.key'),
                    'X-RapidAPI-Host' => 'ai-movie-recommender.p.rapidapi.com',
                ])
                    ->get('https://ai-movie-recommender.p.rapidapi.com/api/trending');

                if ($response->successful()) {
                    /** @var array<string, mixed> */
                    return $response->json();
                }

                return ['success' => false, 'movies' => []];
            } catch (\Throwable) {
                return ['success' => false, 'movies' => []];
            }
        });
    }
}
