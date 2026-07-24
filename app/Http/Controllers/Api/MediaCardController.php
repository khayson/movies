<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Watchlist;
use App\Services\Tmdb;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaCardController extends Controller
{
    public function show(Request $request, Tmdb $tmdb, string $type, int $id): JsonResponse
    {
        $details = $tmdb->get("/{$type}/{$id}", [
            'append_to_response' => 'videos,credits',
        ]);

        $trailerKey = Tmdb::extractTrailerKey($details['videos']['results'] ?? []);

        $genres = collect($details['genres'] ?? [])
            ->take(3)
            ->pluck('name')
            ->all();

        $cast = collect($details['credits']['cast'] ?? [])
            ->take(4)
            ->map(fn (array $p): array => [
                'name' => $p['name'],
                'character' => $p['character'] ?? '',
                'image' => ! empty($p['profile_path'])
                    ? $tmdb->imageUrl($p['profile_path'], 'w92')
                    : null,
            ])
            ->all();

        $runtime = $type === 'movie'
            ? ($details['runtime'] ?? null)
            : ($details['episode_run_time'][0] ?? $details['last_episode_to_air']['runtime'] ?? null);

        $user = $request->user();

        return response()->json([
            'id' => $id,
            'type' => $type,
            'title' => $details['title'] ?? $details['name'] ?? '',
            'tagline' => $details['tagline'] ?? '',
            'overview' => $details['overview'] ?? '',
            'rating' => round($details['vote_average'] ?? 0, 1),
            'vote_count' => $details['vote_count'] ?? 0,
            'runtime' => $runtime,
            'genres' => $genres,
            'cast' => $cast,
            'trailer_key' => $trailerKey,
            'backdrop' => ! empty($details['backdrop_path'])
                ? $tmdb->imageUrl($details['backdrop_path'], 'w780')
                : null,
            'status' => $details['status'] ?? '',
            'seasons' => $type === 'tv' ? ($details['number_of_seasons'] ?? null) : null,
            'is_favorited' => $user?->hasFavorited($id, $type) ?? false,
            'is_watchlisted' => $user?->hasOnWatchlist($id, $type) ?? false,
        ]);
    }

    public function toggleWatchlist(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $existing = $user->watchlist()
            ->where('tmdb_id', $id)
            ->where('media_type', $type)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['added' => false]);
        }

        $tmdb = app(Tmdb::class);
        $details = $tmdb->get("/{$type}/{$id}");

        Watchlist::create([
            'user_id' => $user->id,
            'tmdb_id' => $id,
            'media_type' => $type,
            'title' => $details['title'] ?? $details['name'] ?? '',
            'poster_path' => $details['poster_path'] ?? '',
            'overview' => $details['overview'] ?? '',
            'release_date' => $details['release_date'] ?? $details['first_air_date'] ?? null,
            'vote_average' => $details['vote_average'] ?? 0,
        ]);

        return response()->json(['added' => true]);
    }

    public function toggleFavorite(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $existing = $user->favorites()
            ->where('tmdb_id', $id)
            ->where('media_type', $type)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['added' => false]);
        }

        $tmdb = app(Tmdb::class);
        $details = $tmdb->get("/{$type}/{$id}");

        Favorite::create([
            'user_id' => $user->id,
            'tmdb_id' => $id,
            'media_type' => $type,
            'title' => $details['title'] ?? $details['name'] ?? '',
            'poster_path' => $details['poster_path'] ?? '',
        ]);

        return response()->json(['added' => true]);
    }
}
