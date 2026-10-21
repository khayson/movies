<?php

namespace App\Services;

use App\Models\WatchHistory;
use App\Support\ApiPayload;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

/**
 * Top 10 ranked by what StreamVault viewers watched this week, topped up with TMDB trending.
 */
class TopTen
{
    private const SIZE = 10;

    /**
     * A title only counts as popular on StreamVault once this many accounts watched it,
     * so one person's viewing never shows up on the public home page.
     */
    private const MIN_VIEWERS = 2;

    public function __construct(private Tmdb $tmdb) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forType(string $type): array
    {
        /** @var list<array<string, mixed>> */
        return Cache::remember("top_ten.{$type}", now()->addMinutes(30), function () use ($type): array {
            $ranked = [];

            foreach ($this->mostWatchedIds($type) as $tmdbId) {
                $details = $this->details($type, $tmdbId);

                if ($details === [] || ($details['adult'] ?? false) === true || empty($details['poster_path'])) {
                    continue;
                }

                $ranked[$tmdbId] = $details;
            }

            foreach (ApiPayload::rows($this->tmdb->trending($type, 'week')['results'] ?? null) as $item) {
                if (count($ranked) >= self::SIZE) {
                    break;
                }

                if (is_int($item['id'] ?? null) && ! isset($ranked[$item['id']]) && empty($item['adult'])) {
                    $ranked[$item['id']] = $item;
                }
            }

            return array_values(array_slice($ranked, 0, self::SIZE, true));
        });
    }

    /**
     * TMDB ids watched by the most distinct accounts in the last 7 days, private viewing excluded.
     *
     * @return list<int>
     */
    private function mostWatchedIds(string $type): array
    {
        return array_values(WatchHistory::query()
            ->visible()
            ->where('media_type', $type)
            ->where('updated_at', '>=', now()->subDays(7))
            ->groupBy('tmdb_id')
            ->havingRaw('COUNT(DISTINCT user_id) >= ?', [self::MIN_VIEWERS])
            ->orderByRaw('COUNT(DISTINCT user_id) DESC')
            ->limit(self::SIZE)
            ->pluck('tmdb_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function details(string $type, int $tmdbId): array
    {
        try {
            return $this->tmdb->details($type, $tmdbId);
        } catch (RequestException) {
            return [];
        }
    }
}
