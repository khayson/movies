<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class TvMaze
{
    /**
     * Popular-ish catalog of shows that already map to TMDB IDs.
     *
     * @return list<array<string, mixed>>
     */
    public function catalog(int $limit = 24): array
    {
        /** @var list<array<string, mixed>> $shows */
        $shows = Cache::remember('tvmaze.catalog', now()->addHours(12), function (): array {
            $payload = $this->get('/shows', ['page' => 0]);

            if (! is_array($payload) || ! array_is_list($payload)) {
                return [];
            }

            return $this->mapShows($payload);
        }) ?? [];

        return array_slice($shows, 0, $limit);
    }

    /**
     * Shows airing today (US schedule).
     *
     * @return list<array<string, mixed>>
     */
    public function schedule(int $limit = 24): array
    {
        /** @var list<array<string, mixed>> $shows */
        $shows = Cache::remember('tvmaze.schedule.'.now()->toDateString(), now()->addHours(6), function (): array {
            $payload = $this->get('/schedule', ['country' => 'US']);

            if (! is_array($payload) || ! array_is_list($payload)) {
                return [];
            }

            $shows = [];

            foreach ($payload as $entry) {
                if (! is_array($entry) || ! isset($entry['show']) || ! is_array($entry['show'])) {
                    continue;
                }

                $mapped = $this->mapShow($entry['show']);

                if ($mapped !== null) {
                    $shows[] = $mapped;
                }
            }

            return $this->uniqueById($shows);
        }) ?? [];

        return array_slice($shows, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $cacheKey = 'tvmaze.search.'.md5(Str::lower($query));

        /** @var list<array<string, mixed>> $shows */
        $shows = Cache::remember($cacheKey, now()->addHours(6), function () use ($query): array {
            $payload = $this->get('/search/shows', ['q' => $query]);

            if (! is_array($payload) || ! array_is_list($payload)) {
                return [];
            }

            $shows = [];

            foreach ($payload as $entry) {
                if (! is_array($entry) || ! isset($entry['show']) || ! is_array($entry['show'])) {
                    continue;
                }

                $mapped = $this->mapShow($entry['show']);

                if ($mapped !== null) {
                    $shows[] = $mapped;
                }
            }

            return $shows;
        }) ?? [];

        return array_slice($shows, 0, $limit);
    }

    /**
     * Minimal TMDB-shaped TV details payload.
     *
     * @return array<string, mixed>|null
     */
    public function showByTmdbId(int $tmdbId): ?array
    {
        if ($tmdbId < 1) {
            return null;
        }

        $cacheKey = "tvmaze.by_tmdb.{$tmdbId}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($tmdbId): ?array {
            $show = $this->get('/lookup/shows', ['tmdb' => $tmdbId]);

            if (! is_array($show)) {
                return null;
            }

            $mapped = $this->mapShow($show);

            if ($mapped === null) {
                return null;
            }

            $imdbId = is_array($show['externals'] ?? null) && isset($show['externals']['imdb'])
                ? (string) $show['externals']['imdb']
                : null;

            return [
                'id' => $tmdbId,
                'name' => $mapped['name'],
                'overview' => $mapped['overview'],
                'poster_path' => $mapped['poster_path'],
                'backdrop_path' => $mapped['backdrop_path'],
                'vote_average' => $mapped['vote_average'],
                'first_air_date' => $mapped['first_air_date'],
                'status' => $show['status'] ?? null,
                'number_of_episodes' => $show['runtime'] ?? null,
                'seasons' => [],
                'credits' => ['cast' => []],
                'videos' => ['results' => []],
                'similar' => ['results' => []],
                'recommendations' => ['results' => []],
                'reviews' => ['results' => []],
                'external_ids' => ['imdb_id' => $imdbId],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|list<mixed>|null
     */
    private function get(string $path, array $query = []): ?array
    {
        $host = (string) config('services.tvmaze.base_url', 'https://api.tvmaze.com');

        try {
            $response = Http::baseUrl($host)
                ->acceptJson()
                ->timeout(6)
                ->connectTimeout(3)
                ->retry(1, 150, function (Throwable $exception, PendingRequest $request): bool {
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
     * @param  list<mixed>  $shows
     * @return list<array<string, mixed>>
     */
    private function mapShows(array $shows): array
    {
        $mapped = [];

        foreach ($shows as $show) {
            if (! is_array($show)) {
                continue;
            }

            $item = $this->mapShow($show);

            if ($item !== null) {
                $mapped[] = $item;
            }
        }

        return $this->uniqueById($mapped);
    }

    /**
     * @param  array<string, mixed>  $show
     * @return array<string, mixed>|null
     */
    private function mapShow(array $show): ?array
    {
        $externals = is_array($show['externals'] ?? null) ? $show['externals'] : [];
        $tmdbId = isset($externals['tmdb']) && is_numeric($externals['tmdb'])
            ? (int) $externals['tmdb']
            : null;

        if ($tmdbId === null) {
            return null;
        }

        $image = is_array($show['image'] ?? null) ? $show['image'] : [];
        $poster = isset($image['medium']) && is_string($image['medium']) ? $image['medium'] : null;
        $original = isset($image['original']) && is_string($image['original']) ? $image['original'] : $poster;
        $summary = isset($show['summary']) && is_string($show['summary'])
            ? trim(html_entity_decode(strip_tags($show['summary'])))
            : '';
        $rating = is_array($show['rating'] ?? null) ? ($show['rating']['average'] ?? 0) : 0;

        return [
            'id' => $tmdbId,
            'name' => (string) ($show['name'] ?? 'Untitled'),
            'title' => (string) ($show['name'] ?? 'Untitled'),
            'media_type' => 'tv',
            'overview' => $summary,
            'poster_path' => $poster,
            'backdrop_path' => $original,
            'vote_average' => is_numeric($rating) ? (float) $rating : 0,
            'first_air_date' => (string) ($show['premiered'] ?? ''),
            'genre_ids' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $shows
     * @return list<array<string, mixed>>
     */
    private function uniqueById(array $shows): array
    {
        $seen = [];
        $unique = [];

        foreach ($shows as $show) {
            $id = $show['id'] ?? null;

            if (! is_numeric($id) || isset($seen[(int) $id])) {
                continue;
            }

            $seen[(int) $id] = true;
            $unique[] = $show;
        }

        return $unique;
    }
}
