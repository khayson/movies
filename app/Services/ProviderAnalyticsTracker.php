<?php

namespace App\Services;

use App\Models\ProviderAnalytic;
use Illuminate\Support\Facades\Cache;

class ProviderAnalyticsTracker
{
    public function reportFailure(string $provider): void
    {
        $failed = Cache::get('failed_providers', []);
        $failed[$provider] = time();
        Cache::put('failed_providers', $failed, now()->addMinutes(30));

        $this->record($provider, 'failure');
    }

    public function reportSuccess(string $provider): void
    {
        $this->record($provider, 'success');
    }

    public function reportBuffering(string $provider, int $loadTimeMs = 0): void
    {
        $this->record($provider, 'buffer', $loadTimeMs);
    }

    /**
     * @param  array<int, string>  $providers
     * @return array<string, int>
     */
    public function healthMap(array $providers): array
    {
        $failed = Cache::get('failed_providers', []);
        $health = [];

        foreach ($providers as $provider) {
            if (! isset($failed[$provider])) {
                $health[$provider] = 100;

                continue;
            }

            $minutesAgo = (time() - $failed[$provider]) / 60;
            $health[$provider] = $minutesAgo > 15 ? 75 : ($minutesAgo > 5 ? 50 : 25);
        }

        return $health;
    }

    /**
     * @return array<string, int>
     */
    public function failedProviders(): array
    {
        /** @var array<string, int> */
        return Cache::get('failed_providers', []);
    }

    /**
     * @return array<string, int>
     */
    public function hourlyScores(): array
    {
        $hour = (int) now()->format('G');

        return Cache::remember("provider_hourly_scores.{$hour}", now()->addMinutes(15), function () use ($hour): array {
            $analytics = ProviderAnalytic::where('hour_bucket', $hour)
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->selectRaw('provider, SUM(success_count) as wins, SUM(failure_count) as fails, AVG(avg_load_ms) as load_ms')
                ->groupBy('provider')
                ->get();

            $scores = [];
            foreach ($analytics as $row) {
                $total = $row->wins + $row->fails;
                if ($total < 5) {
                    continue;
                }

                $successRate = $row->wins / $total;
                $loadPenalty = min(10, (int) ($row->load_ms / 1000));
                $scores[$row->provider] = (int) round($successRate * 15) - $loadPenalty;
            }

            return $scores;
        });
    }

    /**
     * @return array<string, int>
     */
    public function regionScores(): array
    {
        $region = $this->detectRegion();

        return Cache::remember("provider_region_scores.{$region}", now()->addMinutes(30), function () use ($region): array {
            $analytics = ProviderAnalytic::where('region', $region)
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->selectRaw('provider, SUM(success_count) as wins, SUM(failure_count) as fails, SUM(buffer_count) as buffers')
                ->groupBy('provider')
                ->get();

            $scores = [];
            foreach ($analytics as $row) {
                $total = $row->wins + $row->fails;
                if ($total < 5) {
                    continue;
                }

                $successRate = $row->wins / $total;
                $bufferRate = $total > 0 ? $row->buffers / $total : 0;
                $scores[$row->provider] = (int) round($successRate * 10) - (int) round($bufferRate * 8);
            }

            return $scores;
        });
    }

    /**
     * @return array<string, int>
     */
    public function underutilizedBoosts(): array
    {
        return Cache::remember('provider_underutilized_boosts', now()->addMinutes(15), function (): array {
            /** @var array<string, int> $totals */
            $totals = ProviderAnalytic::query()
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->selectRaw('provider, SUM(success_count) as successes')
                ->groupBy('provider')
                ->pluck('successes', 'provider')
                ->all();

            if ($totals === []) {
                return [];
            }

            $max = max($totals);

            if ($max < 10) {
                return [];
            }

            $boosts = [];

            foreach ($totals as $provider => $successes) {
                $ratio = $successes / $max;

                if ($ratio >= 0.5) {
                    continue;
                }

                $boosts[$provider] = (int) round((0.5 - $ratio) * 16);
            }

            return $boosts;
        });
    }

    public function detectRegion(): string
    {
        foreach (['CF-IPCountry', 'X-Country-Code', 'CloudFront-Viewer-Country'] as $header) {
            $country = request()->header($header);

            if (is_string($country) && strlen($country) === 2 && strtoupper($country) !== 'XX') {
                return strtoupper($country);
            }
        }

        $ip = request()->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'local';
        }

        return Cache::remember("geo_region.{$ip}", now()->addHours(24), function (): string {
            $timezone = config('app.timezone', 'UTC');

            return match (true) {
                str_contains($timezone, 'America') => 'NA',
                str_contains($timezone, 'Europe') => 'EU',
                str_contains($timezone, 'Asia') => 'AS',
                str_contains($timezone, 'Africa') => 'AF',
                str_contains($timezone, 'Australia'), str_contains($timezone, 'Pacific') => 'OC',
                default => 'unknown',
            };
        });
    }

    /**
     * @return array<int, array{provider: string, success_rate: float, failures: int, buffers: int, samples: int, health: int}>
     */
    public function sloSummary(int $days = 7): array
    {
        $rows = ProviderAnalytic::query()
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->selectRaw('provider, SUM(success_count) as wins, SUM(failure_count) as fails, SUM(buffer_count) as buffers')
            ->groupBy('provider')
            ->get();

        $health = $this->healthMap($rows->pluck('provider')->all());
        $probe = app(ProviderHealthProbe::class);

        $summary = [];

        foreach ($rows as $row) {
            $samples = (int) $row->wins + (int) $row->fails;

            if ($samples < 1) {
                continue;
            }

            $provider = (string) $row->provider;
            $probeHealthy = $probe->isHealthy($provider);

            $summary[] = [
                'provider' => $provider,
                'success_rate' => round(((int) $row->wins / $samples) * 100, 1),
                'failures' => (int) $row->fails,
                'buffers' => (int) $row->buffers,
                'samples' => $samples,
                'health' => $health[$provider] ?? 100,
                'probe_healthy' => $probeHealthy,
            ];
        }

        usort($summary, fn (array $a, array $b): int => $b['success_rate'] <=> $a['success_rate']);

        return $summary;
    }

    private function record(string $provider, string $event, int $loadTimeMs = 0): void
    {
        $hour = (int) now()->format('G');
        $region = $this->detectRegion();
        $date = now()->toDateString();

        $record = ProviderAnalytic::firstOrCreate(
            ['provider' => $provider, 'region' => $region, 'hour_bucket' => $hour, 'date' => $date],
            ['success_count' => 0, 'failure_count' => 0, 'buffer_count' => 0, 'avg_load_ms' => 0],
        );

        match ($event) {
            'success' => $record->increment('success_count'),
            'failure' => $record->increment('failure_count'),
            'buffer' => $record->increment('buffer_count'),
            default => null,
        };

        if ($loadTimeMs > 0 && $record->success_count > 0) {
            $currentAvg = $record->avg_load_ms;
            $newAvg = (int) round(($currentAvg * ($record->success_count - 1) + $loadTimeMs) / $record->success_count);
            $record->update(['avg_load_ms' => $newAvg]);
        }
    }
}
