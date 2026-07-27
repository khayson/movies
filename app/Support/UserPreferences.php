<?php

namespace App\Support;

class UserPreferences
{
    /**
     * Documented defaults for all first-party preference keys.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'default_source' => '',
            'preferred_type' => 'all',
            'content_language' => 'en',
            'streaming_country' => 'us',
            'email_notifications' => true,
            'autoplay_trailers' => true,
            'show_adult_content' => false,
            'stream_quality' => '',
            'cinesrc_autoskip' => false,
            'cinesrc_autonext' => true,
            'prefer_hls_direct' => false,
            'autoplay_on_watch' => true,
            'start_muted' => false,
            'resume_prompt' => true,
            'cinesrc_seek' => 10,
            'show_continue_watching' => true,
            'blur_spoilers' => false,
            'hide_from_leaderboard' => false,
            'remember_last_server' => true,
            'auto_fallback_on_error' => true,
            'show_trailer_in_servers' => true,
            'use_provider_scores' => true,
            'excluded_providers' => [],
            'disable_content_personalization' => false,
            'cinesrc_back' => 'close',
        ];
    }

    /**
     * Merge existing preferences with updates without dropping unknown keys.
     *
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>
     */
    public static function merge(?array $existing, array $updates): array
    {
        return array_merge($existing ?? [], $updates);
    }

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function get(?array $preferences, string $key, mixed $default = null): mixed
    {
        $prefs = $preferences ?? [];

        if (array_key_exists($key, $prefs)) {
            return $prefs[$key];
        }

        $defaults = self::defaults();

        return array_key_exists($key, $defaults) ? $defaults[$key] : $default;
    }

    /**
     * @param  array<string, mixed>|null  $preferences
     */
    public static function bool(?array $preferences, string $key, bool $default = false): bool
    {
        return (bool) self::get($preferences, $key, $default);
    }
}
