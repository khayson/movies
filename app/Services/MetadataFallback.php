<?php

namespace App\Services;

class MetadataFallback
{
    public function __construct(private TvMaze $tvMaze) {}

    /**
     * TMDB-shaped payload from backup catalogs when TMDB hosts are down.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    public function forEndpoint(string $endpoint, array $params = []): ?array
    {
        if (preg_match('#^/tv/(\d+)$#', $endpoint, $matches) === 1) {
            return $this->tvMaze->showByTmdbId((int) $matches[1]);
        }

        if (preg_match('#^/trending/(tv|all)/#', $endpoint) === 1
            || preg_match('#^/tv/(popular|top_rated)$#', $endpoint) === 1) {
            return ['results' => $this->tvMaze->catalog(24)];
        }

        if (str_contains($endpoint, 'airing_today') || str_contains($endpoint, 'on_the_air')) {
            return ['results' => $this->tvMaze->schedule(24)];
        }

        if ($endpoint === '/search/multi') {
            $query = $params['query'] ?? '';

            return ['results' => $this->tvMaze->search(is_string($query) ? $query : '', 20)];
        }

        return null;
    }
}
