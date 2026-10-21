<?php

namespace App\Support;

/**
 * Helpers for reading decoded JSON from third-party APIs, whose shape is never guaranteed.
 */
class ApiPayload
{
    /**
     * The array entries of a JSON list, skipping anything that is not an object.
     *
     * @return list<array<array-key, mixed>>
     */
    public static function rows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
    }
}
