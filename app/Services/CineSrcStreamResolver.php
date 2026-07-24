<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CineSrcStreamResolver
{
    /**
     * Resolve a direct HLS stream URL via an optional external CineSrc resolver service.
     *
     * Configure CINESRC_RESOLVER_URL to point at a running cinesrc-stream-resolver instance.
     *
     * @return array{url: string, quality: string, provider: string, source: string|null, name: string|null}|null
     */
    public function resolve(
        int $tmdbId,
        string $mediaType = 'movie',
        ?int $season = null,
        ?int $episode = null,
    ): ?array {
        $resolverUrl = config('sources.cinesrc.resolver_url');

        if (! is_string($resolverUrl) || $resolverUrl === '') {
            return null;
        }

        $cacheKey = "cinesrc.stream.{$mediaType}.{$tmdbId}.{$season}.{$episode}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($resolverUrl, $tmdbId, $mediaType, $season, $episode): ?array {
            $query = [
                'id' => $tmdbId,
                'type' => $mediaType,
            ];

            if ($mediaType === 'tv' && $season !== null && $episode !== null) {
                $query['season'] = $season;
                $query['episode'] = $episode;
            }

            $endpoint = rtrim($resolverUrl, '/').'/api/stream/live?'.http_build_query($query);

            try {
                $response = Http::timeout((int) config('sources.cinesrc.resolver_timeout', 45))
                    ->withOptions(['stream' => true])
                    ->get($endpoint);

                if (! $response->successful()) {
                    return null;
                }

                $stream = $response->toPsrResponse()->getBody();
                $buffer = '';
                $readyPayload = null;

                while (! $stream->eof()) {
                    $chunk = $stream->read(1024);
                    if ($chunk === '') {
                        break;
                    }

                    $buffer .= $chunk;

                    while (($lineBreak = strpos($buffer, "\n\n")) !== false) {
                        $block = substr($buffer, 0, $lineBreak);
                        $buffer = substr($buffer, $lineBreak + 2);

                        $event = $this->parseSseBlock($block);
                        if (($event['event'] ?? '') === 'ready' && is_array($event['data'])) {
                            $readyPayload = $event['data'];
                            break 2;
                        }

                        if (($event['event'] ?? '') === 'fail') {
                            return null;
                        }
                    }
                }

                if ($readyPayload === null) {
                    return null;
                }

                $playUrl = $readyPayload['playUrl'] ?? $readyPayload['url'] ?? null;
                if (! is_string($playUrl) || $playUrl === '') {
                    return null;
                }

                return [
                    'url' => $playUrl,
                    'quality' => (string) ($readyPayload['quality'] ?? 'auto'),
                    'provider' => 'CineSrc Direct',
                    'source' => isset($readyPayload['source']) ? (string) $readyPayload['source'] : null,
                    'name' => isset($readyPayload['name']) ? (string) $readyPayload['name'] : null,
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @return array{event: string|null, data: array<string, mixed>|null}
     */
    private function parseSseBlock(string $block): array
    {
        $event = null;
        $dataLines = [];

        foreach (explode("\n", $block) as $line) {
            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, 6));
            } elseif (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(substr($line, 5));
            }
        }

        $decoded = null;
        if ($dataLines !== []) {
            $json = implode("\n", $dataLines);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                $decoded = $parsed;
            }
        }

        return ['event' => $event, 'data' => $decoded];
    }
}
