<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.guest')]
#[Title('Leaderboard — StreamVault')]
class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'reviewers';

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $topReviewers = User::withCount('reviews')
            ->has('reviews')
            ->orderByDesc('reviews_count')
            ->paginate(20, pageName: 'reviewers');

        $mostActive = User::withCount(['watchHistory' => fn ($query) => $query->visible()])
            ->whereHas('watchHistory', fn ($query) => $query->visible())
            ->where(function ($query) {
                $query->whereNull('preferences->hide_from_leaderboard')
                    ->orWhere('preferences->hide_from_leaderboard', false);
            })
            ->orderByDesc('watch_history_count')
            ->paginate(20, pageName: 'watchers');

        $topCollectors = User::withCount('collections')
            ->has('collections')
            ->orderByDesc('collections_count')
            ->paginate(20, pageName: 'collectors');

        return [
            'topReviewers' => $topReviewers,
            'mostActive' => $mostActive,
            'topCollectors' => $topCollectors,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-amber-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-3xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-amber-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400/80">Community</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Leaderboard</h1>
            <p class="mt-2 text-sm text-zinc-400">Top contributors in the community</p>

            <div class="mt-8 flex gap-2">
                @foreach(['reviewers' => 'Top Reviewers', 'watchers' => 'Most Active', 'collectors' => 'Top Collectors'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                            class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === $key ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-3xl px-4 pb-16 sm:px-6 lg:px-8">
        @php
            $paginator = match($tab) {
                'watchers' => $mostActive,
                'collectors' => $topCollectors,
                default => $topReviewers,
            };
            $countField = match($tab) {
                'watchers' => 'watch_history_count',
                'collectors' => 'collections_count',
                default => 'reviews_count',
            };
            $label = match($tab) {
                'watchers' => 'watched',
                'collectors' => Str::plural('collection', 1),
                default => Str::plural('review', 1),
            };
        @endphp

        <div class="space-y-2">
            @foreach($paginator as $index => $user)
                @php $rank = ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1; @endphp
                <a href="{{ route('user.profile', $user->id) }}"
                   class="flex items-center gap-4 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.12] hover:bg-white/[0.04]" wire:navigate>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold
                        {{ $rank === 1 ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : ($rank === 2 ? 'bg-zinc-400 text-white' : ($rank === 3 ? 'bg-amber-800 text-white' : 'bg-white/[0.06] text-zinc-400')) }}">
                        {{ $rank }}
                    </span>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-600/20 text-sm font-bold text-amber-400">
                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-200">{{ $user->name }}</p>
                        <p class="text-xs text-zinc-500">Joined {{ $user->created_at->format('M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold tabular-nums text-amber-400">{{ $user->$countField }}</p>
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if($paginator->isEmpty())
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                <p class="text-zinc-500">No data yet. Be the first!</p>
            </div>
        @endif

        <div class="mt-8">
            {{ $paginator->links() }}
        </div>
    </div>
</div>
