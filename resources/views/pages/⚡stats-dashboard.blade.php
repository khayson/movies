<?php

use App\Models\Review;
use App\Models\WatchHistory;
use App\Services\ProviderAnalyticsTracker;
use App\Services\StreakService;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Your Stats — StreamVault')]
class extends Component
{
    public function with(StreakService $streakService, Tmdb $tmdb, ProviderAnalyticsTracker $analytics): array
    {
        $user = auth()->user();
        $streak = $streakService->calculate($user);

        $totalWatched = $user->watchHistory()->visible()->count();
        $totalReviews = $user->reviews()->count();
        $totalFavorites = $user->favorites()->count();
        $totalCollections = $user->collections()->count();

        $totalWatchTimeSeconds = $user->watchHistory()->visible()->sum('progress_seconds');
        $totalHours = round($totalWatchTimeSeconds / 3600, 1);

        $recentHistory = $user->watchHistory()->visible()->latest('updated_at')->take(20)->get();
        $genreCounts = [];
        foreach ($recentHistory as $item) {
            try {
                $type = $item->media_type === 'tv' ? 'tv' : 'movie';
                $details = $tmdb->details($type, $item->tmdb_id);
                foreach ($details['genres'] ?? [] as $genre) {
                    $genreCounts[$genre['name']] = ($genreCounts[$genre['name']] ?? 0) + 1;
                }
            } catch (\Throwable) {
                continue;
            }
        }
        arsort($genreCounts);
        $topGenres = array_slice($genreCounts, 0, 6, true);
        $maxGenreCount = max($topGenres ?: [1]);

        $ratingDistribution = array_fill(1, 10, 0);
        $userReviews = $user->reviews()->get(['rating']);
        foreach ($userReviews as $review) {
            $rating = max(1, min(10, $review->rating));
            $ratingDistribution[$rating]++;
        }
        $maxRating = max($ratingDistribution ?: [1]);
        $avgRating = $userReviews->count() > 0 ? round($userReviews->avg('rating'), 1) : 0;

        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->format('M');
            $count = $user->watchHistory()
                ->visible()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $monthlyData[] = ['label' => $monthLabel, 'count' => $count];
        }
        $maxMonthly = max(array_column($monthlyData, 'count') ?: [1]);

        $movieCount = $user->watchHistory()->visible()->where('media_type', 'movie')->count();
        $tvCount = $user->watchHistory()->visible()->where('media_type', 'tv')->count();

        $providerSlos = $analytics->sloSummary();

        return [
            'totalWatched' => $totalWatched,
            'totalReviews' => $totalReviews,
            'totalFavorites' => $totalFavorites,
            'totalCollections' => $totalCollections,
            'totalHours' => $totalHours,
            'streak' => $streak,
            'topGenres' => $topGenres,
            'maxGenreCount' => $maxGenreCount,
            'ratingDistribution' => $ratingDistribution,
            'maxRating' => $maxRating,
            'avgRating' => $avgRating,
            'monthlyData' => $monthlyData,
            'maxMonthly' => $maxMonthly,
            'movieCount' => $movieCount,
            'tvCount' => $tvCount,
            'providerSlos' => $providerSlos,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-violet-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-5xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-violet-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-400/80">Analytics</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Your Stats</h1>
            <p class="mt-2 text-sm text-zinc-400">A breakdown of your viewing and activity on StreamVault.</p>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Overview cards --}}
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach([
                ['label' => 'Watched', 'value' => $totalWatched, 'color' => 'text-violet-400'],
                ['label' => 'Hours', 'value' => $totalHours, 'color' => 'text-blue-400'],
                ['label' => 'Reviews', 'value' => $totalReviews, 'color' => 'text-amber-400'],
                ['label' => 'Favorites', 'value' => $totalFavorites, 'color' => 'text-rose-400'],
                ['label' => 'Collections', 'value' => $totalCollections, 'color' => 'text-emerald-400'],
                ['label' => 'Day Streak', 'value' => $streak['current'], 'color' => 'text-orange-400'],
            ] as $stat)
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 text-center">
                    <p class="text-2xl font-bold tabular-nums {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                    <p class="mt-0.5 text-xs font-medium text-zinc-500">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Monthly activity --}}
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400">Monthly Activity</h2>
                <div class="flex items-end justify-between gap-2" style="height: 120px;">
                    @foreach($monthlyData as $month)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-violet-500/80 transition-all"
                                 style="height: {{ $maxMonthly > 0 ? max(4, ($month['count'] / $maxMonthly) * 100) : 4 }}px;"></div>
                            <span class="text-[10px] text-zinc-500">{{ $month['label'] }}</span>
                            <span class="text-[10px] font-bold tabular-nums text-zinc-400">{{ $month['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Movies vs TV --}}
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400">Movies vs TV Shows</h2>
                @php
                    $total = $movieCount + $tvCount;
                    $moviePct = $total > 0 ? round(($movieCount / $total) * 100) : 50;
                    $tvPct = 100 - $moviePct;
                @endphp
                <div class="flex items-center gap-6">
                    <div class="flex-1">
                        <div class="mb-3 flex justify-between text-sm">
                            <span class="text-blue-400">Movies</span>
                            <span class="font-bold tabular-nums text-zinc-300">{{ $movieCount }}</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-white/[0.06]">
                            <div class="h-full rounded-full bg-blue-500 transition-all" style="width: {{ $moviePct }}%"></div>
                        </div>
                        <div class="mt-3 flex justify-between text-sm">
                            <span class="text-emerald-400">TV Shows</span>
                            <span class="font-bold tabular-nums text-zinc-300">{{ $tvCount }}</span>
                        </div>
                        <div class="mt-1 h-3 overflow-hidden rounded-full bg-white/[0.06]">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $tvPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top genres --}}
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400">Top Genres</h2>
                @if(count($topGenres) > 0)
                    <div class="space-y-3">
                        @foreach($topGenres as $genre => $count)
                            <div>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span class="text-zinc-300">{{ $genre }}</span>
                                    <span class="font-bold tabular-nums text-violet-400">{{ $count }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white/[0.06]">
                                    <div class="h-full rounded-full bg-violet-500/60 transition-all"
                                         style="width: {{ ($count / $maxGenreCount) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="py-8 text-center text-sm text-zinc-600">Watch more titles to see genre breakdown.</p>
                @endif
            </div>

            {{-- Rating distribution --}}
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-1 text-sm font-semibold uppercase tracking-wider text-zinc-400">Your Ratings</h2>
                @if($avgRating > 0)
                    <p class="mb-4 text-xs text-zinc-500">Average: <span class="font-bold text-amber-400">{{ $avgRating }}/10</span></p>
                @endif
                <div class="flex items-end justify-between gap-1" style="height: 100px;">
                    @foreach($ratingDistribution as $rating => $count)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <span class="text-[10px] font-bold tabular-nums text-zinc-400">{{ $count > 0 ? $count : '' }}</span>
                            <div class="w-full rounded-t bg-amber-500/70 transition-all"
                                 style="height: {{ $maxRating > 0 ? max(2, ($count / $maxRating) * 80) : 2 }}px;"></div>
                            <span class="text-[10px] text-zinc-500">{{ $rating }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Provider reliability (system SLO) --}}
        <div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Streaming server health</h2>
                    <p class="mt-1 text-xs text-zinc-500">Last 7 days of playback success across providers.</p>
                </div>
            </div>
            @if(count($providerSlos) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[480px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.06] text-[10px] uppercase tracking-wider text-zinc-500">
                                <th class="pb-2 pr-3 font-semibold">Provider</th>
                                <th class="pb-2 pr-3 font-semibold">Success</th>
                                <th class="pb-2 pr-3 font-semibold">Samples</th>
                                <th class="pb-2 pr-3 font-semibold">Failures</th>
                                <th class="pb-2 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-zinc-300">
                            @foreach($providerSlos as $row)
                                @php
                                    $rate = $row['success_rate'];
                                    $tone = $rate >= 85 ? 'text-emerald-400' : ($rate >= 60 ? 'text-amber-400' : 'text-red-400');
                                    $probe = $row['probe_healthy'];
                                    $status = $probe === false ? 'Down' : ($rate >= 85 ? 'Healthy' : ($rate >= 60 ? 'Degraded' : 'Poor'));
                                    $statusTone = $probe === false ? 'text-red-400' : $tone;
                                @endphp
                                <tr class="border-b border-white/[0.04]">
                                    <td class="py-2.5 pr-3 font-medium text-white">{{ $row['provider'] }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums {{ $tone }}">{{ $rate }}%</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-zinc-400">{{ $row['samples'] }}</td>
                                    <td class="py-2.5 pr-3 tabular-nums text-zinc-400">{{ $row['failures'] }}</td>
                                    <td class="py-2.5 font-medium {{ $statusTone }}">{{ $status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="py-6 text-center text-sm text-zinc-600">No playback analytics yet. Watch a few titles and this fills in automatically.</p>
            @endif
        </div>

        {{-- Streak calendar --}}
        @if(count($streak['active_days']) > 0)
            <div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400">Watch Streak (Last 90 Days)</h2>
                <div class="flex flex-wrap gap-1">
                    @for($i = 89; $i >= 0; $i--)
                        @php $day = now()->subDays($i)->format('Y-m-d'); @endphp
                        <div class="size-3 rounded-sm {{ in_array($day, $streak['active_days']) ? 'bg-violet-500' : 'bg-white/[0.04]' }}"
                             title="{{ $day }}"></div>
                    @endfor
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-zinc-500">
                    <span>Current: <strong class="text-zinc-300">{{ $streak['current'] }}</strong> {{ Str::plural('day', $streak['current']) }}</span>
                    <span>Longest: <strong class="text-zinc-300">{{ $streak['longest'] }}</strong> {{ Str::plural('day', $streak['longest']) }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
