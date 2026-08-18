<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class Tmdb
{
    /**
     * TMDB Animation genre (movies and TV).
     */
    public const ANIMATION_GENRE_ID = 16;

    private bool $tmdbUnavailable = false;

    public function __construct(private MetadataFallback $fallback) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        $params = $this->applyUserPreferences($params);
        $cacheKey = 'tmdb.'.md5($endpoint.serialize($params));
        $staleKey = $cacheKey.'.stale';
        $ttl = $this->getTtl($endpoint);

        if ($this->tmdbUnavailable) {
            return $this->cachedOrFallback($endpoint, $params, $cacheKey, $staleKey);
        }

        try {
            /** @var array<string, mixed> */
            $payload = Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($endpoint, $params): array {
                return $this->fetchFromHosts($endpoint, $params);
            });

            Cache::put($staleKey, $payload, now()->addDays(7));

            return $payload;
        } catch (ConnectionException $e) {
            return $this->recoverFromUpstreamFailure($e, $endpoint, $params, $cacheKey, $staleKey);
        } catch (RequestException $e) {
            if (! $e->response->serverError() && $e->response->status() !== 429) {
                throw $e;
            }

            return $this->recoverFromUpstreamFailure($e, $endpoint, $params, $cacheKey, $staleKey);
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function fetchFromHosts(string $endpoint, array $params): array
    {
        $lastException = null;

        foreach ($this->hosts() as $host) {
            try {
                $response = Http::baseUrl($host)
                    ->withToken(config('tmdb.api_key'))
                    ->acceptJson()
                    ->timeout(8)
                    ->connectTimeout(3)
                    ->retry(1, 150, function (Throwable $exception, PendingRequest $request): bool {
                        return $exception instanceof ConnectionException
                            || ($exception instanceof RequestException && $exception->response->serverError());
                    })
                    ->get($endpoint, $params)
                    ->throw();

                /** @var array<string, mixed> */
                return $response->json();
            } catch (ConnectionException $e) {
                $lastException = $e;

                continue;
            } catch (RequestException $e) {
                if (! $e->response->serverError() && $e->response->status() !== 429) {
                    throw $e;
                }

                $lastException = $e;

                continue;
            }
        }

        throw $lastException ?? new ConnectionException('All TMDB hosts failed.');
    }

    /**
     * @return list<string>
     */
    private function hosts(): array
    {
        $configured = config('tmdb.base_urls', [config('tmdb.base_url')]);

        if (! is_array($configured)) {
            $configured = [config('tmdb.base_url')];
        }

        /** @var list<string> $hosts */
        $hosts = array_values(array_unique(array_filter(
            $configured,
            fn ($host): bool => is_string($host) && $host !== ''
        )));

        return $hosts !== [] ? $hosts : ['https://api.themoviedb.org/3'];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function recoverFromUpstreamFailure(Throwable $e, string $endpoint, array $params, string $cacheKey, string $staleKey): array
    {
        $this->tmdbUnavailable = true;
        report($e);

        return $this->cachedOrFallback($endpoint, $params, $cacheKey, $staleKey);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function cachedOrFallback(string $endpoint, array $params, string $cacheKey, string $staleKey): array
    {
        $cached = Cache::get($staleKey) ?? Cache::get($cacheKey);

        if (is_array($cached) && $this->hasUsablePayload($cached)) {
            return $cached;
        }

        $fallback = $this->fallback->forEndpoint($endpoint, $params);

        if (is_array($fallback) && $this->hasUsablePayload($fallback)) {
            return $fallback;
        }

        return is_array($cached) ? $cached : ['results' => []];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasUsablePayload(array $payload): bool
    {
        if (isset($payload['id']) || isset($payload['title']) || isset($payload['name'])) {
            return true;
        }

        return isset($payload['results']) && is_array($payload['results']) && $payload['results'] !== [];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function applyUserPreferences(array $params): array
    {
        $user = auth()->user();

        if (! $user) {
            $params['include_adult'] = false;

            return $params;
        }

        $prefs = $user->preferences ?? [];

        if (! isset($params['language']) && ! empty($prefs['content_language'])) {
            $params['language'] = $prefs['content_language'];
        }

        $params['include_adult'] = $user->canViewAdultContent();

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    public function trending(string $type = 'movie', string $window = 'week', int $page = 1): array
    {
        return $this->get("/trending/{$type}/{$window}", ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function popular(string $type = 'movie', int $page = 1): array
    {
        return $this->get("/{$type}/popular", ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function topRated(string $type = 'movie', int $page = 1): array
    {
        return $this->get("/{$type}/top_rated", ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function details(string $type, int $id): array
    {
        return $this->get("/{$type}/{$id}", [
            'append_to_response' => 'credits,videos,similar,recommendations,reviews,external_ids',
        ]);
    }

    /**
     * Related titles for detail pages.
     *
     * TMDB "similar" is keyword/genre based and often returns older catalog titles.
     * Prefer "recommendations" (collaborative), then fill from similar newest-first.
     *
     * @param  array<string, mixed>  $details
     * @return list<array<string, mixed>>
     */
    public function relatedFromDetails(array $details, int $limit = 12): array
    {
        /** @var list<array<string, mixed>> $recommendations */
        $recommendations = array_values(array_filter(
            $details['recommendations']['results'] ?? [],
            'is_array'
        ));

        /** @var list<array<string, mixed>> $similar */
        $similar = array_values(array_filter(
            $details['similar']['results'] ?? [],
            'is_array'
        ));

        usort($similar, function (array $a, array $b): int {
            $dateA = (string) ($a['release_date'] ?? $a['first_air_date'] ?? '');
            $dateB = (string) ($b['release_date'] ?? $b['first_air_date'] ?? '');

            return $dateB <=> $dateA;
        });

        $merged = [];
        $seen = [];

        foreach (array_merge($recommendations, $similar) as $item) {
            $id = $item['id'] ?? null;

            if (! is_numeric($id)) {
                continue;
            }

            $id = (int) $id;

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $merged[] = $item;

            if (count($merged) >= $limit) {
                break;
            }
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public function season(int $tvId, int $seasonNumber): array
    {
        return $this->get("/tv/{$tvId}/season/{$seasonNumber}");
    }

    /**
     * @return array<string, mixed>
     */
    public function person(int $id): array
    {
        return $this->get("/person/{$id}", [
            'append_to_response' => 'combined_credits,external_ids,images',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function popularPeople(int $page = 1): array
    {
        return $this->get('/person/popular', ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query, int $page = 1): array
    {
        return $this->get('/search/multi', [
            'query' => $query,
            'page' => $page,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function genres(string $type = 'movie'): array
    {
        return $this->get("/genre/{$type}/list");
    }

    /**
     * @return array<string, mixed>
     */
    public function upcoming(int $page = 1): array
    {
        return $this->get('/movie/upcoming', ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function nowPlaying(int $page = 1): array
    {
        return $this->get('/movie/now_playing', ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function airingToday(int $page = 1): array
    {
        return $this->get('/tv/airing_today', ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function onTheAir(int $page = 1): array
    {
        return $this->get('/tv/on_the_air', ['page' => $page]);
    }

    /**
     * @return array<string, mixed>
     */
    public function animation(string $type = 'tv', int $page = 1): array
    {
        return $this->discoverByGenre($type, self::ANIMATION_GENRE_ID, $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function discoverByGenre(string $type, int $genreId, int $page = 1): array
    {
        return $this->get("/discover/{$type}", [
            'with_genres' => $genreId,
            'sort_by' => 'popularity.desc',
            'page' => $page,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function images(string $type, int $id): array
    {
        return $this->get("/{$type}/{$id}/images", [
            'include_image_languages' => 'en,null',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function videos(string $type, int $id): array
    {
        return $this->get("/{$type}/{$id}/videos");
    }

    /**
     * @param  array<int, array<string, mixed>>  $videosResults
     */
    public static function extractTrailerKey(array $videosResults): ?string
    {
        $video = collect($videosResults)->first(
            fn (array $v): bool => ($v['site'] ?? '') === 'YouTube' && in_array($v['type'] ?? '', ['Trailer', 'Teaser'])
        );

        return $video['key'] ?? null;
    }

    public function imageUrl(string $path, string $size = 'w500'): string
    {
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return config('tmdb.image_base_url')."/{$size}{$path}";
    }

    public function backdropUrl(string $path, string $size = 'original'): string
    {
        return $this->imageUrl($path, $size);
    }

    private function getTtl(string $endpoint): int
    {
        /** @var array<string, int> $ttls */
        $ttls = config('tmdb.cache_ttl');

        if (str_contains($endpoint, 'trending')) {
            return $ttls['trending'];
        }

        if (str_contains($endpoint, 'popular') || str_contains($endpoint, 'top_rated') || str_contains($endpoint, 'upcoming') || str_contains($endpoint, 'now_playing') || str_contains($endpoint, 'airing_today') || str_contains($endpoint, 'on_the_air') || str_contains($endpoint, 'discover')) {
            return $ttls['popular'];
        }

        if (str_contains($endpoint, 'search')) {
            return $ttls['search'];
        }

        return $ttls['details'];
    }
}
