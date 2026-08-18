<?php

namespace App\Support;

use Illuminate\Support\Str;

class AdultSafety
{
    /**
     * Block queries and titles that could request or surface minor-related content.
     *
     * @var list<string>
     */
    private const BLOCKED_PATTERNS = [
        '/\bteens?\b/',
        '/\bteenagers?\b/',
        '/\bpre[-\s]?teens?\b/',
        '/\bchild(?:ren)?\b/',
        '/\bkids?\b/',
        '/\bunderage\b/',
        '/\bminors?\b/',
        '/\bloli(?:ta)?\b/',
        '/\bshota\b/',
        '/\bped[oa]/',
    ];

    public static function isBlockedQuery(string $value): bool
    {
        $normalized = Str::of($value)->ascii()->lower()->trim()->toString();

        if ($normalized === '') {
            return false;
        }

        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $videos
     * @return array<int, array<string, mixed>>
     */
    public static function rejectBlockedTitles(array $videos): array
    {
        return collect($videos)
            ->reject(fn (array $video): bool => self::isBlockedQuery((string) ($video['title'] ?? '')))
            ->values()
            ->all();
    }
}
