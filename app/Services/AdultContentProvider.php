<?php

namespace App\Services;

use App\Support\AdultSafety;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdultContentProvider
{
    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function xnxx(string $query = '', int $page = 1, string $mode = 'trending', string $category = ''): array
    {
        if (AdultSafety::isBlockedQuery($query) || AdultSafety::isBlockedQuery($category)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.xnxx.{$mode}.{$page}.".md5($query.$category);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $page, $mode, $category): array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.xnxx', 'porn-xnxx-api.p.rapidapi.com');

            if (! $apiKey) {
                return $this->emptyCatalog();
            }

            $http = Http::timeout(15)
                ->withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => $host,
                ]);

            try {
                $response = match ($mode) {
                    'search' => $http->withHeaders(['Content-Type' => 'application/json'])
                        ->post("https://{$host}/search", ['q' => $query, 'page' => $page])
                        ->json(),
                    'category' => $http->withHeaders(['Content-Type' => 'application/json'])
                        ->post("https://{$host}/category", ['slug' => $category, 'page' => $page])
                        ->json(),
                    default => $http->get("https://{$host}/trending", ['page' => $page])
                        ->json(),
                };

                $videos = collect($response['results'] ?? [])
                    ->map(fn (array $video): array => [
                        'id' => $video['video_link'] ?? '',
                        'title' => $video['title'] ?? 'Untitled',
                        'thumbnail' => $video['thumbnail'] ?? '',
                        'duration' => $video['duration'] ?? '',
                        'views' => $video['views'] ?? '',
                        'rating' => '',
                        'embed_url' => '',
                        'video_link' => $video['video_link'] ?? '',
                        'provider' => 'XNXX',
                    ])
                    ->all();

                $count = (int) ($response['count'] ?? count($videos));
                $totalPages = $count >= 36 ? $page + 1 : $page;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });
    }

    /**
     * @return array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null
     */
    public function xnxxDownload(string $videoLink): ?array
    {
        $cacheKey = 'adult.xnxx.dl.'.md5($videoLink);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($videoLink): ?array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.xnxx', 'porn-xnxx-api.p.rapidapi.com');

            if (! $apiKey) {
                return null;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/download", ['video_link' => $videoLink])
                    ->json();

                if (empty($response['video_high']) && empty($response['hls'])) {
                    return null;
                }

                return [
                    'title' => $response['title'] ?? 'Untitled',
                    'video_low' => $response['video_low'] ?? '',
                    'video_high' => $response['video_high'] ?? '',
                    'hls' => $response['hls'] ?? '',
                    'thumbnail' => $response['thumbnail'] ?? '',
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array<int, array{name: string, slug: string}>
     */
    public function xnxxCategories(string $letter = 'a'): array
    {
        $cacheKey = "adult.xnxx.cats.{$letter}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($letter): array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.xnxx', 'porn-xnxx-api.p.rapidapi.com');

            if (! $apiKey) {
                return [];
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                    ])
                    ->get("https://{$host}/categories", ['letter' => $letter])
                    ->json();

                return collect($response['categories'] ?? [])
                    ->reject(fn (array $category): bool => AdultSafety::isBlockedQuery((string) ($category['name'] ?? $category['slug'] ?? '')))
                    ->values()
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function pornhub(string $query = '', int $page = 1, string $mode = 'trending'): array
    {
        if (AdultSafety::isBlockedQuery($query)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.pornhub.{$mode}.{$page}.".md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $page, $mode): array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.pornhub', 'pornhub-api-xnxx.p.rapidapi.com');

            if (! $apiKey) {
                return $this->emptyCatalog();
            }

            $http = Http::timeout(15)
                ->withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => $host,
                    'Content-Type' => 'application/json',
                ]);

            try {
                $response = match ($mode) {
                    'search' => $http->post("https://{$host}/api/search", ['q' => $query, 'page' => $page])->json(),
                    default => $http->get("https://{$host}/api/trending", ['page' => $page])->json(),
                };

                $videos = collect($response['results'] ?? [])
                    ->map(fn (array $video): array => [
                        'id' => $video['video_link'] ?? '',
                        'title' => $video['title'] ?? 'Untitled',
                        'thumbnail' => $video['thumbnail'] ?? '',
                        'duration' => $video['duration'] ?? '',
                        'views' => $video['views'] ?? '',
                        'rating' => '',
                        'embed_url' => '',
                        'video_link' => $video['video_link'] ?? '',
                        'provider' => 'PornHub',
                    ])
                    ->all();

                $count = (int) ($response['count'] ?? count($videos));
                $totalPages = $count >= 30 ? $page + 1 : $page;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });
    }

    /**
     * @return array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null
     */
    public function pornhubDownload(string $videoLink): ?array
    {
        $cacheKey = 'adult.pornhub.dl.'.md5($videoLink);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($videoLink): ?array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.pornhub', 'pornhub-api-xnxx.p.rapidapi.com');

            if (! $apiKey) {
                return null;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/api/download", ['video_link' => $videoLink])
                    ->json();

                if (empty($response['video_high']) && empty($response['hls'])) {
                    return null;
                }

                return [
                    'title' => $response['title'] ?? 'Untitled',
                    'video_low' => $response['video_low'] ?? '',
                    'video_high' => $response['video_high'] ?? '',
                    'hls' => $response['hls'] ?? '',
                    'thumbnail' => $response['thumbnail'] ?? '',
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function xvideos(string $query = '', int $page = 1): array
    {
        if (AdultSafety::isBlockedQuery($query)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.xvideos.{$page}.".md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $page): array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.xvideos', 'xvideos-com-api.p.rapidapi.com');

            if (! $apiKey || $query === '') {
                return $this->emptyCatalog();
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/search_video", ['query' => $query, 'page' => $page])
                    ->json();

                $videos = collect($response['results'] ?? $response['videos'] ?? [])
                    ->map(fn (array $video): array => [
                        'id' => $video['video_link'] ?? ($video['url'] ?? ''),
                        'title' => $video['title'] ?? 'Untitled',
                        'thumbnail' => $video['thumbnail'] ?? ($video['thumb'] ?? ''),
                        'duration' => $video['duration'] ?? '',
                        'views' => $video['views'] ?? '',
                        'rating' => '',
                        'embed_url' => '',
                        'video_link' => $video['video_link'] ?? ($video['url'] ?? ''),
                        'provider' => 'XVideos',
                    ])
                    ->all();

                $count = (int) ($response['count'] ?? count($videos));
                $totalPages = $count >= 20 ? $page + 1 : $page;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });
    }

    /**
     * @return array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null
     */
    public function xvideosDownload(string $videoLink): ?array
    {
        $cacheKey = 'adult.xvideos.dl.'.md5($videoLink);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($videoLink): ?array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.xvideos', 'xvideos-com-api.p.rapidapi.com');

            if (! $apiKey) {
                return null;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/download_video", ['lien' => $videoLink])
                    ->json();

                if (empty($response['video_high']) && empty($response['hls']) && empty($response['video_low'])) {
                    return null;
                }

                return [
                    'title' => $response['title'] ?? 'Untitled',
                    'video_low' => $response['video_low'] ?? '',
                    'video_high' => $response['video_high'] ?? '',
                    'hls' => $response['hls'] ?? '',
                    'thumbnail' => $response['thumbnail'] ?? '',
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function eporner(string $query = '', int $page = 1, string $order = 'top-weekly'): array
    {
        if (AdultSafety::isBlockedQuery($query)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.eporner.{$order}.{$page}.".md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $page, $order): array {
            $params = [
                'per_page' => 24,
                'page' => $page,
                'thumbsize' => 'big',
                'order' => $order,
                'format' => 'json',
            ];

            if ($query !== '') {
                $params['query'] = $query;
            }

            try {
                $response = Http::timeout(10)
                    ->get('https://www.eporner.com/api/v2/video/search/', $params)
                    ->json();

                $videos = collect($response['videos'] ?? [])
                    ->map(fn (array $video): array => [
                        'id' => $video['id'] ?? '',
                        'title' => $video['title'] ?? 'Untitled',
                        'thumbnail' => $video['default_thumb']['src'] ?? ($video['thumbs'][0]['src'] ?? ''),
                        'duration' => $video['length_min'] ?? '',
                        'views' => $this->formatNumber((int) ($video['views'] ?? 0)),
                        'rating' => number_format((float) ($video['rate'] ?? 0), 1),
                        'embed_url' => "https://www.eporner.com/embed/{$video['id']}/",
                        'provider' => 'Eporner',
                    ])
                    ->all();

                $totalCount = (int) ($response['total_count'] ?? 0);
                $totalPages = $totalCount > 0 ? (int) ceil($totalCount / 24) : 1;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function redtube(string $query = '', int $page = 1, string $order = 'mostviewed'): array
    {
        if (AdultSafety::isBlockedQuery($query)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.redtube.{$order}.{$page}.".md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($query, $page, $order): array {
            $params = [
                'data' => 'redtube.Videos.searchVideos',
                'output' => 'json',
                'page' => $page,
                'thumbsize' => 'big',
                'ordering' => $order,
            ];

            if ($query !== '') {
                $params['search'] = $query;
            }

            try {
                $response = Http::timeout(10)
                    ->get('https://api.redtube.com/', $params)
                    ->json();

                $videos = collect($response['videos'] ?? [])
                    ->map(function (array $wrapper): array {
                        $video = $wrapper['video'] ?? $wrapper;

                        return [
                            'id' => (string) ($video['video_id'] ?? ''),
                            'title' => $video['title'] ?? 'Untitled',
                            'thumbnail' => $video['default_thumb'] ?? ($video['thumb'] ?? ''),
                            'duration' => $video['duration'] ?? '',
                            'views' => $this->formatNumber((int) str_replace(',', '', (string) ($video['views'] ?? '0'))),
                            'rating' => number_format((float) ($video['rating'] ?? 0), 1),
                            'embed_url' => "https://embed.redtube.com/?id={$video['video_id']}",
                            'provider' => 'RedTube',
                        ];
                    })
                    ->all();

                $totalCount = (int) ($response['count'] ?? 0);
                $totalPages = $totalCount > 0 ? (int) ceil($totalCount / 20) : 1;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });
    }

    /**
     * @return array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null
     */
    public function epornerDownload(string $videoId): ?array
    {
        $videoUrl = "https://www.eporner.com/video-{$videoId}/";
        $cacheKey = 'adult.eporner.dl.'.md5($videoUrl);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($videoUrl): ?array {
            $apiKey = config('sources.rapidapi_key');
            $host = config('sources.rapidapi_hosts.eporner', 'eporner-com-api-v2-xnxx.p.rapidapi.com');

            if (! $apiKey) {
                return null;
            }

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/download_video", ['url' => $videoUrl])
                    ->json();

                if (empty($response['video_high']) && empty($response['hls']) && empty($response['video_low'])) {
                    return null;
                }

                return [
                    'title' => $response['title'] ?? 'Untitled',
                    'video_low' => $response['video_low'] ?? '',
                    'video_high' => $response['video_high'] ?? '',
                    'hls' => $response['hls'] ?? '',
                    'thumbnail' => $response['thumbnail'] ?? '',
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    private function emptyCatalog(): array
    {
        return ['videos' => [], 'total_pages' => 1];
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    private function catalog(array $videos, int $totalPages): array
    {
        return [
            'videos' => AdultSafety::rejectBlockedTitles($videos),
            'total_pages' => min($totalPages, 500),
        ];
    }

    private function formatNumber(int $number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1).'M';
        }

        if ($number >= 1000) {
            return number_format($number / 1000, 1).'K';
        }

        return (string) $number;
    }
}
