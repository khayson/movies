<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchHistory;
use App\Support\ApiPayload;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Home-page rows built from what the active profile has watched, Netflix style.
 */
class PersonalizedRows
{
    private const ITEMS_PER_ROW = 12;

    private const SOURCE_TITLES = 2;

    public function __construct(private Tmdb $tmdb) {}

    /**
     * Rows for the user's active profile. Cached per profile and rebuilt when it starts a new title.
     *
     * @return list<array{title: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user): array
    {
        $history = $this->recentHistory($user);

        if ($history->isEmpty()) {
            return [];
        }

        $cacheKey = 'personalized_rows.'.$user->activeProfile()->id.'.'.$history->max('id');

        /** @var list<array{title: string, items: list<array<string, mixed>>}> */
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($history): array {
            $watched = $history->map(fn (WatchHistory $entry): string => $entry->media_type.':'.$entry->tmdb_id)->values()->all();
            $rows = [];

            foreach ($history->unique(fn (WatchHistory $entry): string => $entry->media_type.':'.$entry->tmdb_id)->take(self::SOURCE_TITLES) as $entry) {
                $items = $this->unwatched(
                    $this->tmdb->relatedFromDetails($this->details($entry), self::ITEMS_PER_ROW * 2),
                    $watched,
                    $entry->media_type,
                );

                if ($items !== []) {
                    $rows[] = ['title' => "Because you watched {$entry->title}", 'items' => $items];
                }
            }

            $topGenre = $this->favoriteGenres($history, 1)[0] ?? null;

            if ($topGenre !== null) {
                $items = $this->unwatched(
                    ApiPayload::rows($this->tmdb->discoverByGenre('movie', $topGenre['id'])['results'] ?? null),
                    $watched,
                    'movie',
                );

                if ($items !== []) {
                    $rows[] = ['title' => "More {$topGenre['name']} for you", 'items' => $items];
                }
            }

            return $rows;
        });
    }

    /**
     * The genres that appear most across the most recently watched titles.
     *
     * @param  Collection<int, WatchHistory>  $history
     * @return list<array{id: int, name: string}>
     */
    public function favoriteGenres(Collection $history, int $limit = 3): array
    {
        $counts = [];
        $names = [];

        foreach ($history->take(5) as $entry) {
            foreach (ApiPayload::rows($this->details($entry)['genres'] ?? null) as $genre) {
                if (! is_int($genre['id'] ?? null) || ! is_string($genre['name'] ?? null)) {
                    continue;
                }

                $counts[$genre['id']] = ($counts[$genre['id']] ?? 0) + 1;
                $names[$genre['id']] = $genre['name'];
            }
        }

        arsort($counts);

        return array_map(
            fn (int $id): array => ['id' => $id, 'name' => $names[$id]],
            array_slice(array_keys($counts), 0, $limit),
        );
    }

    /**
     * @return Collection<int, WatchHistory>
     */
    public function recentHistory(User $user): Collection
    {
        return $user->watchHistory()->visible()->limit(20)->get();
    }

    /**
     * TMDB details for a watched title, or nothing when TMDB no longer knows it.
     *
     * @return array<string, mixed>
     */
    private function details(WatchHistory $entry): array
    {
        try {
            return $this->tmdb->details($entry->media_type, $entry->tmdb_id);
        } catch (RequestException) {
            return [];
        }
    }

    /**
     * @param  list<array<array-key, mixed>>  $items
     * @param  list<string>  $watched  "type:id" keys already seen by the profile
     * @return list<array<string, mixed>>
     */
    private function unwatched(array $items, array $watched, string $defaultType): array
    {
        $rows = [];

        foreach ($items as $item) {
            $type = is_string($item['media_type'] ?? null) ? $item['media_type'] : $defaultType;

            if (! isset($item['id']) || empty($item['poster_path']) || in_array($type.':'.$item['id'], $watched, true)) {
                continue;
            }

            $rows[] = array_merge(array_filter($item, is_string(...), ARRAY_FILTER_USE_KEY), ['media_type' => $type]);

            if (count($rows) === self::ITEMS_PER_ROW) {
                break;
            }
        }

        return $rows;
    }
}
