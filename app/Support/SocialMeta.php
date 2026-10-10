<?php

namespace App\Support;

use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SocialMeta
{
    /**
     * Publish Open Graph / Twitter card data for a movie or TV title.
     *
     * WhatsApp and other crawlers need absolute HTTPS images that are not
     * multi‑megabyte originals — prefer w1280 backdrops (≈16:9) under ~600KB.
     *
     * @param  array<string, mixed>  $media
     */
    public static function forMedia(array $media, string $mediaType, ?string $canonicalUrl = null): void
    {
        $tmdb = app(Tmdb::class);

        $name = (string) ($media['title'] ?? $media['name'] ?? 'Untitled');
        $year = Str::substr((string) ($media['release_date'] ?? $media['first_air_date'] ?? ''), 0, 4);
        $displayTitle = $year !== '' ? "{$name} ({$year})" : $name;

        $overview = trim((string) ($media['overview'] ?? ''));
        $description = $overview !== ''
            ? Str::limit($overview, 160)
            : "Watch {$displayTitle} on ".config('app.name').'.';

        $image = null;
        if (! empty($media['backdrop_path'])) {
            $image = $tmdb->backdropUrl((string) $media['backdrop_path'], 'w1280');
        } elseif (! empty($media['poster_path'])) {
            $image = $tmdb->imageUrl((string) $media['poster_path'], 'w780');
        }

        $isTv = $mediaType === 'tv';

        View::share([
            'title' => $displayTitle,
            'ogTitle' => $displayTitle,
            'ogDescription' => $description,
            'ogImage' => $image,
            'ogImageAlt' => $displayTitle,
            'ogUrl' => $canonicalUrl ?? url()->current(),
            'ogType' => $isTv ? 'video.tv_show' : 'video.movie',
        ]);
    }

    /**
     * Build a share-friendly message (title + short pitch). Platforms that
     * scrape the URL still pick up the rich OG card from the page itself.
     *
     * @param  array<string, mixed>  $media
     */
    public static function shareText(array $media, string $mediaType = 'movie'): string
    {
        $name = (string) ($media['title'] ?? $media['name'] ?? 'this title');
        $year = Str::substr((string) ($media['release_date'] ?? $media['first_air_date'] ?? ''), 0, 4);
        $label = $year !== '' ? "{$name} ({$year})" : $name;
        $kind = $mediaType === 'tv' ? 'TV show' : 'movie';

        return "Watch {$label} — stream this {$kind} on ".config('app.name');
    }

    /**
     * Absolute image URL suitable for in-app share preview thumbnails.
     *
     * @param  array<string, mixed>  $media
     */
    public static function shareImage(array $media): ?string
    {
        $tmdb = app(Tmdb::class);

        if (! empty($media['backdrop_path'])) {
            return $tmdb->backdropUrl((string) $media['backdrop_path'], 'w780');
        }

        if (! empty($media['poster_path'])) {
            return $tmdb->imageUrl((string) $media['poster_path'], 'w342');
        }

        return null;
    }
}
