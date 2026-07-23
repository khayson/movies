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
        <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-xl font-bold text-white shadow-lg shadow-amber-600/20 ring-2 ring-amber-500/20">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Welcome back, {{ Str::words(auth()->user()->name, 1, '') }}</h1>
                    <p class="mt-0.5 text-sm text-zinc-500">Member since {{ auth()->user()->created_at->format('F Y') }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                Settings
            </a>
        </div>

        {{-- Stats cards --}}
        <div class="mb-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Titles Watched</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $totalWatched }}</p>
                <div class="mt-2 h-1 w-12 rounded-full bg-gradient-to-r from-amber-500 to-amber-600"></div>
            </div>
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Hours Watched</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $totalHoursWatched }}</p>
                <div class="mt-2 h-1 w-12 rounded-full bg-gradient-to-r from-blue-500 to-blue-600"></div>
            </div>
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Favorites</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $favorites->count() }}</p>
                <div class="mt-2 h-1 w-12 rounded-full bg-gradient-to-r from-red-500 to-red-600"></div>
            </div>
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">Watchlist</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $watchlistItems->count() }}</p>
                <div class="mt-2 h-1 w-12 rounded-full bg-gradient-to-r from-purple-500 to-purple-600"></div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="{{ route('movies.index') }}" class="group rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.1] hover:bg-white/[0.04]" wire:navigate>
                <div class="mb-3 flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 transition group-hover:bg-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-2.625 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 0 1 6 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25c0 .621.504 1.125 1.125 1.125M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" /></svg>
                </div>
                <p class="text-sm font-semibold text-white">Movies</p>
                <p class="mt-0.5 text-xs text-zinc-500">Browse all movies</p>
            </a>
            <a href="{{ route('tv.index') }}" class="group rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.1] hover:bg-white/[0.04]" wire:navigate>
                <div class="mb-3 flex size-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400 transition group-hover:bg-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125Z" /></svg>
                </div>
                <p class="text-sm font-semibold text-white">TV Shows</p>
                <p class="mt-0.5 text-xs text-zinc-500">Browse all series</p>
            </a>
            <a href="{{ route('genres.index') }}" class="group rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.1] hover:bg-white/[0.04]" wire:navigate>
                <div class="mb-3 flex size-10 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 transition group-hover:bg-purple-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
                </div>
                <p class="text-sm font-semibold text-white">Genres</p>
                <p class="mt-0.5 text-xs text-zinc-500">Browse by genre</p>
            </a>
            <a href="{{ route('search') }}" class="group rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.1] hover:bg-white/[0.04]" wire:navigate>
                <div class="mb-3 flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 transition group-hover:bg-emerald-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <p class="text-sm font-semibold text-white">Search</p>
                <p class="mt-0.5 text-xs text-zinc-500">Find anything</p>
            </a>
        </div>

        {{-- Watch activity breakdown --}}
        @if($totalWatched > 0)
            <section class="mb-10">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold tracking-tight">
                    <span class="h-5 w-1 rounded-full bg-blue-500"></span>
                    Your Activity
                </h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5">
                        <h3 class="mb-4 text-xs font-medium uppercase tracking-widest text-zinc-500">Movies vs TV Shows</h3>
                        <div class="flex items-end gap-6">
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="text-zinc-300">Movies</span>
                                    <span class="font-bold tabular-nums text-amber-400">{{ $movieCount }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white/[0.06]">
                                    <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-500" style="width: {{ $totalWatched > 0 ? ($movieCount / $totalWatched) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="text-zinc-300">TV Shows</span>
                                    <span class="font-bold tabular-nums text-blue-400">{{ $tvCount }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white/[0.06]">
                                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500" style="width: {{ $totalWatched > 0 ? ($tvCount / $totalWatched) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5">
                        <h3 class="mb-4 text-xs font-medium uppercase tracking-widest text-zinc-500">Watch Time</h3>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold tabular-nums text-white">{{ $totalHoursWatched }}</span>
                            <span class="text-sm text-zinc-500">hours total</span>
                        </div>
                        <div class="mt-3 flex gap-4 text-xs text-zinc-500">
                            <span>~{{ round($totalHoursWatched / max(1, (int) now()->diffInDays(auth()->user()->created_at) ?: 1), 1) }} hrs/day avg</span>
                            <span>&bull; {{ $totalWatched }} titles</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Continue Watching --}}
        @if($watchHistory->isNotEmpty())
            <section class="mb-10">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xl font-bold tracking-tight">
                        <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                        Continue Watching
                    </h2>
                    <button wire:click="clearHistory" wire:confirm="Clear all watch history?" class="rounded-lg border border-white/[0.06] px-3 py-1.5 text-xs text-zinc-500 transition hover:border-white/[0.1] hover:text-zinc-300">
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
                            <div class="relative aspect-[2/3] overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-800">
                                @if($item->poster_path)
                                    <img src="{{ app(Tmdb::class)->imageUrl($item->poster_path, 'w342') }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                @endif
                                @if($item->duration_seconds > 0)
                                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/[0.1]">
                                        <div class="h-full bg-amber-500" style="width: {{ $item->progressPercent() }}%"></div>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-600/90 text-white shadow-lg shadow-amber-600/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <span class="absolute left-2 top-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300 backdrop-blur-sm">{{ $item->media_type }}</span>
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
                <h2 class="flex items-center gap-2 text-xl font-bold tracking-tight">
                    <span class="h-5 w-1 rounded-full bg-purple-500"></span>
                    My Watchlist
                    <span class="ml-1 text-sm font-normal text-zinc-500">({{ $watchlistItems->count() }})</span>
                </h2>
            </div>
            @if($watchlistItems->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($watchlistItems as $wl)
                        @php $detailRoute = $wl->media_type === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                        <div class="group relative flex gap-4 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-3 transition hover:border-white/[0.1] hover:bg-white/[0.04]">
                            <a href="{{ route($detailRoute, $wl->tmdb_id) }}" class="shrink-0" wire:navigate>
                                <div class="h-28 w-20 overflow-hidden rounded-xl bg-zinc-800">
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
                                            <span>&bull; {{ Str::substr($wl->release_date, 0, 4) }}</span>
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
                                    <a href="{{ route('watch', ['type' => $wl->media_type, 'tmdbId' => $wl->tmdb_id]) }}" class="inline-flex items-center gap-1 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-3 py-1.5 text-xs font-medium text-white shadow-lg shadow-amber-600/20 transition hover:from-amber-500 hover:to-amber-600" wire:navigate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        Watch
                                    </a>
                                    <button wire:click="removeFromWatchlist({{ $wl->id }})" class="rounded-xl border border-white/[0.08] bg-white/[0.03] px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-500/30 hover:text-red-400" title="Remove">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] px-6 py-12 text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-white/[0.04]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-7 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                    </div>
                    <p class="text-sm text-zinc-500">Your watchlist is empty. Add movies and shows you want to watch later.</p>
                    <a href="{{ route('movies.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white" wire:navigate>
                        Discover Content
                    </a>
                </div>
            @endif
        </section>

        {{-- Favorites --}}
        <section class="mb-10">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-xl font-bold tracking-tight">
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
                                <div class="relative aspect-[2/3] overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-800">
                                    @if($fav->poster_path)
                                        <img src="{{ app(Tmdb::class)->imageUrl($fav->poster_path, 'w342') }}" alt="{{ $fav->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                    @endif
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                        <div class="flex size-12 items-center justify-center rounded-full bg-amber-600/90 text-white shadow-lg shadow-amber-600/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                    <span class="absolute left-2 top-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300 backdrop-blur-sm">{{ $fav->media_type }}</span>
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
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] px-6 py-12 text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-white/[0.04]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-7 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-zinc-500">No favorites yet. Browse and add movies or shows you love.</p>
                    <a href="{{ route('movies.index') }}" class="mt-4 inline-flex rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-amber-600/20 transition hover:from-amber-500 hover:to-amber-600" wire:navigate>
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
