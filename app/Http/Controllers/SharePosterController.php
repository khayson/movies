<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SharePosterController extends Controller
{
    /**
     * Proxy a TMDB image so browsers can share it as a real photo
     * (needed for WhatsApp Status — text+URL alone often skips the poster).
     */
    public function __invoke(Request $request): Response
    {
        $src = (string) $request->query('src', '');

        abort_unless($this->isAllowedTmdbImageUrl($src), 404);

        try {
            $upstream = Http::timeout(8)
                ->connectTimeout(3)
                ->withHeaders([
                    'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'User-Agent' => 'StreamVault-SharePoster/1.0',
                ])
                ->get($src)
                ->throw();
        } catch (ConnectionException|RequestException) {
            abort(502);
        }

        $contentType = (string) ($upstream->header('Content-Type') ?: 'image/jpeg');
        abort_unless(Str::startsWith($contentType, 'image/'), 502);

        return response($upstream->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    private function isAllowedTmdbImageUrl(string $src): bool
    {
        if ($src === '' || filter_var($src, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($src);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($scheme !== 'https' || $host !== 'image.tmdb.org') {
            return false;
        }

        return preg_match('#^/t/p/(w\d+|original)/[A-Za-z0-9._/-]+\.(jpg|jpeg|png|webp)$#i', $path) === 1;
    }
}
