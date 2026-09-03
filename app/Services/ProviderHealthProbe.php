<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProviderHealthProbe
{
    private const CACHE_KEY = 'provider_probe_results';

    private const SAMPLE_TMDB_ID = 550;

    /**
     * @return array<string, array{healthy: bool, checked_at: int, status?: int}>
     */
    public function results(): array
    {
        /** @var array<string, array{healthy: bool, checked_at: int, status?: int}> */
        return Cache::get(self::CACHE_KEY, []);
    }

    public function isHealthy(string $provider): ?bool
    {
        $results = $this->results();

        if (! isset($results[$provider])) {
            return null;
        }

        $checkedAt = $results[$provider]['checked_at'] ?? 0;

        if ((time() - $checkedAt) > 3600) {
            return null;
        }

        return (bool) ($results[$provider]['healthy'] ?? false);
    }

    /**
     * @return array{checked: int, healthy: int, unhealthy: int}
     */
    public function probeAll(): array
    {
        /** @var array<int, array{name?: string, driver?: string, movie_url?: string}> $providers */
        $providers = config('sources.providers', []);
        $results = $this->results();
        $stats = ['checked' => 0, 'healthy' => 0, 'unhealthy' => 0];

        foreach ($providers as $provider) {
            if (($provider['driver'] ?? '') !== 'embed') {
                continue;
            }

            $name = $provider['name'] ?? 'Embed';
            $template = $provider['movie_url'] ?? '';

            if ($template === '') {
                continue;
            }

            $url = str_replace('{id}', (string) self::SAMPLE_TMDB_ID, $template);
            $healthy = $this->probeUrl($url);
            $stats['checked']++;
            $stats[$healthy ? 'healthy' : 'unhealthy']++;

            $results[$name] = [
                'healthy' => $healthy,
                'checked_at' => time(),
            ];
        }

        $cineSrcBase = rtrim((string) config('sources.cinesrc.base_url', ''), '/');
        if ($cineSrcBase !== '') {
            $healthy = $this->probeUrl($cineSrcBase.'/embed/movie/'.self::SAMPLE_TMDB_ID);
            $stats['checked']++;
            $stats[$healthy ? 'healthy' : 'unhealthy']++;
            $results['CineSrc'] = [
                'healthy' => $healthy,
                'checked_at' => time(),
            ];
        }

        Cache::put(self::CACHE_KEY, $results, now()->addHours(2));

        return $stats;
    }

    public function probeUrl(string $url): bool
    {
        try {
            $response = Http::timeout(8)
                ->connectTimeout(3)
                ->withHeaders(['User-Agent' => 'StreamVault-HealthProbe/1.0'])
                ->head($url);

            if ($response->successful()) {
                return true;
            }

            if (in_array($response->status(), [405, 403, 301, 302, 303, 307, 308], true)) {
                return true;
            }

            $getResponse = Http::timeout(8)
                ->connectTimeout(3)
                ->withHeaders(['User-Agent' => 'StreamVault-HealthProbe/1.0'])
                ->get($url);

            return $getResponse->successful() || in_array($getResponse->status(), [403, 405], true);
        } catch (\Throwable) {
            return false;
        }
    }
}
