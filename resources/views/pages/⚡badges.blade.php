<?php

use App\Services\BadgeService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Badges — StreamVault')]
class extends Component
{
    public function with(BadgeService $badgeService): array
    {
        $user = auth()->user();
        $badgeService->checkAndAward($user);

        $earnedBadges = $user->badges()->get()->keyBy('badge_key');

        /** @var array<string, array{name: string, description: string, icon: string}> $allBadges */
        $allBadges = config('badges');

        $badges = collect($allBadges)->map(function (array $badge, string $key) use ($earnedBadges) {
            $earned = $earnedBadges->get($key);

            return [
                ...$badge,
                'key' => $key,
                'earned' => $earned !== null,
                'earned_at' => $earned?->earned_at,
            ];
        });

        $earnedCount = $badges->where('earned', true)->count();

        return [
            'badges' => $badges,
            'earnedCount' => $earnedCount,
            'totalCount' => $badges->count(),
            'progressPercent' => $badges->count() > 0 ? round(($earnedCount / $badges->count()) * 100) : 0,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-amber-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-4xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-amber-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400/80">Achievements</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Badges</h1>
            <p class="mt-2 text-sm text-zinc-400">Earn badges by watching, reviewing, and curating content.</p>

            {{-- Progress bar --}}
            <div class="mt-6 max-w-md">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-zinc-400">{{ $earnedCount }} of {{ $totalCount }} earned</span>
                    <span class="font-bold tabular-nums text-amber-400">{{ $progressPercent }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-white/[0.06]">
                    <div class="h-full rounded-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all duration-500"
                         style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($badges as $badge)
                <div class="relative overflow-hidden rounded-2xl border p-5 transition
                    {{ $badge['earned'] ? 'border-amber-500/20 bg-amber-500/[0.04]' : 'border-white/[0.06] bg-white/[0.02] opacity-60' }}">
                    @if($badge['earned'])
                        <div class="absolute right-3 top-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                    <div class="flex items-start gap-4">
                        <span class="text-3xl {{ $badge['earned'] ? '' : 'grayscale' }}">{{ $badge['icon'] }}</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold {{ $badge['earned'] ? 'text-amber-300' : 'text-zinc-400' }}">{{ $badge['name'] }}</h3>
                            <p class="mt-0.5 text-sm text-zinc-500">{{ $badge['description'] }}</p>
                            @if($badge['earned'] && $badge['earned_at'])
                                <p class="mt-2 text-xs text-amber-500/60">Earned {{ $badge['earned_at']->format('M d, Y') }}</p>
                            @else
                                <p class="mt-2 flex items-center gap-1 text-xs text-zinc-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    Locked
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
