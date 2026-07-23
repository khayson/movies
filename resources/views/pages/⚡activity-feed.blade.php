<?php

use App\Models\Activity;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Activity Feed — StreamVault')]
class extends Component
{
    #[Url]
    public string $filter = 'following';

    public function with(): array
    {
        $user = auth()->user();

        if ($this->filter === 'following' && $user) {
            $followingIds = $user->following()->pluck('users.id');
            $activities = Activity::whereIn('user_id', $followingIds)
                ->with('user')
                ->latest()
                ->limit(50)
                ->get();
        } else {
            $activities = Activity::with('user')
                ->latest()
                ->limit(50)
                ->get();
        }

        return [
            'activities' => $activities,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Activity Feed</h1>
            <div class="flex gap-2">
                <button wire:click="$set('filter', 'following')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'following' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700' }}">
                    Following
                </button>
                <button wire:click="$set('filter', 'everyone')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'everyone' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700' }}">
                    Everyone
                </button>
            </div>
        </div>

        @if($activities->isEmpty())
            <div class="py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 size-12 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-zinc-500">No activity yet</p>
                @if($filter === 'following')
                    <p class="mt-1 text-sm text-zinc-600">Follow other users to see their activity here.</p>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach($activities as $activity)
                    <div class="flex items-start gap-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
                        @if($activity->poster_path)
                            <img src="https://image.tmdb.org/t/p/w92{{ $activity->poster_path }}"
                                 alt="" class="h-16 w-11 flex-shrink-0 rounded-lg object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('user.profile', $activity->user_id) }}" class="text-sm font-semibold text-amber-400 hover:underline" wire:navigate>
                                    {{ $activity->user->name }}
                                </a>
                                <span class="text-sm text-zinc-400">{{ $activity->description }}</span>
                            </div>
                            @if($activity->title)
                                @if($activity->tmdb_id && $activity->media_type)
                                    <a href="{{ route($activity->media_type === 'movie' ? 'movies.detail' : 'tv.detail', $activity->tmdb_id) }}"
                                       class="mt-1 block text-sm font-medium text-zinc-200 hover:text-amber-400" wire:navigate>
                                        {{ $activity->title }}
                                    </a>
                                @else
                                    <p class="mt-1 text-sm font-medium text-zinc-200">{{ $activity->title }}</p>
                                @endif
                            @endif
                            <p class="mt-1 text-xs text-zinc-600">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            @php
                                $typeIcons = [
                                    'review' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />',
                                    'favorite' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />',
                                    'watchlist' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />',
                                    'collection' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />',
                                    'follow' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />',
                                ];
                                $icon = $typeIcons[$activity->type] ?? $typeIcons['review'];
                            @endphp
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $icon !!}</svg>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
