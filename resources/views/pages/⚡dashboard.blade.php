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

    public function clearHistory(): void
    {
        auth()->user()->watchHistory()->delete();
    }

    public function with(Tmdb $tmdb): array
    {
        $user = auth()->user();
        $favorites = $user->favorites()->latest()->get();
        $watchHistory = $user->watchHistory()->latest('updated_at')->take(12)->get();

        $recommendations = [];
        if ($watchHistory->isNotEmpty()) {
            $lastWatched = $watchHistory->first();

            try {
                $details = $tmdb->details($lastWatched->media_type, $lastWatched->tmdb_id);
                $recommendations = array_slice($details['recommendations']['results'] ?? $details['similar']['results'] ?? [], 0, 12);
            } catch (\Throwable) {
            }
        }

        return [
            'favorites' => $favorites,
            'watchHistory' => $watchHistory,
            'recommendations' => $recommendations,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Welcome header --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="mt-1 text-sm text-zinc-400">Your personal streaming dashboard</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-700 hover:text-white" wire:navigate>
                Settings
            </a>
        </div>

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
                        <a href="{{ $watchRoute }}" class="group w-36 shrink-0 sm:w-40">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800">
                                @if($item->poster_path)
                                    <img src="{{ app(Tmdb::class)->imageUrl($item->poster_path, 'w342') }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                @endif
                                {{-- Progress bar --}}
                                @if($item->duration_seconds > 0)
                                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-zinc-700">
                                        <div class="h-full bg-amber-500" style="width: {{ $item->progressPercent() }}%"></div>
                                    </div>
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-600/90 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
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

        {{-- Favorites --}}
        <section class="mb-10">
            <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                <span class="h-5 w-1 rounded-full bg-red-500"></span>
                My Favorites
                <span class="ml-1 text-sm font-normal text-zinc-500">({{ $favorites->count() }})</span>
            </h2>
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
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 px-6 py-12 text-center">
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
    </div>
</div>
