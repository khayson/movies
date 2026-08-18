<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

class RottenTomatoes
{
    /**
     * Lookup Tomatometer + audience scores by title.
     *
     * @return array{tomatometer: int|null, audience: int|null, consensus: string|null, title: string|null}|null
     */
    public function scores(?string $title, string $type = 'movie', ?int $year = null): ?array
    {
        $title = $this->normalizeTitle($title);

        if ($title === null) {
            return null;
        }

        $type = $type === 'tv' ? 'tv' : 'movie';
        $cacheKey = 'rt.scores.'.md5($type.'|'.Str::lower($title).'|'.($year ?? ''));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($title, $type, $year): ?array {
            $path = $type === 'tv' ? '/tv_shows/by_name' : '/movies/by_name';
            $payload = $this->get($path, ['name' => $title]);

            if ($payload === null) {
                $payload = $this->get('/by_name', ['name' => $title]);
            }

            if ($payload === null) {
                return null;
            }

            $record = $this->pickBestMatch($payload, $title, $year);

            if ($record === null) {
                return null;
            }

            $tomatometer = $this->extractPercent($record, ['tomatometer_score', 'tomatometer', 'tomatoMeter', 'critics_score']);
            $audience = $this->extractPercent($record, ['audience_score', 'audienceScore', 'popcornmeter', 'audience']);
            $consensus = $this->extractConsensus($record);
            $matchedTitle = $this->extractTitle($record) ?? $title;

            if ($tomatometer === null && $audience === null && $consensus === null) {
                return null;
            }

            return [
                'tomatometer' => $tomatometer,
                'audience' => $audience,
                'consensus' => $consensus,
                'title' => $matchedTitle,
            ];
        });
    }

    /**
     * Paginated / sortable movie catalog from Rotten Tomatoes.
     *
     * @return list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>
     */
    public function queryMovies(int $page = 1, ?string $sortBy = null, int $limit = 18): array
    {
        $query = array_filter([
            'page' => $page,
            'sortby' => $sortBy,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->cachedList(
            'rt.query_movies.'.md5(serialize($query)),
            '/query_movies',
            $query,
            $limit,
            'movie',
            now()->addHours(12),
        );
    }

    /**
     * Daily top Netflix TV shows ranked by Rotten Tomatoes.
     *
     * @return list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>
     */
    public function netflixTopTv(int $limit = 18): array
    {
        return $this->cachedList(
            'rt.netflix_top_tv',
            '/today-top100TVshows-netflix',
            [],
            $limit,
            'tv',
            now()->addHours(6),
        );
    }

    /**
     * Upcoming theatrical / limited-series titles.
     *
     * @return list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>
     */
    public function comingSoon(int $limit = 18): array
    {
        return $this->cachedList(
            'rt.coming_soon',
            '/soon-in-theaters',
            [],
            $limit,
            'movie',
            now()->addHours(12),
        );
    }

    /**
     * Map RT list titles onto TMDB cards so existing media rows can link to detail/watch.
     *
     * @param  list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>  $rtItems
     * @return list<array<string, mixed>>
     */
    public function toTmdbCards(array $rtItems, Tmdb $tmdb, int $limit = 12): array
    {
        $cards = [];

        foreach (array_slice($rtItems, 0, max($limit * 2, $limit)) as $item) {
            if (count($cards) >= $limit) {
                break;
            }

            $title = $item['title'] ?? null;

            if (! is_string($title) || $title === '') {
                continue;
            }

            $preferredType = ($item['type'] ?? 'movie') === 'tv' ? 'tv' : 'movie';
            $year = $item['year'] ?? null;
            $cacheKey = 'rt.tmdb.'.md5($preferredType.'|'.Str::lower($title).'|'.($year ?? ''));

            /** @var array<string, mixed>|null $match */
            $match = Cache::remember($cacheKey, now()->addDays(3), function () use ($tmdb, $title, $preferredType, $year): ?array {
                try {
                    $results = $tmdb->search($title)['results'] ?? [];
                } catch (Throwable) {
                    return null;
                }

                $best = null;
                $bestScore = -1;

                foreach ($results as $result) {
                    if (! is_array($result)) {
                        continue;
                    }

                    $mediaType = $result['media_type'] ?? null;

                    if (! in_array($mediaType, ['movie', 'tv'], true)) {
                        continue;
                    }

                    $candidateTitle = Str::lower((string) ($result['title'] ?? $result['name'] ?? ''));
                    $needle = Str::lower($title);
                    $score = 0;

                    if ($candidateTitle === $needle) {
                        $score += 100;
                    } elseif ($candidateTitle !== '' && str_contains($candidateTitle, $needle)) {
                        $score += 40;
                    }

                    if ($mediaType === $preferredType) {
                        $score += 30;
                    }

                    $candidateYear = (int) Str::substr((string) ($result['release_date'] ?? $result['first_air_date'] ?? ''), 0, 4);

                    if ($year !== null && $candidateYear === $year) {
                        $score += 50;
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $result;
                    }
                }

                return $best;
            });

            if ($match === null) {
                continue;
            }

            $match['rt_tomatometer'] = $item['tomatometer'] ?? null;
            $match['rt_audience'] = $item['audience'] ?? null;
            $cards[] = $match;
        }

        return $cards;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>
     */
    private function cachedList(string $cacheKey, string $path, array $query, int $limit, string $type, mixed $ttl): array
    {
        /** @var list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}> */
        $items = Cache::remember($cacheKey, $ttl, function () use ($path, $query, $type): array {
            $payload = $this->get($path, $query);

            if ($payload === null) {
                return [];
            }

            return $this->mapListRecords($payload, $type);
        }) ?? [];

        return array_slice($items, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{title: string, image: string|null, tomatometer: int|null, audience: int|null, year: int|null, type: string}>
     */
    private function mapListRecords(array $payload, string $type): array
    {
        $mapped = [];

        foreach ($this->normalizeRecordList($payload) as $record) {
            $title = $this->extractTitle($record);

            if ($title === null) {
                continue;
            }

            $mapped[] = [
                'title' => $title,
                'image' => $this->extractImage($record),
                'tomatometer' => $this->extractPercent($record, ['tomatometer_score', 'tomatometer', 'tomatoMeter', 'critics_score']),
                'audience' => $this->extractPercent($record, ['audience_score', 'audienceScore', 'popcornmeter', 'audience']),
                'year' => $this->extractYear($record),
                'type' => $type,
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function get(string $path, array $query = []): ?array
    {
        if (blank(config('services.rapidapi.key'))) {
            return null;
        }

        if (RateLimiter::tooManyAttempts('rapidapi', 450) || RateLimiter::tooManyAttempts('rapidapi-per-user', 30)) {
            return null;
        }

        RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);
        RateLimiter::hit('rapidapi-per-user', 60 * 60);

        $host = (string) config('services.rottentomatoes.host', 'rottentomato.p.rapidapi.com');

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
                ->get($path, $query);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function normalizeTitle(?string $title): ?string
    {
        if ($title === null) {
            return null;
        }

        $title = trim($title);

        return $title !== '' ? $title : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function pickBestMatch(array $payload, string $title, ?int $year): ?array
    {
        $candidates = $this->normalizeRecordList($payload);

        if ($candidates === []) {
            return null;
        }

        $needle = Str::lower($title);

        usort($candidates, function (array $a, array $b) use ($needle, $year): int {
            return $this->matchScore($b, $needle, $year) <=> $this->matchScore($a, $needle, $year);
        });

        return $candidates[0];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function normalizeRecordList(array $payload): array
    {
        if ($this->looksLikeTitleRecord($payload)) {
            return [$payload];
        }

        foreach (['results', 'data', 'movies', 'tv_shows', 'items', 'shows', 'titles'] as $key) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter($payload[$key], fn ($item) => is_array($item) && $this->looksLikeTitleRecord($item)));

            if ($list !== []) {
                return $list;
            }
        }

        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter($payload, fn ($item) => is_array($item) && $this->looksLikeTitleRecord($item)));

            return $list;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function looksLikeTitleRecord(array $record): bool
    {
        return isset($record['title'])
            || isset($record['name'])
            || isset($record['image'])
            || isset($record['poster'])
            || isset($record['tomatometer_score'])
            || isset($record['audience_score'])
            || isset($record['critics_consensus']);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function extractTitle(array $record): ?string
    {
        foreach (['title', 'name', 'primaryTitle'] as $key) {
            if (isset($record[$key]) && is_string($record[$key]) && trim($record[$key]) !== '') {
                return trim($record[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function extractImage(array $record): ?string
    {
        foreach (['image', 'poster', 'posterImage', 'poster_url', 'imageUrl'] as $key) {
            if (isset($record[$key]) && is_string($record[$key]) && str_starts_with($record[$key], 'http')) {
                return $record[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function matchScore(array $record, string $needle, ?int $year): int
    {
        $candidateTitle = $this->extractTitle($record);
        $candidateTitle = $candidateTitle !== null ? Str::lower($candidateTitle) : '';

        $score = 0;

        if ($candidateTitle === $needle) {
            $score += 100;
        } elseif ($candidateTitle !== '' && str_contains($candidateTitle, $needle)) {
            $score += 40;
        } elseif ($candidateTitle !== '' && str_contains($needle, $candidateTitle)) {
            $score += 20;
        }

        if ($year !== null) {
            $candidateYear = $this->extractYear($record);

            if ($candidateYear === $year) {
                $score += 50;
            } elseif ($candidateYear !== null) {
                $score -= 10;
            }
        }

        return $score;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function extractYear(array $record): ?int
    {
        foreach (['year', 'releaseYear', 'release_year'] as $key) {
            if (isset($record[$key]) && is_numeric($record[$key])) {
                return (int) $record[$key];
            }
        }

        foreach (['release_date', 'releaseDate', 'year'] as $key) {
            if (! isset($record[$key]) || ! is_string($record[$key])) {
                continue;
            }

            if (preg_match('/\b(19|20)\d{2}\b/', $record[$key], $matches) === 1) {
                return (int) $matches[0];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  list<string>  $keys
     */
    private function extractPercent(array $record, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $record) || $record[$key] === null || $record[$key] === '') {
                continue;
            }

            $value = $record[$key];

            if (is_string($value)) {
                $value = str_replace('%', '', trim($value));
            }

            if (! is_numeric($value)) {
                continue;
            }

            $score = (int) round((float) $value);

            if ($score < 0 || $score > 100) {
                continue;
            }

            return $score;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function extractConsensus(array $record): ?string
    {
        foreach (['critics_consensus', 'criticsConsensus', 'consensus'] as $key) {
            if (! isset($record[$key]) || ! is_string($record[$key])) {
                continue;
            }

            $text = trim($record[$key]);

            if ($text === '') {
                continue;
            }

            return Str::limit(preg_replace('/\s+/', ' ', $text) ?? $text, 280);
        }

        return null;
    }
}
