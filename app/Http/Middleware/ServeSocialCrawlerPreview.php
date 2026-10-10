<?php

namespace App\Http\Middleware;

use App\Services\Tmdb;
use App\Support\SocialMeta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ServeSocialCrawlerPreview
{
    /**
     * Social crawlers only need OG tags. Serving a tiny HTML document avoids
     * Livewire/full-page work so WhatsApp can scrape before it times out —
     * especially after a free-host cold start.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET' || ! SocialMeta::isSocialCrawler($request)) {
            return $next($request);
        }

        $preview = $this->resolvePreview($request);

        if ($preview === null) {
            return $next($request);
        }

        return response()
            ->view('social.crawler-preview', $preview)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * @return array{
     *     title: string,
     *     ogTitle: string,
     *     ogDescription: string,
     *     ogImage: ?string,
     *     ogImageAlt: string,
     *     ogUrl: string,
     *     ogType: string
     * }|null
     */
    private function resolvePreview(Request $request): ?array
    {
        $path = '/'.ltrim($request->path(), '/');

        if (preg_match('#^/movies/(\d+)$#', $path, $matches) === 1) {
            return $this->payloadFor('movie', (int) $matches[1], route('movies.detail', (int) $matches[1]));
        }

        if (preg_match('#^/tv/(\d+)$#', $path, $matches) === 1) {
            return $this->payloadFor('tv', (int) $matches[1], route('tv.detail', (int) $matches[1]));
        }

        if (preg_match('#^/watch/(movie|tv)/(\d+)(?:/(\d+)/(\d+))?$#', $path, $matches) === 1) {
            $type = $matches[1];
            $tmdbId = (int) $matches[2];
            $canonical = $type === 'tv' && isset($matches[3], $matches[4])
                ? route('watch', ['type' => 'tv', 'tmdbId' => $tmdbId, 'season' => (int) $matches[3], 'episode' => (int) $matches[4]])
                : route('watch', ['type' => $type, 'tmdbId' => $tmdbId]);

            return $this->payloadFor($type, $tmdbId, $canonical);
        }

        return null;
    }

    /**
     * @return array{
     *     title: string,
     *     ogTitle: string,
     *     ogDescription: string,
     *     ogImage: ?string,
     *     ogImageAlt: string,
     *     ogUrl: string,
     *     ogType: string
     * }|null
     */
    private function payloadFor(string $mediaType, int $tmdbId, string $canonicalUrl): ?array
    {
        try {
            $media = app(Tmdb::class)->details($mediaType, $tmdbId);
        } catch (Throwable) {
            return null;
        }

        if (empty($media['id']) && empty($media['title']) && empty($media['name'])) {
            return null;
        }

        return SocialMeta::payload($media, $mediaType, $canonicalUrl);
    }
}
