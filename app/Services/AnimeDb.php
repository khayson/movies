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

class AnimeDb
{
    /**
     * Browse / search anime catalog.
     *
     * @param  array{page?: int, size?: int, search?: string|null, genres?: string|null, sortBy?: string|null, sortOrder?: string|null, types?: string|null}  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, size: int, totalData: int, totalPage: int}}
     */
    public function browse(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $size = min(50, max(1, (int) ($filters['size'] ?? 24)));

        $query = array_filter([
            'page' => (string) $page,
            'size' => (string) $size,
            'search' => $this->normalizeOptionalString($filters['search'] ?? null),
            'genres' => $this->normalizeOptionalString($filters['genres'] ?? null),
            'sortBy' => $this->normalizeOptionalString($filters['sortBy'] ?? null),
            'sortOrder' => $this->normalizeOptionalString($filters['sortOrder'] ?? null),
            'types' => $this->normalizeOptionalString($filters['types'] ?? null),
        ], fn ($value) => $value !== null && $value !== '');

        $cacheKey = 'anime_db.browse.'.md5(serialize($query));

        /** @var array{data: list<array<string, mixed>>, meta: array{page: int, size: int, totalData: int, totalPage: int}} */
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query, $page, $size): array {
            $payload = $this->get('/anime', $query);

            if ($payload === null) {
                return [
                    'data' => [],
                    'meta' => [
                        'page' => $page,
                        'size' => $size,
                        'totalData' => 0,
                        'totalPage' => 1,
                    ],
                ];
            }

            $items = $this->extractDataList($payload);
            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

            return [
                'data' => array_map(fn (array $item): array => $this->normalizeAnime($item), $items),
                'meta' => [
                    'page' => (int) ($meta['page'] ?? $page),
                    'size' => (int) ($meta['size'] ?? $size),
                    'totalData' => (int) ($meta['totalData'] ?? count($items)),
                    'totalPage' => max(1, (int) ($meta['totalPage'] ?? 1)),
                ],
            ];
        }) ?? [
            'data' => [],
            'meta' => ['page' => $page, 'size' => $size, 'totalData' => 0, 'totalPage' => 1],
        ];
    }

    /**
     * Top-ranked anime (ascending MAL ranking).
     *
     * @return list<array<string, mixed>>
     */
    public function topRanked(int $limit = 24): array
    {
        $result = $this->browse([
            'page' => 1,
            'size' => min(50, max(1, $limit)),
            'sortBy' => 'ranking',
            'sortOrder' => 'asc',
        ]);

        return array_slice($result['data'], 0, $limit);
    }

    /**
     * Search anime by title / alternate titles.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $title, int $limit = 24): array
    {
        $title = trim($title);

        if ($title === '') {
            return [];
        }

        $result = $this->browse([
            'page' => 1,
            'size' => min(50, max(1, $limit)),
            'search' => $title,
        ]);

        return array_slice($result['data'], 0, $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $id = trim((string) $id);

        if ($id === '' || ! ctype_digit($id)) {
            return null;
        }

        $cacheKey = "anime_db.by_id.{$id}";

        return Cache::remember($cacheKey, now()->addDays(3), function () use ($id): ?array {
            $payload = $this->get("/anime/by-id/{$id}");

            if ($payload === null) {
                return null;
            }

            if ($this->looksLikeAnime($payload)) {
                return $this->normalizeAnime($payload);
            }

            $items = $this->extractDataList($payload);

            return $items === [] ? null : $this->normalizeAnime($items[0]);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function byRanking(int $rank): ?array
    {
        if ($rank < 1) {
            return null;
        }

        $cacheKey = "anime_db.by_ranking.{$rank}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($rank): ?array {
            $payload = $this->get("/anime/by-ranking/{$rank}");

            if ($payload === null) {
                return null;
            }

            if ($this->looksLikeAnime($payload)) {
                return $this->normalizeAnime($payload);
            }

            $items = $this->extractDataList($payload);

            return $items === [] ? null : $this->normalizeAnime($items[0]);
        });
    }

    /**
     * @return list<string>
     */
    public function genres(): array
    {
        /** @var list<string> */
        return Cache::remember('anime_db.genres', now()->addDays(7), function (): array {
            $payload = $this->get('/genre');

            if ($payload === null) {
                return [];
            }

            if (array_is_list($payload)) {
                return array_values(array_filter(array_map(
                    fn ($item) => is_string($item) ? $item : (is_array($item) ? ($item['name'] ?? $item['genre'] ?? null) : null),
                    $payload
                ), fn ($item) => is_string($item) && $item !== ''));
            }

            foreach (['genres', 'data', 'results'] as $key) {
                if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                    continue;
                }

                return array_values(array_filter(array_map(
                    fn ($item) => is_string($item) ? $item : (is_array($item) ? ($item['name'] ?? $item['genre'] ?? null) : null),
                    $payload[$key]
                ), fn ($item) => is_string($item) && $item !== ''));
            }

            return [];
        }) ?? [];
    }

    /**
     * Map Anime DB titles onto TMDB cards for existing media rows.
     *
     * @param  list<array<string, mixed>>  $animeItems
     * @return list<array<string, mixed>>
     */
    public function toTmdbCards(array $animeItems, Tmdb $tmdb, int $limit = 12): array
    {
        $cards = [];

        foreach (array_slice($animeItems, 0, max($limit * 2, $limit)) as $item) {
            if (count($cards) >= $limit) {
                break;
            }

            $title = $item['title'] ?? null;

            if (! is_string($title) || $title === '') {
                continue;
            }

            $typeHint = $this->preferredTmdbType($item['type'] ?? null);
            $cacheKey = 'anime_db.tmdb.'.md5($typeHint.'|'.Str::lower($title));

            /** @var array<string, mixed>|null $match */
            $match = Cache::remember($cacheKey, now()->addDays(3), function () use ($tmdb, $title, $typeHint): ?array {
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
                    } elseif ($candidateTitle !== '' && str_contains($needle, $candidateTitle)) {
                        $score += 20;
                    }

                    if ($mediaType === $typeHint) {
                        $score += 30;
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

            $match['anime_id'] = $item['id'] ?? null;
            $match['anime_ranking'] = $item['ranking'] ?? null;
            $match['anime_episodes'] = $item['episodes'] ?? null;
            $match['mal_link'] = $item['link'] ?? null;
            $cards[] = $match;
        }

        return $cards;
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

        $host = (string) config('services.anime_db.host', 'anime-db.p.rapidapi.com');

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

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractDataList(array $payload): array
    {
        foreach (['data', 'results', 'anime', 'items'] as $key) {
            if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                continue;
            }

            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter($payload[$key], fn ($item) => is_array($item) && $this->looksLikeAnime($item)));

            if ($list !== []) {
                return $list;
            }
        }

        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $list */
            $list = array_values(array_filter($payload, fn ($item) => is_array($item) && $this->looksLikeAnime($item)));

            return $list;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function looksLikeAnime(array $record): bool
    {
        return isset($record['title'])
            || isset($record['id'])
            || isset($record['_id'])
            || isset($record['ranking'])
            || isset($record['synopsis']);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function normalizeAnime(array $record): array
    {
        $genres = [];

        if (isset($record['genres']) && is_array($record['genres'])) {
            $genres = array_values(array_filter($record['genres'], 'is_string'));
        }

        $alternates = [];

        if (isset($record['alternativeTitles']) && is_array($record['alternativeTitles'])) {
            $alternates = array_values(array_filter($record['alternativeTitles'], 'is_string'));
        }

        $id = $record['id'] ?? $record['_id'] ?? null;

        return [
            'id' => $id !== null ? (string) $id : null,
            'title' => isset($record['title']) && is_string($record['title']) ? $record['title'] : 'Untitled',
            'alternativeTitles' => $alternates,
            'genres' => $genres,
            'image' => isset($record['image']) && is_string($record['image']) ? $record['image'] : null,
            'thumb' => isset($record['thumb']) && is_string($record['thumb']) ? $record['thumb'] : null,
            'link' => isset($record['link']) && is_string($record['link']) ? $record['link'] : null,
            'ranking' => isset($record['ranking']) && is_numeric($record['ranking']) ? (int) $record['ranking'] : null,
            'synopsis' => isset($record['synopsis']) && is_string($record['synopsis']) ? $record['synopsis'] : null,
            'episodes' => isset($record['episodes']) && is_numeric($record['episodes']) ? (int) $record['episodes'] : null,
            'status' => isset($record['status']) && is_string($record['status']) ? $record['status'] : null,
            'type' => isset($record['type']) && is_string($record['type']) ? $record['type'] : null,
        ];
    }

    private function preferredTmdbType(?string $animeType): string
    {
        $animeType = Str::lower((string) $animeType);

        return in_array($animeType, ['movie', 'special', 'ova', 'ona'], true) ? 'movie' : 'tv';
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
