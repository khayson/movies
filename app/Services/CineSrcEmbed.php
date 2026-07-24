<?php

namespace App\Services;

class CineSrcEmbed
{
    /**
     * Build a CineSrc embed URL with documented customization parameters.
     *
     * @param  array{
     *     progress_seconds?: int,
     *     cinesrc_server_id?: string|null,
     *     quality?: string|null,
     *     autoplay?: bool,
     *     autonext?: bool|null,
     *     autoskip?: bool|null,
     *     muted?: bool,
     *     continue_prompt?: bool|null,
     *     back?: string|null,
     * }  $options
     */
    public function buildUrl(
        int $tmdbId,
        string $mediaType,
        ?int $season = null,
        ?int $episode = null,
        array $options = [],
    ): string {
        $baseUrl = rtrim((string) config('sources.cinesrc.base_url', 'https://cinesrc.st'), '/');

        $path = $mediaType === 'tv'
            ? "/embed/tv/{$tmdbId}"
            : "/embed/movie/{$tmdbId}";

        $query = $this->defaultQuery($mediaType, $season, $episode, $options);

        return $baseUrl.$path.'?'.http_build_query($query);
    }

    /**
     * @param  array{
     *     progress_seconds?: int,
     *     cinesrc_server_id?: string|null,
     *     quality?: string|null,
     *     autoplay?: bool,
     *     autonext?: bool|null,
     *     autoskip?: bool|null,
     *     muted?: bool,
     *     continue_prompt?: bool|null,
     *     back?: string|null,
     * }  $options
     * @return array<string, bool|int|string>
     */
    private function defaultQuery(string $mediaType, ?int $season, ?int $episode, array $options): array
    {
        $query = [
            'color' => (string) config('sources.cinesrc.color', '%23d97706'),
            'seek' => (int) config('sources.cinesrc.seek', 10),
            'autoplay' => $options['autoplay'] ?? filter_var(config('sources.cinesrc.autoplay', true), FILTER_VALIDATE_BOOL) ? 'true' : 'false',
        ];

        if ($mediaType === 'tv' && $season !== null && $episode !== null) {
            $query['s'] = $season;
            $query['e'] = $episode;
        }

        $progress = max(0, (int) ($options['progress_seconds'] ?? 0));
        if ($progress > 0) {
            $query['t'] = $progress;
            $continuePrompt = $options['continue_prompt'] ?? true;
            $query['continueprompt'] = $continuePrompt ? 'true' : 'false';
        }

        $serverId = $options['cinesrc_server_id'] ?? null;
        if (is_string($serverId) && $serverId !== '') {
            $query['lastserver'] = $serverId;
            $query['prioritize'] = 'true';
        }

        $quality = $options['quality'] ?? config('sources.cinesrc.default_quality');
        if (is_string($quality) && $quality !== '') {
            $query['quality'] = $quality;
        }

        if ($mediaType === 'tv') {
            $autonext = $options['autonext'] ?? config('sources.cinesrc.autonext');
            if ($autonext !== null) {
                $query['autonext'] = filter_var($autonext, FILTER_VALIDATE_BOOL) ? 'true' : 'false';
            }

            $autoskip = $options['autoskip'] ?? config('sources.cinesrc.autoskip');
            if ($autoskip !== null) {
                $query['autoskip'] = filter_var($autoskip, FILTER_VALIDATE_BOOL) ? 'true' : 'false';
            }
        }

        if (array_key_exists('muted', $options)) {
            $query['muted'] = $options['muted'] ? 'true' : 'false';
        }

        $back = $options['back'] ?? config('sources.cinesrc.back');
        if (is_string($back) && $back !== '') {
            $query['back'] = $back;
        }

        return $query;
    }
}
