<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Dashboard — StreamVault')]
class extends Component
{
    public function removeFavorite(int $favoriteId): void
    {
        auth()->user()->favorites()->where('id', $favoriteId)->delete();
    }

    public function removeFromWatchlist(int $watchlistId): void
    {
        auth()->user()->watchlist()->where('id', $watchlistId)->delete();
    }

    public function clearHistory(): void
    {
        auth()->user()->watchHistory()->delete();
    }

    public function with(Tmdb $tmdb): array
    {
        $user = auth()->user();
        $favorites = $user->favorites()->latest()->get();
        $watchHistory = $user->watchHistory()->latest('updated_at')->take(12)->get();
        $watchlistItems = $user->watchlist()->latest()->get();

        $totalWatched = $user->watchHistory()->count();
        $totalHoursWatched = round($user->watchHistory()->sum('duration_seconds') / 3600, 1);
        $movieCount = $user->watchHistory()->where('media_type', 'movie')->count();
        $tvCount = $user->watchHistory()->where('media_type', 'tv')->count();

        $recommendations = [];
        if ($watchHistory->isNotEmpty()) {
            $lastWatched = $watchHistory->first();

            try {
                $details = $tmdb->details($lastWatched->media_type, $lastWatched->tmdb_id);
                $recommendations = array_slice($details['recommendations']['results'] ?? $details['similar']['results'] ?? [], 0, 12);
            } catch (\Throwable) {
            }
        }

        $prefs = $user->preferences ?? [];
        $preferredType = $prefs['preferred_type'] ?? 'all';
        $trendingType = $preferredType === 'all' ? 'all' : ($preferredType === 'tv' ? 'tv' : 'movie');

        $trending = [];

        try {
            $trending = array_slice($tmdb->trending($trendingType, 'day')['results'] ?? [], 0, 12);
        } catch (\Throwable) {
        }

        return [
            'favorites' => $favorites,
            'watchHistory' => $watchHistory,
            'watchlistItems' => $watchlistItems,
            'recommendations' => $recommendations,
            'trending' => $trending,
            'totalWatched' => $totalWatched,
            'totalHoursWatched' => $totalHoursWatched,
            'movieCount' => $movieCount,
            'tvCount' => $tvCount,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Welcome header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-xl font-bold text-white shadow-lg shadow-amber-600/20">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl">Welcome back, {{ auth()->user()->name }}</h1>
                    <p class="mt-0.5 text-sm text-zinc-400">Member since {{ auth()->user()->created_at->format('F Y') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800/50 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:border-zinc-600 hover:bg-zinc-700 hover:text-white" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    Settings
                </a>
            </div>
        </div>

        {{-- Stats cards --}}
        <div class="mb-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-amber-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tabular-nums">{{ $totalWatched }}</p>
                        <p class="text-xs text-zinc-500">Titles Watched</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-blue-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tabular-nums">{{ $totalHoursWatched }}</p>
                        <p class="text-xs text-zinc-500">Hours Watched</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-red-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tabular-nums">{{ $favorites->count() }}</p>
                        <p class="text-xs text-zinc-500">Favorites</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-purple-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold tabular-nums">{{ $watchlistItems->count() }}</p>
                        <p class="text-xs text-zinc-500">Watchlist</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="{{ route('movies.index') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-amber-600/30 hover:bg-amber-600/5" wire:navigate>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-amber-600/10 transition group-hover:bg-amber-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">Movies</p>
                    <p class="text-xs text-zinc-500">Browse all</p>
                </div>
            </a>
            <a href="{{ route('tv.index') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-blue-600/30 hover:bg-blue-600/5" wire:navigate>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-600/10 transition group-hover:bg-blue-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">TV Shows</p>
                    <p class="text-xs text-zinc-500">Browse all</p>
                </div>
            </a>
            <a href="{{ route('genres.index') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-purple-600/30 hover:bg-purple-600/5" wire:navigate>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-600/10 transition group-hover:bg-purple-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">Genres</p>
                    <p class="text-xs text-zinc-500">Explore genres</p>
                </div>
            </a>
            <a href="{{ route('search') }}" class="group flex items-center gap-3 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-green-600/30 hover:bg-green-600/5" wire:navigate>
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-green-600/10 transition group-hover:bg-green-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-white">Search</p>
                    <p class="text-xs text-zinc-500">Find anything</p>
                </div>
            </a>
        </div>

        {{-- Watch activity breakdown --}}
        @if($totalWatched > 0)
            <section class="mb-10">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-blue-500"></span>
                    Your Activity
                </h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-zinc-400">Movies vs TV Shows</h3>
                        </div>
                        <div class="flex items-end gap-6">
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="text-zinc-300">Movies</span>
                                    <span class="font-bold tabular-nums text-amber-400">{{ $movieCount }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-zinc-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-500" style="width: {{ $totalWatched > 0 ? ($movieCount / $totalWatched) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="text-zinc-300">TV Shows</span>
                                    <span class="font-bold tabular-nums text-blue-400">{{ $tvCount }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-zinc-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500" style="width: {{ $totalWatched > 0 ? ($tvCount / $totalWatched) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
                        <h3 class="mb-3 text-sm font-medium text-zinc-400">Watch Time</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold tabular-nums text-white">{{ $totalHoursWatched }}</span>
                            <span class="text-sm text-zinc-500">hours total</span>
                        </div>
                        <div class="mt-3 flex gap-4 text-xs text-zinc-500">
                            <span>~{{ round($totalHoursWatched / max(1, (int) now()->diffInDays(auth()->user()->created_at) ?: 1), 1) }} hrs/day avg</span>
                            <span>{{ $totalWatched }} titles</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Continue Watching --}}
        @if($watchHistory->isNotEmpty())
            <section class="mb-10">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xl font-bold">
                        <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                        Continue Watching
                    </h2>
                    <button wire:click="clearHistory" wire:confirm="Clear all watch history?" class="text-xs text-zinc-500 transition hover:text-zinc-300">
                        Clear History
                    </button>
                </div>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($watchHistory as $item)
                        @php
                            $watchRoute = $item->media_type === 'tv'
                                ? route('watch', ['type' => 'tv', 'tmdbId' => $item->tmdb_id, 'season' => $item->season ?? 1, 'episode' => $item->episode ?? 1])
                                : route('watch', ['type' => 'movie', 'tmdbId' => $item->tmdb_id]);
                        @endphp
                        <a href="{{ $watchRoute }}" class="group w-36 shrink-0 sm:w-40" wire:navigate>
                            <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800">
                                @if($item->poster_path)
                                    <img src="{{ app(Tmdb::class)->imageUrl($item->poster_path, 'w342') }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                @endif
                                @if($item->duration_seconds > 0)
                                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-zinc-700">
                                        <div class="h-full bg-amber-500" style="width: {{ $item->progressPercent() }}%"></div>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-600/90 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <span class="absolute left-2 top-2 rounded bg-zinc-900/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300">{{ $item->media_type }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-zinc-300 transition group-hover:text-white">{{ Str::limit($item->title, 25) }}</h3>
                            <p class="text-xs text-zinc-500">
                                {{ $item->media_type === 'tv' && $item->season ? 'S'.$item->season.' E'.$item->episode : ucfirst($item->media_type) }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Watchlist --}}
        <section class="mb-10">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-purple-500"></span>
                    My Watchlist
                    <span class="ml-1 text-sm font-normal text-zinc-500">({{ $watchlistItems->count() }})</span>
                </h2>
            </div>
            @if($watchlistItems->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($watchlistItems as $wl)
                        @php $detailRoute = $wl->media_type === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                        <div class="group relative flex gap-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-3 transition hover:border-zinc-700 hover:bg-zinc-800/50">
                            <a href="{{ route($detailRoute, $wl->tmdb_id) }}" class="shrink-0" wire:navigate>
                                <div class="h-28 w-20 overflow-hidden rounded-lg bg-zinc-800">
                                    @if($wl->poster_path)
                                        <img src="{{ app(Tmdb::class)->imageUrl($wl->poster_path, 'w185') }}" alt="{{ $wl->title }}" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>
                            </a>
                            <div class="flex min-w-0 flex-1 flex-col justify-between">
                                <div>
                                    <a href="{{ route($detailRoute, $wl->tmdb_id) }}" class="block font-medium text-zinc-200 transition hover:text-white" wire:navigate>
                                        {{ Str::limit($wl->title, 35) }}
                                    </a>
                                    <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500">
                                        <span class="uppercase">{{ $wl->media_type }}</span>
                                        @if($wl->release_date)
                                            <span>{{ Str::substr($wl->release_date, 0, 4) }}</span>
                                        @endif
                                        @if($wl->vote_average > 0)
                                            <span class="flex items-center gap-0.5 text-amber-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                {{ number_format($wl->vote_average, 1) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($wl->overview)
                                        <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-zinc-500">{{ $wl->overview }}</p>
                                    @endif
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <a href="{{ route('watch', ['type' => $wl->media_type, 'tmdbId' => $wl->tmdb_id]) }}" class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-500" wire:navigate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        Watch
                                    </a>
                                    <button wire:click="removeFromWatchlist({{ $wl->id }})" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:text-red-400" title="Remove">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 px-6 py-10 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 size-10 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    <p class="text-sm text-zinc-500">Your watchlist is empty. Add movies and shows you want to watch later.</p>
                    <a href="{{ route('movies.index') }}" class="mt-4 inline-block rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700 hover:text-white" wire:navigate>
                        Discover Content
                    </a>
                </div>
            @endif
        </section>

        {{-- Favorites --}}
        <section class="mb-10">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-red-500"></span>
                    My Favorites
                    <span class="ml-1 text-sm font-normal text-zinc-500">({{ $favorites->count() }})</span>
                </h2>
            </div>
            @if($favorites->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach($favorites as $fav)
                        @php $detailRoute = $fav->media_type === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                        <div class="group relative">
                            <a href="{{ route($detailRoute, $fav->tmdb_id) }}" class="block" wire:navigate>
                                <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800">
                                    @if($fav->poster_path)
                                        <img src="{{ app(Tmdb::class)->imageUrl($fav->poster_path, 'w342') }}" alt="{{ $fav->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                        <div class="flex size-12 items-center justify-center rounded-full bg-amber-600/90 text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                    <span class="absolute left-2 top-2 rounded bg-zinc-900/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300">{{ $fav->media_type }}</span>
                                </div>
                                <h3 class="mt-2 text-sm font-medium text-zinc-300 transition group-hover:text-white">{{ Str::limit($fav->title, 28) }}</h3>
                            </a>
                            <button
                                wire:click="removeFavorite({{ $fav->id }})"
                                class="absolute right-2 top-2 rounded-full bg-black/60 p-1.5 text-zinc-400 opacity-0 backdrop-blur-sm transition hover:text-red-400 group-hover:opacity-100"
                                title="Remove from favorites"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 px-6 py-10 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 size-10 text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <p class="text-sm text-zinc-500">No favorites yet. Browse and add movies or shows you love.</p>
                    <a href="{{ route('movies.index') }}" class="mt-4 inline-block rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500" wire:navigate>
                        Browse Movies
                    </a>
                </div>
            @endif
        </section>

        {{-- Recommendations --}}
        @if(count($recommendations) > 0)
            @include('partials.media-row', ['title' => 'Recommended for You', 'items' => $recommendations, 'style' => 'scroll'])
        @endif

        {{-- Trending Today --}}
        @if(count($trending) > 0)
            <div class="mt-10">
                @include('partials.media-row', ['title' => 'Trending Today', 'items' => $trending, 'style' => 'scroll'])
            </div>
        @endif
    </div>
</div>
