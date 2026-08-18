<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class Imdb
{
    /**
     * Normalized ratings for a title (IMDb score + Metascore).
     *
     * @return array{rating: float|null, votes: int|null, metascore: int|null}|null
     */
    public function ratings(?string $imdbId): ?array
    {
        $imdbId = $this->normalizeTitleId($imdbId);

        if ($imdbId === null) {
            return null;
        }

        $cacheKey = "imdb.ratings.{$imdbId}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($imdbId): ?array {
            $ratingPayload = $this->get("/api/imdb/{$imdbId}/rating");
            $metascorePayload = $this->get("/api/imdb/{$imdbId}/metascore");

            if ($ratingPayload === null && $metascorePayload === null) {
                return null;
            }

            return [
                'rating' => $this->extractRating($ratingPayload),
                'votes' => $this->extractVotes($ratingPayload),
                'metascore' => $this->extractMetascore($metascorePayload),
            ];
        });
    }

    /**
     * Similar titles from IMDb (raw title objects).
     *
     * @return list<array<string, mixed>>
     */
    public function similar(?string $imdbId, int $limit = 8): array
    {
        $imdbId = $this->normalizeTitleId($imdbId);

        if ($imdbId === null) {
            return [];
        }

        $cacheKey = "imdb.similar.{$imdbId}";

        /** @var list<array<string, mixed>> */
        $titles = Cache::remember($cacheKey, now()->addDays(3), function () use ($imdbId): array {
            $payload = $this->get("/api/imdb/{$imdbId}/similar");

            return $this->normalizeTitleList($payload);
        }) ?? [];

        return array_slice($titles, 0, $limit);
    }

    /**
     * Filmography titles for a person (nm… id).
     *
     * @return list<array<string, mixed>>
     */
    public function castTitles(?string $personImdbId, int $limit = 40): array
    {
        $personImdbId = $this->normalizePersonId($personImdbId);

        if ($personImdbId === null) {
            return [];
        }

        $cacheKey = "imdb.cast_titles.{$personImdbId}";

        /** @var list<array<string, mixed>> */
        $titles = Cache::remember($cacheKey, now()->addDays(3), function () use ($personImdbId): array {
            $payload = $this->get("/api/imdb/cast/{$personImdbId}/titles");

            return $this->normalizeTitleList($payload);
        }) ?? [];

        return array_slice($titles, 0, $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $path): ?array
    {
        if (blank(config('services.rapidapi.key'))) {
            return null;
        }

        if (RateLimiter::tooManyAttempts('rapidapi', 450) || RateLimiter::tooManyAttempts('rapidapi-per-user', 30)) {
            return null;
        }

        RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);
        RateLimiter::hit('rapidapi-per-user', 60 * 60);

        $host = (string) config('services.imdb.host', 'imdb236.p.rapidapi.com');

        try {
            $response = Http::baseUrl("https://{$host}")
                ->withHeaders([
                    'X-RapidAPI-Key' => config('services.rapidapi.key'),
                    'X-RapidAPI-Host' => $host,
                ])
                ->acceptJson()
                ->timeout(8)
                ->connectTimeout(4)
                ->retry(2, 200, function (Throwable $exception, PendingRequest $request): bool {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError());
                })
                ->get($path);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            if (is_array($json)) {
                /** @var array<string, mixed> $json */
                return $json;
            }

            if (is_numeric($json)) {
                return ['value' => $json + 0];
            }

            return null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function normalizeTitleId(?string $imdbId): ?string
    {
        if ($imdbId === null || $imdbId === '') {
            return null;
        }

        $imdbId = trim($imdbId);

        return preg_match('/^tt\d+$/', $imdbId) === 1 ? $imdbId : null;
    }

    private function normalizePersonId(?string $imdbId): ?string
    {
        if ($imdbId === null || $imdbId === '') {
            return null;
        }

        $imdbId = trim($imdbId);

        return preg_match('/^nm\d+$/', $imdbId) === 1 ? $imdbId : null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractRating(?array $payload): ?float
    {
        if ($payload === null) {
            return null;
        }

        foreach (['averageRating', 'rating', 'imdbRating', 'value'] as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return round((float) $payload[$key], 1);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractVotes(?array $payload): ?int
    {
        if ($payload === null) {
            return null;
        }

        foreach (['numVotes', 'votes', 'voteCount'] as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (int) $payload[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractMetascore(?array $payload): ?int
    {
        if ($payload === null) {
            return null;
        }

        foreach (['metascore', 'metaScore', 'score', 'value'] as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (int) $payload[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizeTitleList(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $payload */
            return array_values(array_filter($payload, 'is_array'));
        }

        foreach (['results', 'titles', 'data', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key]) && array_is_list($payload[$key])) {
                /** @var list<array<string, mixed>> $list */
                $list = array_values(array_filter($payload[$key], 'is_array'));

                return $list;
            }
        }

        return [];
    }
}
