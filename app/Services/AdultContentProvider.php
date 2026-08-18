<?php

namespace App\Services;

use App\Support\AdultSafety;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdultContentProvider
{
    private const BROWSE_FALLBACK_QUERY = 'amateur';

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function xnxx(string $query = '', int $page = 1, string $mode = 'trending', string $category = ''): array
    {
        if (AdultSafety::isBlockedQuery($query) || AdultSafety::isBlockedQuery($category)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.xnxx.{$mode}.{$page}.".md5($query.$category);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['videos'] ?? []) !== []) {
            return $cached;
        }

        $apiKey = config('sources.rapidapi_key');
        $host = config('sources.rapidapi_hosts.xnxx', 'porn-xnxx-api.p.rapidapi.com');

        if (! $apiKey) {
            return $this->emptyCatalog();
        }

        try {
            $http = Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->withHeaders([
                    'X-RapidAPI-Key' => $apiKey,
                    'X-RapidAPI-Host' => $host,
                ]);

            $response = match ($mode) {
                'search' => $http->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://{$host}/search", ['q' => $query, 'page' => $page]),
                'category' => $http->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://{$host}/category", ['slug' => $category, 'page' => $page]),
                default => $http->get("https://{$host}/trending", ['page' => $page]),
            };

            $payload = $response->successful() ? $response->json() : null;
            $videos = $this->mapXnxxVideos($payload);
            $count = is_array($payload) ? (int) ($payload['count'] ?? count($videos)) : count($videos);
            $totalPages = $count >= 36 ? $page + 1 : $page;
            $catalog = $this->catalog($videos, $totalPages);

            if ($catalog['videos'] === [] && $mode === 'trending') {
                $fallback = $this->xnxx(page: $page, mode: 'category', category: 'amateur');

                if ($fallback['videos'] !== []) {
                    Cache::put($cacheKey, $fallback, now()->addMinutes(15));
                }

                return $fallback;
            }

            if ($catalog['videos'] !== []) {
                Cache::put($cacheKey, $catalog, now()->addMinutes(15));
            }

            return $catalog;
        } catch (\Throwable) {
            if ($mode === 'trending') {
                return $this->xnxx(page: $page, mode: 'category', category: 'amateur');
            }

            return $this->emptyCatalog();
        }
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
        $catalog = $this->rememberNonEmpty($cacheKey, 15, function () use ($query, $page, $mode): array {
            $host = config('sources.rapidapi_hosts.pornhub', 'pornhub-api-xnxx.p.rapidapi.com');
            $http = $this->rapidClient($host);

            if (! $http) {
                return $this->emptyCatalog();
            }

            try {
                $response = match ($mode) {
                    'search' => $http->asJson()->post("https://{$host}/api/search", [
                        'q' => $query,
                        'pages' => $page,
                        'page' => $page,
                    ]),
                    default => $http->get("https://{$host}/api/trending", ['page' => $page]),
                };

                $payload = $response->successful() ? $response->json() : null;
                $videos = $this->mapRapidVideos($payload, 'PornHub');
                $count = is_array($payload) && ! array_is_list($payload)
                    ? (int) ($payload['count'] ?? count($videos))
                    : count($videos);

                return $this->catalog($videos, $count >= 20 ? $page + 1 : $page);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });

        if ($catalog['videos'] === [] && $mode === 'trending') {
            $fallback = $this->pornhub(query: self::BROWSE_FALLBACK_QUERY, page: $page, mode: 'search');

            if ($fallback['videos'] !== []) {
                Cache::put($cacheKey, $fallback, now()->addMinutes(15));
            }

            return $fallback;
        }

        return $catalog;
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
                $http = $this->rapidClient($host);

                if (! $http) {
                    return null;
                }

                $response = $http
                    ->asJson()
                    ->post("https://{$host}/api/download", ['url' => $videoLink]);

                $payload = $response->successful() ? $response->json() : null;
                $mapped = $this->mapPornhubDownload($payload);

                if ($mapped !== null) {
                    return $mapped;
                }

                $legacy = Http::timeout(15)
                    ->connectTimeout(5)
                    ->acceptJson()
                    ->withHeaders([
                        'X-RapidAPI-Key' => $apiKey,
                        'X-RapidAPI-Host' => $host,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("https://{$host}/api/download", ['video_link' => $videoLink])
                    ->json();

                if (empty($legacy['video_high']) && empty($legacy['hls'])) {
                    return null;
                }

                return [
                    'title' => $legacy['title'] ?? 'Untitled',
                    'video_low' => $legacy['video_low'] ?? '',
                    'video_high' => $legacy['video_high'] ?? '',
                    'hls' => $legacy['hls'] ?? '',
                    'thumbnail' => $legacy['thumbnail'] ?? '',
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
        $browseQuery = $query !== '' ? $query : self::BROWSE_FALLBACK_QUERY;

        if (AdultSafety::isBlockedQuery($browseQuery)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.xvideos.{$page}.".md5($browseQuery);

        return $this->rememberNonEmpty($cacheKey, 15, function () use ($browseQuery, $page): array {
            $host = config('sources.rapidapi_hosts.xvideos', 'xvideos-com-api.p.rapidapi.com');
            $http = $this->rapidClient($host);

            if (! $http) {
                return $this->emptyCatalog();
            }

            try {
                $response = $http->asJson()->post("https://{$host}/search_video", [
                    'keyword' => $browseQuery,
                    'page' => $page,
                ]);

                $payload = $response->successful() ? $response->json() : null;
                $videos = $this->mapRapidVideos($payload, 'XVideos');
                $count = count($videos);
                $total = is_array($payload) && ! array_is_list($payload)
                    ? (int) ($payload['total'] ?? $payload['count'] ?? $count)
                    : $count;
                $perPage = max($count, 1);
                $totalPages = $total > $perPage
                    ? (int) ceil($total / $perPage)
                    : ($count >= 20 ? $page + 1 : $page);

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

        $allowedOrders = ['top-weekly', 'top-monthly', 'latest', 'longest'];
        $order = in_array($order, $allowedOrders, true) ? $order : 'top-weekly';
        $cacheKey = "adult.eporner.{$order}.{$page}.".md5($query);

        $catalog = $this->rememberNonEmpty($cacheKey, 30, function () use ($query, $page, $order): array {
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
                    ->connectTimeout(5)
                    ->acceptJson()
                    ->get('https://www.eporner.com/api/v2/video/search/', $params);

                $payload = $response->successful() ? $response->json() : null;
                $videos = $this->mapEpornerVideos($payload);
                $totalCount = is_array($payload) ? (int) ($payload['total_count'] ?? count($videos)) : count($videos);
                $totalPages = $totalCount > 0 ? (int) ceil($totalCount / 24) : 1;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });

        if ($catalog['videos'] === [] && $order !== 'latest') {
            $fallback = $this->eporner($query, $page, 'latest');

            if ($fallback['videos'] !== []) {
                Cache::put($cacheKey, $fallback, now()->addMinutes(30));
            }

            return $fallback;
        }

        return $catalog;
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function redtube(string $query = '', int $page = 1, string $order = 'mostviewed'): array
    {
        if (AdultSafety::isBlockedQuery($query)) {
            return $this->emptyCatalog();
        }

        $allowedOrders = ['mostviewed', 'rating', 'newest'];
        $order = in_array($order, $allowedOrders, true) ? $order : 'mostviewed';
        $cacheKey = "adult.redtube.{$order}.{$page}.".md5($query);

        $catalog = $this->rememberNonEmpty($cacheKey, 30, function () use ($query, $page, $order): array {
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
                    ->connectTimeout(5)
                    ->acceptJson()
                    ->get('https://api.redtube.com/', $params);

                $payload = $response->successful() ? $response->json() : null;
                $videos = $this->mapRedtubeVideos($payload);
                $totalCount = is_array($payload) ? (int) ($payload['count'] ?? count($videos)) : count($videos);
                $totalPages = $totalCount > 0 ? (int) ceil($totalCount / 20) : 1;

                return $this->catalog($videos, $totalPages);
            } catch (\Throwable) {
                return $this->emptyCatalog();
            }
        });

        if ($catalog['videos'] === [] && $order !== 'newest') {
            $fallback = $this->redtube($query, $page, 'newest');

            if ($fallback['videos'] !== []) {
                Cache::put($cacheKey, $fallback, now()->addMinutes(30));
            }

            return $fallback;
        }

        return $catalog;
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
     * @param  Closure(): array{videos: array<int, array<string, mixed>>, total_pages: int}  $callback
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    private function rememberNonEmpty(string $cacheKey, int $minutes, Closure $callback): array
    {
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['videos'] ?? []) !== []) {
            return $cached;
        }

        $catalog = $callback();

        if (($catalog['videos'] ?? []) !== []) {
            Cache::put($cacheKey, $catalog, now()->addMinutes($minutes));
        }

        return $catalog;
    }

    private function rapidClient(string $host): ?PendingRequest
    {
        $apiKey = config('sources.rapidapi_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        return Http::timeout(15)
            ->connectTimeout(5)
            ->acceptJson()
            ->withHeaders([
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => $host,
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if ($response !== [] && array_is_list($response)) {
            $rows = $response;
        } else {
            $rows = $response['results'] ?? $response['videos'] ?? $response['data'] ?? [];
        }

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapRapidVideos(mixed $response, string $provider): array
    {
        return collect($this->extractRows($response))
            ->map(fn (array $video): array => [
                'id' => $this->firstPresent($video, ['video_link', 'url', 'lien', 'link']),
                'title' => $this->firstPresent($video, ['title', 'titre', 'name']) ?: 'Untitled',
                'thumbnail' => $this->firstPresent($video, ['thumbnail', 'thumb', 'preview', 'miniature']),
                'duration' => $this->firstPresent($video, ['duration', 'duree']),
                'views' => $this->firstPresent($video, ['views', 'vues']),
                'rating' => '',
                'embed_url' => '',
                'video_link' => $this->firstPresent($video, ['video_link', 'url', 'lien', 'link']),
                'provider' => $provider,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapXnxxVideos(mixed $response): array
    {
        return $this->mapRapidVideos($response, 'XNXX');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapEpornerVideos(mixed $response): array
    {
        return collect($this->extractRows($response))
            ->map(function (array $video): array {
                $thumb = '';
                if (is_string($video['default_thumb'] ?? null)) {
                    $thumb = $video['default_thumb'];
                } elseif (is_array($video['default_thumb'] ?? null)) {
                    $thumb = (string) ($video['default_thumb']['src'] ?? '');
                }
                if ($thumb === '' && is_array($video['thumbs'][0] ?? null)) {
                    $thumb = (string) ($video['thumbs'][0]['src'] ?? '');
                }

                return [
                    'id' => $video['id'] ?? '',
                    'title' => $video['title'] ?? 'Untitled',
                    'thumbnail' => $thumb,
                    'duration' => $video['length_min'] ?? '',
                    'views' => $this->formatNumber((int) ($video['views'] ?? 0)),
                    'rating' => number_format((float) ($video['rate'] ?? 0), 1),
                    'embed_url' => "https://www.eporner.com/embed/{$video['id']}/",
                    'provider' => 'Eporner',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapRedtubeVideos(mixed $response): array
    {
        return collect($this->extractRows($response))
            ->map(function (array $wrapper): array {
                $video = is_array($wrapper['video'] ?? null) ? $wrapper['video'] : $wrapper;

                return [
                    'id' => (string) ($video['video_id'] ?? ''),
                    'title' => $video['title'] ?? 'Untitled',
                    'thumbnail' => $video['default_thumb'] ?? ($video['thumb'] ?? ''),
                    'duration' => $video['duration'] ?? '',
                    'views' => $this->formatNumber((int) str_replace(',', '', (string) ($video['views'] ?? '0'))),
                    'rating' => number_format((float) ($video['rating'] ?? 0), 1),
                    'embed_url' => 'https://embed.redtube.com/?id='.($video['video_id'] ?? ''),
                    'provider' => 'RedTube',
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     */
    private function firstPresent(array $values, array $keys): string
    {
        foreach ($keys as $key) {
            if (is_string($values[$key] ?? null) && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return '';
    }

    /**
     * @return array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null
     */
    private function mapPornhubDownload(mixed $payload): ?array
    {
        if (is_array($payload) && (! empty($payload['video_high']) || ! empty($payload['hls']))) {
            return [
                'title' => $payload['title'] ?? 'Untitled',
                'video_low' => $payload['video_low'] ?? '',
                'video_high' => $payload['video_high'] ?? '',
                'hls' => $payload['hls'] ?? '',
                'thumbnail' => $payload['thumbnail'] ?? '',
            ];
        }

        $formats = $this->extractRows($payload);
        $hls = '';
        $high = '';
        $low = '';

        foreach ($formats as $format) {
            $url = (string) ($format['url'] ?? '');
            $id = strtolower((string) ($format['id'] ?? ''));

            if ($url === '') {
                continue;
            }

            if (str_contains($id, 'hls') || str_contains($url, '.m3u8')) {
                $hls = $hls !== '' ? $hls : $url;

                continue;
            }

            if (str_contains($id, '240') || str_contains($id, '360')) {
                $low = $low !== '' ? $low : $url;
                $high = $high !== '' ? $high : $url;

                continue;
            }

            $high = $url;
        }

        if ($hls === '' && $high === '' && $low === '') {
            return null;
        }

        return [
            'title' => 'Untitled',
            'video_low' => $low,
            'video_high' => $high !== '' ? $high : $low,
            'hls' => $hls,
            'thumbnail' => '',
        ];
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
