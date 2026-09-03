<?php

namespace App\Services;

class EmbedUrlBuilder
{
    /**
     * Append provider-specific query parameters for playback personalization.
     *
     * @param  array{
     *     embed_options?: array<string, mixed>,
     * }  $provider
     * @param  array{
     *     progress_seconds?: int,
     *     autoplay?: bool,
     *     muted?: bool,
     *     continue_prompt?: bool,
     *     media_type?: string,
     * }  $context
     */
    public function enrich(string $url, array $provider, array $context = []): string
    {
        /** @var array<string, mixed> $optionMap */
        $optionMap = $provider['embed_options'] ?? [];

        if ($optionMap === []) {
            return $url;
        }

        $query = $this->buildQuery($optionMap, $context);

        if ($query === []) {
            return $url;
        }

        $fragment = '';
        if (($hashPos = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }

        $existing = [];
        $base = $url;

        if (($queryPos = strpos($url, '?')) !== false) {
            $base = substr($url, 0, $queryPos);
            parse_str(substr($url, $queryPos + 1), $existing);
        }

        $merged = array_merge($existing, $query);

        return $base.'?'.http_build_query($merged).$fragment;
    }

    /**
     * @param  array<string, mixed>  $optionMap
     * @param  array{
     *     progress_seconds?: int,
     *     autoplay?: bool,
     *     muted?: bool,
     *     continue_prompt?: bool,
     *     media_type?: string,
     * }  $context
     * @return array<string, bool|int|string>
     */
    private function buildQuery(array $optionMap, array $context): array
    {
        $query = [];

        $autoplayKey = $optionMap['autoplay'] ?? null;
        if (is_string($autoplayKey) && array_key_exists('autoplay', $context)) {
            $query[$autoplayKey] = $this->formatBoolParam($context['autoplay'], $optionMap['autoplay_format'] ?? 'true_false');
        }

        $progress = max(0, (int) ($context['progress_seconds'] ?? 0));
        $progressKey = $optionMap['progress'] ?? null;

        if (is_string($progressKey) && $progress > 30) {
            $query[$progressKey] = $progress;
        }

        $mutedKey = $optionMap['muted'] ?? null;
        if (is_string($mutedKey) && ($context['muted'] ?? false)) {
            $query[$mutedKey] = $this->formatBoolParam(true, $optionMap['muted_format'] ?? 'one_zero');
        }

        $themeKey = $optionMap['theme'] ?? null;
        if (is_string($themeKey)) {
            $query[$themeKey] = ltrim((string) ($optionMap['theme_value'] ?? 'd97706'), '#');
        }

        $subtitleKey = $optionMap['subtitle'] ?? null;
        if (is_string($subtitleKey) && is_string($context['subtitle'] ?? null) && $context['subtitle'] !== '') {
            $query[$subtitleKey] = $context['subtitle'];
        }

        return $query;
    }

    private function formatBoolParam(bool $value, string $format): string|int
    {
        return match ($format) {
            'one_zero' => $value ? 1 : 0,
            'true_false' => $value ? 'true' : 'false',
            default => $value ? '1' : '0',
        };
    }
}
