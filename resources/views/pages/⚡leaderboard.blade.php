<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Leaderboard — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'reviewers';

    public function with(): array
    {
        $topReviewers = User::withCount('reviews')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_count')
            ->limit(20)
            ->get();

        $mostActive = User::withCount('watchHistory')
            ->having('watch_history_count', '>', 0)
            ->orderByDesc('watch_history_count')
            ->limit(20)
            ->get();

        $topCollectors = User::withCount('collections')
            ->having('collections_count', '>', 0)
            ->orderByDesc('collections_count')
            ->limit(20)
            ->get();

        return [
            'topReviewers' => $topReviewers,
            'mostActive' => $mostActive,
            'topCollectors' => $topCollectors,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-bold">Leaderboard</h1>

        <div class="mb-6 flex gap-2">
            <button wire:click="$set('tab', 'reviewers')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'reviewers' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Top Reviewers
            </button>
            <button wire:click="$set('tab', 'watchers')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'watchers' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Most Active
            </button>
            <button wire:click="$set('tab', 'collectors')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'collectors' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Top Collectors
            </button>
        </div>

        @php
            $list = match($tab) {
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
            @foreach($list as $index => $user)
                <a href="{{ route('user.profile', $user->id) }}"
                   class="flex items-center gap-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-zinc-700" wire:navigate>
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold
                        {{ $index === 0 ? 'bg-amber-600 text-white' : ($index === 1 ? 'bg-zinc-500 text-white' : ($index === 2 ? 'bg-amber-800 text-white' : 'bg-zinc-800 text-zinc-400')) }}">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-600/20 text-sm font-bold text-amber-400">
                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-200">{{ $user->name }}</p>
                        <p class="text-xs text-zinc-500">Joined {{ $user->created_at->format('M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-amber-400">{{ $user->$countField }}</p>
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if($list->isEmpty())
            <p class="text-center text-sm text-zinc-500">No data yet. Be the first!</p>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
