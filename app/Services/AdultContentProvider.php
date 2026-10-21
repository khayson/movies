<?php

namespace App\Services;

use App\Support\AdultSafety;
use App\Support\ApiPayload;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdultContentProvider
{
    private const BROWSE_FALLBACK_QUERY = 'amateur';

    /**
     * Tube providers that have a dedicated watch page, keyed by slug.
     *
     * @var array<string, string>
     */
    public const TUBE_PROVIDERS = [
        'xnxx' => 'XNXX',
        'pornhub' => 'PornHub',
        'xvideos' => 'XVideos',
        'eporner' => 'Eporner',
        'redtube' => 'RedTube',
    ];

    /**
     * Sites whose video page links are passed to their download API.
     *
     * @var array<string, string>
     */
    private const LINKED_VIDEO_DOMAINS = [
        'xnxx' => 'xnxx.com',
        'pornhub' => 'pornhub.com',
        'xvideos' => 'xvideos.com',
    ];

    /**
     * The value that identifies a catalog video on its provider: the page link for
     * linked providers, the numeric/alphanumeric id for Eporner and RedTube.
     *
     * @param  array<string, mixed>  $video
     */
    public static function watchReference(string $provider, array $video): string
    {
        $value = isset(self::LINKED_VIDEO_DOMAINS[$provider])
            ? ($video['video_link'] ?? '')
            : ($video['id'] ?? '');

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * URL-safe route key for a video: its reference plus the catalog title, which the
     * watch page shows while (and when) the provider has no title of its own.
     */
    public static function encodeWatchKey(string $reference, string $title = ''): string
    {
        $payload = $title === '' ? $reference : $reference."\n".Str::limit($title, 200, '');

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * @return array{reference: string, title: string}|null
     */
    public static function decodeWatchKey(string $key): ?array
    {
        if ($key === '' || strlen($key) > 2048 || preg_match('/^[A-Za-z0-9_-]+$/', $key) !== 1) {
            return null;
        }

        $padded = str_pad(strtr($key, '-_', '+/'), (int) (ceil(strlen($key) / 4) * 4), '=');
        $decoded = base64_decode($padded, true);

        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        [$reference, $title] = array_pad(explode("\n", $decoded, 2), 2, '');

        return $reference === '' ? null : ['reference' => $reference, 'title' => trim($title)];
    }

    /**
     * Route key for a catalog video, or null when the video cannot be identified.
     *
     * @param  array<string, mixed>  $video
     */
    public static function watchKey(string $provider, array $video): ?string
    {
        $reference = self::watchReference($provider, $video);

        return $reference === '' ? null : self::encodeWatchKey($reference, (string) ($video['title'] ?? ''));
    }

    /**
     * Whether the reference really identifies a video on that provider: a link on the
     * provider's own domain, or a well-formed Eporner/RedTube id.
     */
    public static function isValidReference(string $provider, string $reference): bool
    {
        if (isset(self::LINKED_VIDEO_DOMAINS[$provider])) {
            $domain = self::LINKED_VIDEO_DOMAINS[$provider];
            $scheme = strtolower((string) parse_url($reference, PHP_URL_SCHEME));
            $host = strtolower((string) parse_url($reference, PHP_URL_HOST));

            return in_array($scheme, ['http', 'https'], true)
                && ($host === $domain || str_ends_with($host, '.'.$domain));
        }

        return match ($provider) {
            'eporner' => preg_match('/^[A-Za-z0-9]+$/', $reference) === 1,
            'redtube' => preg_match('/^\d+$/', $reference) === 1,
            default => false,
        };
    }

    /**
     * Resolve one tube video into display metadata and every playable source.
     *
     * Returns null when the reference does not belong to the provider or the title is blocked.
     *
     * @return array{provider: string, title: string, thumbnail: string, duration: string, views: string, rating: string, sources: list<array{label: string, type: string, url: string}>}|null
     */
    public function video(string $provider, string $reference, string $title = ''): ?array
    {
        if (! self::isValidReference($provider, $reference)) {
            return null;
        }

        $video = match ($provider) {
            'eporner' => $this->epornerVideo($reference),
            'redtube' => $this->redtubeVideo($reference),
            default => $this->linkedVideo($provider, $reference, $title),
        };

        if ($title !== '' && in_array($provider, ['eporner', 'redtube'], true) && $video['title'] === self::TUBE_PROVIDERS[$provider].' video') {
            $video['title'] = $title;
        }

        return AdultSafety::rejectBlockedTitles([$video]) === [] ? null : $video;
    }

    /**
     * Other videos from the same provider's default feed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function related(string $provider, string $excludeReference, int $limit = 12): array
    {
        $catalog = match ($provider) {
            'xnxx' => $this->xnxx(),
            'pornhub' => $this->pornhub(),
            'xvideos' => $this->xvideos(),
            'eporner' => $this->eporner(),
            'redtube' => $this->redtube(),
            default => $this->emptyCatalog(),
        };

        return collect($catalog['videos'])
            ->filter(function (array $video) use ($provider, $excludeReference): bool {
                $reference = self::watchReference($provider, $video);

                return $reference !== '' && $reference !== $excludeReference;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * PornHub stream links are locked to the IP that requested them, so its videos
     * only use the embedded player and skip the download API entirely.
     *
     * @return array{provider: string, title: string, thumbnail: string, duration: string, views: string, rating: string, sources: list<array{label: string, type: string, url: string}>}
     */
    private function linkedVideo(string $provider, string $link, string $catalogTitle): array
    {
        $stream = match ($provider) {
            'xnxx' => $this->xnxxDownload($link),
            'xvideos' => $this->xvideosDownload($link),
            default => null,
        };

        $streamTitle = (string) ($stream['title'] ?? '');
        $title = match (true) {
            $catalogTitle !== '' => $catalogTitle,
            $streamTitle !== '' && $streamTitle !== 'Untitled' => $streamTitle,
            default => $this->titleFromLink($link),
        };

        return $this->videoDetails(
            provider: $provider,
            title: $title,
            thumbnail: (string) ($stream['thumbnail'] ?? ''),
            stream: $stream,
            embedUrl: $this->linkedEmbedUrl($provider, $link),
        );
    }

    /**
     * @return array{provider: string, title: string, thumbnail: string, duration: string, views: string, rating: string, sources: list<array{label: string, type: string, url: string}>}
     */
    private function epornerVideo(string $id): array
    {
        $details = $this->cachedVideoDetails("adult.eporner.video.{$id}", fn (): array => $this->mapEpornerVideos([
            Http::timeout(10)->connectTimeout(5)->acceptJson()
                ->get('https://www.eporner.com/api/v2/video/id/', ['id' => $id, 'thumbsize' => 'big', 'format' => 'json'])
                ->json(),
        ]));

        return $this->videoDetails(
            provider: 'eporner',
            title: (string) ($details['title'] ?? 'Eporner video'),
            thumbnail: (string) ($details['thumbnail'] ?? ''),
            stream: $this->epornerDownload($id),
            embedUrl: "https://www.eporner.com/embed/{$id}/",
            duration: (string) ($details['duration'] ?? ''),
            views: (string) ($details['views'] ?? ''),
            rating: (string) ($details['rating'] ?? ''),
        );
    }

    /**
     * @return array{provider: string, title: string, thumbnail: string, duration: string, views: string, rating: string, sources: list<array{label: string, type: string, url: string}>}
     */
    private function redtubeVideo(string $id): array
    {
        $details = $this->cachedVideoDetails("adult.redtube.video.{$id}", fn (): array => $this->mapRedtubeVideos([
            Http::timeout(10)->connectTimeout(5)->acceptJson()
                ->get('https://api.redtube.com/', [
                    'data' => 'redtube.Videos.getVideoById',
                    'video_id' => $id,
                    'output' => 'json',
                    'thumbsize' => 'big',
                ])
                ->json(),
        ]));

        return $this->videoDetails(
            provider: 'redtube',
            title: (string) ($details['title'] ?? 'RedTube video'),
            thumbnail: (string) ($details['thumbnail'] ?? ''),
            stream: null,
            embedUrl: "https://embed.redtube.com/?id={$id}",
            duration: (string) ($details['duration'] ?? ''),
            views: (string) ($details['views'] ?? ''),
            rating: (string) ($details['rating'] ?? ''),
        );
    }

    /**
     * Fetch a single video's catalog row, caching successful lookups for an hour.
     *
     * @param  Closure(): array<int, array<string, mixed>>  $fetch
     * @return array<string, mixed>
     */
    private function cachedVideoDetails(string $cacheKey, Closure $fetch): array
    {
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $row = $fetch()[0] ?? [];
        } catch (\Throwable) {
            return [];
        }

        if (($row['title'] ?? 'Untitled') === 'Untitled') {
            return [];
        }

        Cache::put($cacheKey, $row, now()->addHour());

        return $row;
    }

    /**
     * @param  array{title: string, video_low: string, video_high: string, hls: string, thumbnail: string}|null  $stream
     * @return array{provider: string, title: string, thumbnail: string, duration: string, views: string, rating: string, sources: list<array{label: string, type: string, url: string}>}
     */
    private function videoDetails(
        string $provider,
        string $title,
        string $thumbnail,
        ?array $stream,
        ?string $embedUrl,
        string $duration = '',
        string $views = '',
        string $rating = '',
    ): array {
        $sources = [];

        if (! empty($stream['hls'])) {
            $sources[] = ['label' => 'Auto quality', 'type' => 'hls', 'url' => $stream['hls']];
        }

        if (! empty($stream['video_high'])) {
            $sources[] = ['label' => 'HD', 'type' => 'mp4', 'url' => $stream['video_high']];
        }

        if (! empty($stream['video_low']) && $stream['video_low'] !== $stream['video_high']) {
            $sources[] = ['label' => 'SD', 'type' => 'mp4', 'url' => $stream['video_low']];
        }

        if ($embedUrl !== null) {
            $sources[] = ['label' => self::TUBE_PROVIDERS[$provider].' player', 'type' => 'embed', 'url' => $embedUrl];
        }

        return [
            'provider' => $provider,
            'title' => $title,
            'thumbnail' => $thumbnail !== '' ? $thumbnail : (string) ($stream['thumbnail'] ?? ''),
            'duration' => $duration,
            'views' => $views,
            'rating' => $rating !== '' && (float) $rating > 0 ? $rating : '',
            'sources' => $sources,
        ];
    }

    private function linkedEmbedUrl(string $provider, string $link): ?string
    {
        $path = (string) parse_url($link, PHP_URL_PATH);

        if ($provider === 'pornhub') {
            parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
            $viewKey = $query['viewkey'] ?? null;

            return is_string($viewKey) && preg_match('/^[A-Za-z0-9]+$/', $viewKey) === 1
                ? "https://www.pornhub.com/embed/{$viewKey}"
                : null;
        }

        $pattern = $provider === 'xnxx' ? '~/video-([A-Za-z0-9]+)~' : '~/video\.?([A-Za-z0-9]+)~';

        if (preg_match($pattern, $path, $matches) !== 1) {
            return null;
        }

        return "https://www.{$provider}.com/embedframe/{$matches[1]}";
    }

    private function titleFromLink(string $link): string
    {
        $slug = basename((string) parse_url($link, PHP_URL_PATH));
        $title = trim((string) preg_replace('/[\s_-]+/', ' ', $slug));

        return str_contains($title, ' ') ? ucfirst($title) : 'Untitled video';
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}
     */
    public function xnxx(string $query = '', int $page = 1, string $mode = 'trending', string $category = ''): array
    {
        if (AdultSafety::isBlockedQuery($query) || AdultSafety::isBlockedQuery($category)) {
            return $this->emptyCatalog();
        }

        $cacheKey = "adult.xnxx.{$mode}.{$page}.".md5($query.$category);
        $cached = $this->cachedCatalog($cacheKey);

        if ($cached !== null) {
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
        $cached = $this->cachedCatalog($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $catalog = $callback();

        if ($catalog['videos'] !== []) {
            Cache::put($cacheKey, $catalog, now()->addMinutes($minutes));
        }

        return $catalog;
    }

    /**
     * A previously cached, non-empty catalog, or null when there is none.
     *
     * @return array{videos: array<int, array<string, mixed>>, total_pages: int}|null
     */
    private function cachedCatalog(string $cacheKey): ?array
    {
        $cached = Cache::get($cacheKey);
        $videos = ApiPayload::rows(is_array($cached) ? ($cached['videos'] ?? null) : null);

        if ($videos === []) {
            return null;
        }

        return [
            'videos' => array_map(fn (array $video): array => array_filter($video, is_string(...), ARRAY_FILTER_USE_KEY), $videos),
            'total_pages' => max(1, (int) ($cached['total_pages'] ?? 1)),
        ];
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
