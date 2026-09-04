<?php

namespace App\Services;

use App\Support\UserPreferences;

class ProviderScorer
{
    /** @var array<string, int> Base provider reliability scores (higher = better) */
    public const PROVIDER_SCORES = [
        'CineSrc Direct' => 88,
        'VidCore' => 86,
        'VidSrc' => 85,
        'VidPhantom' => 84,
        'CineSrc' => 83,
        'VidLink' => 82,
        'VidBinge' => 81,
        'EzVidAPI' => 80,
        'AutoEmbed' => 79,
        'MoviesAPI' => 78,
        'SuperEmbed' => 77,
        'Embed API' => 76,
        'VikingEmbed' => 75,
    ];

    /** Score band within which providers rotate as defaults for load balancing. */
    private const DIVERSITY_SCORE_BAND = 6;

    /** @var array<int, string> */
    private const PLAYABLE_TYPES = ['embed', 'hls'];

    public function __construct(
        private ProviderAnalyticsTracker $analytics,
        private ProviderHealthProbe $healthProbe,
    ) {}

    /**
     * @param  array<int, array{type: string, url: string, quality: string, provider: string}>  $sources
     */
    public function recommend(
        array $sources,
        int $tmdbId,
        string $mediaType,
        ?int $season,
        ?int $episode,
        ?string $excludeProvider = null,
    ): int {
        if (count($sources) === 0) {
            return 0;
        }

        $prefs = auth()->check() ? (auth()->user()->preferences ?? []) : [];
        $rememberLastServer = UserPreferences::bool($prefs, 'remember_last_server', true);
        $useProviderScores = UserPreferences::bool($prefs, 'use_provider_scores', true);
        $preferHlsDirect = UserPreferences::bool($prefs, 'prefer_hls_direct', false);

        $userLastServer = null;
        $defaultSource = null;

        if (auth()->check()) {
            $user = auth()->user();
            if ($rememberLastServer) {
                $history = $user->watchHistory()
                    ->where('tmdb_id', $tmdbId)
                    ->where('media_type', $mediaType)
                    ->first();
                $userLastServer = $history?->last_server;
            }
            $defaultSource = $user->preferences['default_source'] ?? null;
        }

        $hasUserPreference = (is_string($defaultSource) && $defaultSource !== '')
            || (is_string($userLastServer) && $userLastServer !== '');

        $failedProviders = $this->analytics->failedProviders();
        $hourlyScores = $useProviderScores ? $this->analytics->hourlyScores() : [];
        $regionScores = $useProviderScores ? $this->analytics->regionScores() : [];
        $usageBoosts = $useProviderScores ? $this->analytics->underutilizedBoosts() : [];

        /** @var array<int, array{index: int, score: int, provider: string}> $candidates */
        $candidates = [];

        foreach ($sources as $i => $source) {
            if (! in_array($source['type'], self::PLAYABLE_TYPES, true)) {
                continue;
            }

            $provider = $source['provider'] ?? '';

            if ($excludeProvider !== null && $provider === $excludeProvider) {
                continue;
            }

            if (! $useProviderScores) {
                $score = 100 - $i;
            } else {
                $score = self::PROVIDER_SCORES[$provider] ?? 50;

                if ($provider === $userLastServer) {
                    $score += 12;
                }

                if ($provider === $defaultSource) {
                    $score += 12;
                }

                if ($preferHlsDirect && $provider === 'CineSrc Direct') {
                    $score += 15;
                }

                if (isset($hourlyScores[$provider])) {
                    $score += $hourlyScores[$provider];
                }

                if (isset($regionScores[$provider])) {
                    $score += $regionScores[$provider];
                }

                if (isset($usageBoosts[$provider])) {
                    $score += $usageBoosts[$provider];
                }

                if (isset($failedProviders[$provider])) {
                    $failedAt = $failedProviders[$provider];
                    $minutesAgo = (time() - $failedAt) / 60;
                    $score -= max(0, (int) (30 - $minutesAgo));
                }

                if ($this->healthProbe->isHealthy($provider) === false) {
                    $score -= 35;
                }
            }

            if ($preferHlsDirect && ! $useProviderScores && $provider === 'CineSrc Direct') {
                $score += 50;
            }

            $candidates[] = ['index' => $i, 'score' => $score, 'provider' => $provider];
        }

        if ($candidates === []) {
            return 0;
        }

        usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        if (! $useProviderScores || $hasUserPreference || $preferHlsDirect) {
            return $candidates[0]['index'];
        }

        return $this->pickBalancedCandidate($candidates, $tmdbId, $mediaType, $season, $episode);
    }

    /**
     * @param  array<int, array{index: int, score: int, provider: string}>  $candidates
     */
    private function pickBalancedCandidate(array $candidates, int $tmdbId, string $mediaType, ?int $season, ?int $episode): int
    {
        $topScore = $candidates[0]['score'];
        $tier = array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => $candidate['score'] >= $topScore - self::DIVERSITY_SCORE_BAND,
        ));

        if (count($tier) === 1) {
            return $tier[0]['index'];
        }

        $seed = crc32("{$tmdbId}.{$mediaType}.{$season}.{$episode}");

        usort($tier, fn (array $a, array $b): int => crc32($a['provider'].$seed) <=> crc32($b['provider'].$seed));

        return $tier[0]['index'];
    }
}
