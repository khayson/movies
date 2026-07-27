<?php

namespace App\Support;

class SettingsSearch
{
    /**
     * Catalog of settings destinations for sidebar search.
     *
     * @return list<array{id: string, label: string, description: string, group: string, route: string, anchor: string, keywords: list<string>}>
     */
    public static function catalog(): array
    {
        return [
            [
                'id' => 'profile',
                'label' => 'Profile',
                'description' => 'Name, email, and account details',
                'group' => 'Profile',
                'route' => 'profile.edit',
                'anchor' => 'settings-profile',
                'keywords' => ['name', 'email', 'account', 'avatar', 'verify', 'verification', 'member'],
            ],
            [
                'id' => 'profile-delete',
                'label' => 'Delete account',
                'description' => 'Permanently remove your StreamVault account',
                'group' => 'Profile',
                'route' => 'profile.edit',
                'anchor' => 'settings-delete-account',
                'keywords' => ['delete', 'remove', 'close account', 'destroy', 'erase'],
            ],
            [
                'id' => 'security-password',
                'label' => 'Password',
                'description' => 'Change your account password',
                'group' => 'Security',
                'route' => 'security.edit',
                'anchor' => 'settings-password',
                'keywords' => ['password', 'current password', 'new password', 'login', 'credentials'],
            ],
            [
                'id' => 'security-2fa',
                'label' => 'Two-factor authentication',
                'description' => 'Enable or disable 2FA and recovery codes',
                'group' => 'Security',
                'route' => 'security.edit',
                'anchor' => 'settings-two-factor',
                'keywords' => ['2fa', 'two factor', 'totp', 'authenticator', 'mfa', 'recovery codes', 'security'],
            ],
            [
                'id' => 'security-passkeys',
                'label' => 'Passkeys',
                'description' => 'Passwordless sign-in with passkeys',
                'group' => 'Security',
                'route' => 'security.edit',
                'anchor' => 'settings-passkeys',
                'keywords' => ['passkey', 'passwordless', 'webauthn', 'biometric', 'fingerprint'],
            ],
            [
                'id' => 'appearance',
                'label' => 'Appearance',
                'description' => 'Light, dark, or system theme',
                'group' => 'Appearance',
                'route' => 'appearance.edit',
                'anchor' => 'settings-appearance',
                'keywords' => ['theme', 'dark mode', 'light mode', 'system', 'look', 'display'],
            ],
            [
                'id' => 'pref-streaming',
                'label' => 'Streaming',
                'description' => 'Default server, quality, country, and CineSrc Direct',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-streaming',
                'keywords' => ['streaming', 'source', 'server', 'quality', '1080', '720', 'country', 'hls', 'cinesrc direct', 'prefer hls', 'where to watch'],
            ],
            [
                'id' => 'pref-playback',
                'label' => 'Playback',
                'description' => 'Autoplay, mute, resume prompt, seek, and episode skip',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-playback',
                'keywords' => ['playback', 'autoplay', 'trailer', 'muted', 'mute', 'resume', 'continue', 'restart', 'seek', 'skip intro', 'autonext', 'next episode', 'watch page'],
            ],
            [
                'id' => 'pref-discovery',
                'label' => 'Discovery',
                'description' => 'Preferred type, language, continue watching, spoilers',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-discovery',
                'keywords' => ['discovery', 'movies', 'tv', 'language', 'continue watching', 'dashboard', 'spoilers', 'blur', 'recommendations'],
            ],
            [
                'id' => 'pref-notifications',
                'label' => 'Notifications',
                'description' => 'Email alerts for releases and watchlist updates',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-notifications',
                'keywords' => ['notifications', 'email', 'alerts', 'watchlist updates', 'new releases'],
            ],
            [
                'id' => 'pref-privacy',
                'label' => 'Privacy & maturity',
                'description' => 'Date of birth, adult content, and leaderboard visibility',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-privacy',
                'keywords' => ['privacy', 'adult', 'nsfw', 'maturity', 'age', 'birthday', 'date of birth', 'leaderboard', 'hide'],
            ],
            [
                'id' => 'pref-advanced',
                'label' => 'Advanced',
                'description' => 'Manual overrides for servers, fallbacks, and scoring',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-advanced',
                'keywords' => ['advanced', 'fallback', 'auto switch', 'remember server', 'excluded providers', 'reliability', 'scoring', 'personalization', 'cinesrc back', 'trailer in servers', 'overrides', 'manual'],
            ],
            [
                'id' => 'pref-data',
                'label' => 'Your data',
                'description' => 'Clear watch history, watchlist, and favorites',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-your-data',
                'keywords' => ['data', 'history', 'watchlist', 'favorites', 'clear', 'reset preferences', 'delete data'],
            ],
            [
                'id' => 'pref-default-source',
                'label' => 'Default streaming source',
                'description' => 'Preferred server when watching content',
                'group' => 'Preferences · Streaming',
                'route' => 'preferences.edit',
                'anchor' => 'settings-streaming',
                'keywords' => ['default source', 'preferred server', 'vidcore', 'cinesrc', 'auto server'],
            ],
            [
                'id' => 'pref-quality',
                'label' => 'Preferred stream quality',
                'description' => '1080p, 720p, or auto quality for CineSrc',
                'group' => 'Preferences · Streaming',
                'route' => 'preferences.edit',
                'anchor' => 'settings-streaming',
                'keywords' => ['quality', 'resolution', '1080p', '720p', '480p', 'hd'],
            ],
            [
                'id' => 'pref-hls',
                'label' => 'Prefer CineSrc Direct (HLS)',
                'description' => 'Use direct HLS when available instead of the embed',
                'group' => 'Preferences · Streaming',
                'route' => 'preferences.edit',
                'anchor' => 'settings-streaming',
                'keywords' => ['hls', 'direct', 'm3u8', 'native player', 'cinesrc direct'],
            ],
            [
                'id' => 'pref-autoplay-watch',
                'label' => 'Autoplay on watch page',
                'description' => 'Start playback when the watch page loads',
                'group' => 'Preferences · Playback',
                'route' => 'preferences.edit',
                'anchor' => 'settings-playback',
                'keywords' => ['autoplay', 'auto play', 'start playing', 'watch page'],
            ],
            [
                'id' => 'pref-resume',
                'label' => 'Continue / Restart prompt',
                'description' => 'Ask before resuming from your last position',
                'group' => 'Preferences · Playback',
                'route' => 'preferences.edit',
                'anchor' => 'settings-playback',
                'keywords' => ['resume', 'continue watching', 'restart', 'prompt', 'progress'],
            ],
            [
                'id' => 'pref-fallback',
                'label' => 'Auto-switch server on error',
                'description' => 'Try another server when playback fails',
                'group' => 'Preferences · Advanced',
                'route' => 'preferences.edit',
                'anchor' => 'settings-advanced',
                'keywords' => ['fallback', 'auto switch', 'error', 'failed server', 'next server'],
            ],
            [
                'id' => 'pref-excluded',
                'label' => 'Excluded providers',
                'description' => 'Hide specific streaming providers from the server list',
                'group' => 'Preferences · Advanced',
                'route' => 'preferences.edit',
                'anchor' => 'settings-advanced',
                'keywords' => ['exclude', 'excluded', 'block provider', 'hide server', 'blacklist'],
            ],
            [
                'id' => 'pref-reset',
                'label' => 'Reset preferences to defaults',
                'description' => 'Restore StreamVault default preference values',
                'group' => 'Preferences',
                'route' => 'preferences.edit',
                'anchor' => 'settings-your-data',
                'keywords' => ['reset', 'defaults', 'restore', 'factory reset', 'clear preferences'],
            ],
        ];
    }

    /**
     * Search catalog entries by phrase or keywords.
     *
     * @return list<array{id: string, label: string, description: string, group: string, route: string, anchor: string, url: string, score: float}>
     */
    public static function search(string $query, int $limit = 8): array
    {
        $query = trim(mb_strtolower($query));

        if ($query === '') {
            return [];
        }

        $tokens = self::tokens($query);
        $scored = [];

        foreach (self::catalog() as $item) {
            $score = self::score($item, $query, $tokens);

            if ($score <= 0) {
                continue;
            }

            $scored[] = [
                ...$item,
                'url' => route($item['route']).'#'.$item['anchor'],
                'score' => $score,
            ];
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * JSON-ready catalog for Alpine client-side filtering.
     *
     * @return list<array{id: string, label: string, description: string, group: string, route: string, anchor: string, url: string, haystack: string}>
     */
    public static function clientIndex(): array
    {
        return array_map(function (array $item): array {
            $haystack = mb_strtolower(implode(' ', [
                $item['label'],
                $item['description'],
                $item['group'],
                implode(' ', $item['keywords']),
            ]));

            return [
                'id' => $item['id'],
                'label' => $item['label'],
                'description' => $item['description'],
                'group' => $item['group'],
                'route' => $item['route'],
                'anchor' => $item['anchor'],
                'url' => route($item['route']).'#'.$item['anchor'],
                'haystack' => $haystack,
            ];
        }, self::catalog());
    }

    /**
     * @param  array{label: string, description: string, group: string, keywords: list<string>}  $item
     * @param  list<string>  $tokens
     */
    public static function score(array $item, string $query, array $tokens): float
    {
        $label = mb_strtolower($item['label']);
        $description = mb_strtolower($item['description']);
        $group = mb_strtolower($item['group']);
        $keywords = array_map(mb_strtolower(...), $item['keywords']);
        $haystack = trim(implode(' ', [$label, $description, $group, implode(' ', $keywords)]));

        $score = 0.0;

        if ($label === $query) {
            $score += 100;
        } elseif (str_starts_with($label, $query)) {
            $score += 70;
        } elseif (str_contains($label, $query)) {
            $score += 45;
        }

        if (str_contains($description, $query)) {
            $score += 25;
        }

        if (str_contains($group, $query)) {
            $score += 15;
        }

        foreach ($keywords as $keyword) {
            if ($keyword === $query) {
                $score += 55;
            } elseif (str_starts_with($keyword, $query) || str_contains($keyword, $query)) {
                $score += 30;
            } elseif (str_contains($query, $keyword) && mb_strlen($keyword) >= 4) {
                $score += 40;
            }
        }

        if ($tokens !== []) {
            $matched = 0;
            $keywordBlob = implode(' ', $keywords);

            foreach ($tokens as $token) {
                if (str_contains($haystack, $token)) {
                    $matched++;
                    $score += 12;

                    if (str_contains($keywordBlob, $token)) {
                        $score += 4;
                    }
                } elseif (self::fuzzyContains($haystack, $token)) {
                    $matched++;
                    $score += 6;
                }
            }

            if ($matched === count($tokens)) {
                $score += 20;
            } elseif ($matched === 0) {
                return 0;
            }
        }

        // Prefer concrete controls over broad section hubs when scores are close.
        if (str_contains($item['id'], 'pref-') && ! in_array($item['id'], [
            'pref-streaming',
            'pref-playback',
            'pref-discovery',
            'pref-notifications',
            'pref-privacy',
            'pref-advanced',
            'pref-data',
        ], true)) {
            $score += 8;
        }

        return $score;
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $query): array
    {
        $parts = preg_split('/\s+/u', $query) ?: [];

        return array_values(array_filter($parts, fn (string $part): bool => mb_strlen($part) >= 2));
    }

    private static function fuzzyContains(string $haystack, string $needle): bool
    {
        if (mb_strlen($needle) < 4) {
            return false;
        }

        $words = preg_split('/\s+/u', $haystack) ?: [];

        foreach ($words as $word) {
            similar_text($word, $needle, $percent);

            if ($percent >= 78) {
                return true;
            }
        }

        return false;
    }
}
