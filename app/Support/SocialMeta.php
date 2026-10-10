<?php

namespace App\Support;

use App\Services\Tmdb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SocialMeta
{
    /**
     * User-Agents used by WhatsApp, Facebook, X, Telegram, Slack, LinkedIn, Discord, iMessage.
     */
    private const CRAWLER_PATTERN = '/WhatsApp|facebookexternalhit|Facebot|Twitterbot|TelegramBot|Slackbot|LinkedInBot|Discordbot|SkypeUriPreview|Iframely|Embedly|Pinterest|vkShare|Googlebot|bingbot|Applebot/i';

    public static function isSocialCrawler(?Request $request = null): bool
    {
        $request ??= request();
        $agent = (string) $request->userAgent();

        return $agent !== '' && preg_match(self::CRAWLER_PATTERN, $agent) === 1;
    }

    /**
     * @param  array<string, mixed>  $media
     */
    public static function isUpcoming(array $media): bool
    {
        $date = (string) ($media['release_date'] ?? $media['first_air_date'] ?? '');

        return $date !== '' && $date > now()->toDateString();
    }

    /**
     * Human-readable premiere / release date, or null when unknown.
     *
     * @param  array<string, mixed>  $media
     */
    public static function releaseLabel(array $media): ?string
    {
        $date = (string) ($media['release_date'] ?? $media['first_air_date'] ?? '');

        if ($date === '') {
            return null;
        }

        return Carbon::parse($date)->format('M j, Y');
    }

    /**
     * Build Open Graph / Twitter card fields for a movie or TV title.
     *
     * WhatsApp needs absolute HTTPS images that are not multi‑megabyte
     * originals — prefer w1280 backdrops (≈16:9) under ~600KB.
     *
     * @param  array<string, mixed>  $media
     * @return array{
     *     title: string,
     *     ogTitle: string,
     *     ogDescription: string,
     *     ogImage: ?string,
     *     ogImageAlt: string,
     *     ogUrl: string,
     *     ogType: string
     * }
     */
    public static function payload(array $media, string $mediaType, ?string $canonicalUrl = null): array
    {
        $tmdb = app(Tmdb::class);

        $name = (string) ($media['title'] ?? $media['name'] ?? 'Untitled');
        $year = Str::substr((string) ($media['release_date'] ?? $media['first_air_date'] ?? ''), 0, 4);
        $displayTitle = $year !== '' ? "{$name} ({$year})" : $name;
        $upcoming = self::isUpcoming($media);
        $releaseLabel = self::releaseLabel($media);

        if ($upcoming) {
            $displayTitle = 'Coming Soon: '.$displayTitle;
        }

        $overview = trim((string) ($media['overview'] ?? ''));
        if ($upcoming) {
            $prefix = $releaseLabel !== null
                ? "Coming soon · Premieres {$releaseLabel}."
                : 'Coming soon.';
            $description = $overview !== ''
                ? Str::limit($prefix.' '.$overview, 160)
                : $prefix.' Catch trailers and details on '.config('app.name').'.';
        } else {
            $description = $overview !== ''
                ? Str::limit($overview, 160)
                : "Watch {$displayTitle} on ".config('app.name').'.';
        }

        $image = null;
        if (! empty($media['backdrop_path'])) {
            $image = $tmdb->backdropUrl((string) $media['backdrop_path'], 'w1280');
        } elseif (! empty($media['poster_path'])) {
            $image = $tmdb->imageUrl((string) $media['poster_path'], 'w780');
        }

        $isTv = $mediaType === 'tv';

        return [
            'title' => $displayTitle,
            'ogTitle' => $displayTitle,
            'ogDescription' => $description,
            'ogImage' => $image,
            'ogImageAlt' => $displayTitle,
            'ogUrl' => $canonicalUrl ?? url()->current(),
            'ogType' => $isTv ? 'video.tv_show' : 'video.movie',
        ];
    }

    /**
     * Publish Open Graph / Twitter card data into the shared view bag.
     *
     * @param  array<string, mixed>  $media
     */
    public static function forMedia(array $media, string $mediaType, ?string $canonicalUrl = null): void
    {
        View::share(self::payload($media, $mediaType, $canonicalUrl));
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
        $app = config('app.name');

        if (self::isUpcoming($media)) {
            $releaseLabel = self::releaseLabel($media);

            return $releaseLabel !== null
                ? "Coming soon: {$label} premieres {$releaseLabel} — trailers & details on {$app}"
                : "Coming soon: {$label} — trailers & details on {$app}";
        }

        return "Watch {$label} — stream this {$kind} on {$app}";
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
