<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Tmdb
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        $params = $this->applyUserPreferences($params);
        $cacheKey = 'tmdb.'.md5($endpoint.serialize($params));
        $ttl = $this->getTtl($endpoint);

        /** @var array<string, mixed> */
        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($endpoint, $params): array {
            $response = Http::baseUrl(config('tmdb.base_url'))
                ->withToken(config('tmdb.api_key'))
                ->get($endpoint, $params)
                ->throw();

            /** @var array<string, mixed> */
            return $response->json();
        });
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
            'append_to_response' => 'credits,videos,similar,recommendations',
        ]);
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
    public function discoverByGenre(string $type, int $genreId, int $page = 1): array
    {
        return $this->get("/discover/{$type}", [
            'with_genres' => $genreId,
            'sort_by' => 'popularity.desc',
            'page' => $page,
        ]);
    }

    public function imageUrl(string $path, string $size = 'w500'): string
    {
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
