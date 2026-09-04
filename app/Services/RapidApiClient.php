<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class RapidApiClient
{
    private const CIRCUIT_FAILURE_THRESHOLD = 5;

    private const CIRCUIT_COOLDOWN_SECONDS = 300;

    public function configured(): bool
    {
        return filled(config('services.rapidapi.key'));
    }

    public function rateLimited(?string $userBucket = 'rapidapi-per-user'): bool
    {
        if (RateLimiter::tooManyAttempts('rapidapi', 450)) {
            return true;
        }

        if ($userBucket !== null && RateLimiter::tooManyAttempts($userBucket, 30)) {
            return true;
        }

        return false;
    }

    public function hit(?string $userBucket = 'rapidapi-per-user'): void
    {
        RateLimiter::hit('rapidapi', 60 * 60 * 24 * 30);

        if ($userBucket !== null) {
            RateLimiter::hit($userBucket, 60 * 60);
        }
    }

    public function circuitOpen(string $host): bool
    {
        $state = Cache::get($this->circuitCacheKey($host));

        if (! is_array($state)) {
            return false;
        }

        $openedUntil = $state['opened_until'] ?? null;

        return is_numeric($openedUntil) && now()->getTimestamp() < (int) $openedUntil;
    }

    public function recordSuccess(string $host): void
    {
        Cache::forget($this->circuitCacheKey($host));
    }

    public function recordFailure(string $host): void
    {
        $key = $this->circuitCacheKey($host);
        /** @var array{failures?: int, opened_until?: int} $state */
        $state = Cache::get($key, []);
        $failures = ((int) ($state['failures'] ?? 0)) + 1;

        if ($failures >= self::CIRCUIT_FAILURE_THRESHOLD) {
            Cache::put($key, [
                'failures' => $failures,
                'opened_until' => now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS)->getTimestamp(),
            ], now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS));

            return;
        }

        Cache::put($key, ['failures' => $failures], now()->addMinutes(10));
    }

    public function request(string $host, int $timeout = 10, int $connectTimeout = 3, bool $retry = false): PendingRequest
    {
        $request = Http::timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->acceptJson()
            ->withHeaders([
                'X-RapidAPI-Key' => (string) config('services.rapidapi.key'),
                'X-RapidAPI-Host' => $host,
            ]);

        if ($retry) {
            $request = $request->retry(2, 200, function (Throwable $exception, PendingRequest $pendingRequest): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            });
        }

        return $request;
    }

    public function get(
        string $host,
        string $url,
        array $query = [],
        ?string $userBucket = 'rapidapi-per-user',
        int $timeout = 10,
        int $connectTimeout = 3,
        bool $retry = false,
    ): ?Response {
        if (! $this->configured() || $this->rateLimited($userBucket) || $this->circuitOpen($host)) {
            return null;
        }

        $this->hit($userBucket);

        try {
            $response = $this->request($host, $timeout, $connectTimeout, $retry)->get($url, $query);

            if ($response->successful()) {
                $this->recordSuccess($host);

                return $response;
            }

            if ($response->serverError() || $response->status() === 429) {
                $this->recordFailure($host);
            }

            return null;
        } catch (Throwable) {
            $this->recordFailure($host);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJson(
        string $host,
        string $url,
        array $query = [],
        ?string $userBucket = 'rapidapi-per-user',
        int $timeout = 10,
        int $connectTimeout = 3,
        bool $retry = false,
    ): ?array {
        $response = $this->get($host, $url, $query, $userBucket, $timeout, $connectTimeout, $retry);

        if ($response === null) {
            return null;
        }

        $json = $response->json();

        if (is_array($json)) {
            /** @var array<string, mixed> $json */
            return $json;
        }

        if (is_numeric($json)) {
            return ['value' => $json + 0];
        }

        return null;
    }

    private function circuitCacheKey(string $host): string
    {
        return 'rapidapi.circuit.'.md5($host);
    }
}
