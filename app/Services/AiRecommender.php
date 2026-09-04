<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AiRecommender
{
    private const HOST = 'ai-movie-recommender.p.rapidapi.com';

    public function __construct(private RapidApiClient $rapidApi) {}

    public function available(): bool
    {
        return $this->rapidApi->configured();
    }

    /**
     * @return array{success: bool, movies: array<int, mixed>, unavailable?: bool, rate_limited?: bool}
     */
    public function search(string $query): array
    {
        if (! $this->available()) {
            return ['success' => false, 'movies' => [], 'unavailable' => true];
        }

        if ($this->rapidApi->rateLimited()) {
            return ['success' => false, 'movies' => [], 'rate_limited' => true];
        }

        $cacheKey = 'ai_rec.search.'.md5($query);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['success'] ?? false) === true && ($cached['movies'] ?? []) !== []) {
            return $cached;
        }

        $payload = $this->rapidApi->getJson(
            self::HOST,
            'https://ai-movie-recommender.p.rapidapi.com/api/search',
            ['q' => $query],
        );

        if ($payload === null) {
            return ['success' => false, 'movies' => []];
        }

        $result = [
            'success' => (bool) ($payload['success'] ?? false),
            'movies' => is_array($payload['movies'] ?? null) ? $payload['movies'] : [],
        ];

        if ($result['success'] && $result['movies'] !== []) {
            Cache::put($cacheKey, $result, now()->addDays(3));
        }

        return $result;
    }

    /**
     * @return array{success: bool, movies: array<int, mixed>, unavailable?: bool, rate_limited?: bool}
     */
    public function trending(): array
    {
        if (! $this->available()) {
            return ['success' => false, 'movies' => [], 'unavailable' => true];
        }

        if ($this->rapidApi->rateLimited()) {
            return ['success' => false, 'movies' => [], 'rate_limited' => true];
        }

        $cacheKey = 'ai_rec.trending';
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['success'] ?? false) === true && ($cached['movies'] ?? []) !== []) {
            return $cached;
        }

        $payload = $this->rapidApi->getJson(
            self::HOST,
            'https://ai-movie-recommender.p.rapidapi.com/api/trending',
            userBucket: null,
        );

        if ($payload === null) {
            return ['success' => false, 'movies' => []];
        }

        $result = [
            'success' => (bool) ($payload['success'] ?? false),
            'movies' => is_array($payload['movies'] ?? null) ? $payload['movies'] : [],
        ];

        if ($result['success'] && $result['movies'] !== []) {
            Cache::put($cacheKey, $result, now()->addDay());
        }

        return $result;
    }
}
