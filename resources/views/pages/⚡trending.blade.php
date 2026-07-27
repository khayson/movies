<?php

use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Trending & Popular — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'trending';

    #[Url]
    public string $type = 'all';

    #[Url]
    public string $window = 'day';

    #[Url]
    public int $page = 1;

    public function updatedTab(): void
    {
        $this->page = 1;
    }

    public function updatedType(): void
    {
        $this->page = 1;
    }

    public function updatedWindow(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function with(Tmdb $tmdb): array
    {
        View::share('ogTitle', 'Trending & Popular — ' . config('app.name'));
        View::share('ogDescription', 'Discover what\'s trending and popular right now on ' . config('app.name') . '.');

        $results = match ($this->tab) {
            'popular' => $this->type === 'tv'
                ? $tmdb->popular('tv', $this->page)
                : $tmdb->popular('movie', $this->page),
            'top_rated' => $this->type === 'tv'
                ? $tmdb->topRated('tv', $this->page)
                : $tmdb->topRated('movie', $this->page),
            'now_playing' => $this->type === 'tv'
                ? $tmdb->airingToday($this->page)
                : $tmdb->nowPlaying($this->page),
            default => $tmdb->trending(
                $this->type === 'all' ? 'all' : $this->type,
                $this->window,
                $this->page,
            ),
        };

        $items = $results['results'] ?? [];
        $totalPages = min($results['total_pages'] ?? 1, 500);

        $movieGenres = $tmdb->genres('movie')['genres'] ?? [];
        $tvGenres = $tmdb->genres('tv')['genres'] ?? [];
        $genreMap = [];
        foreach (array_merge($movieGenres, $tvGenres) as $g) {
            $genreMap[$g['id']] = $g['name'];
        }

        return [
            'items' => $items,
            'totalPages' => $totalPages,
            'genreMap' => $genreMap,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="border-b border-white/[0.06] bg-gradient-to-b from-red-950/10 to-transparent">
        <div class="mx-auto max-w-7xl px-4 pb-6 pt-10 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                <span class="bg-gradient-to-r from-red-400 to-amber-400 bg-clip-text text-transparent">Trending & Popular</span>
            </h1>
            <p class="mt-2 text-sm text-zinc-500">Discover what everyone is watching right now</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Category tabs --}}
        <div class="scrollbar-hide mb-6 flex items-center gap-1 overflow-x-auto border-b border-white/[0.06] pb-px">
            @foreach(['trending' => 'Trending', 'popular' => 'Popular', 'top_rated' => 'Top Rated', 'now_playing' => 'Now Playing'] as $key => $label)
                <button
                    wire:click="$set('tab', '{{ $key }}')"
                    class="relative whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors {{ $tab === $key ? 'text-white' : 'text-zinc-500 hover:text-zinc-300' }}"
                >
                    {{ $label }}
                    @if($tab === $key)
                        <span class="absolute bottom-0 left-2 right-2 h-0.5 rounded-full bg-red-500"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Filters row --}}
        <div class="mb-8 flex flex-wrap items-center gap-3">
            {{-- Media type filter --}}
            <div class="flex items-center gap-1 rounded-lg border border-white/[0.06] bg-white/[0.02] p-0.5">
                @if($tab === 'trending')
                    <button wire:click="$set('type', 'all')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $type === 'all' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300' }}">All</button>
                @endif
                <button wire:click="$set('type', 'movie')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $type === 'movie' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300' }}">Movies</button>
                <button wire:click="$set('type', 'tv')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $type === 'tv' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300' }}">TV Shows</button>
            </div>

            {{-- Time window (trending only) --}}
            @if($tab === 'trending')
                <div class="flex items-center gap-1 rounded-lg border border-white/[0.06] bg-white/[0.02] p-0.5">
                    <button wire:click="$set('window', 'day')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $window === 'day' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300' }}">Today</button>
                    <button wire:click="$set('window', 'week')" class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $window === 'week' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300' }}">This Week</button>
                </div>
            @endif

            <span class="ml-auto text-xs text-zinc-600">Page {{ $page }} of {{ $totalPages }}</span>
        </div>

        {{-- Results grid --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
            @foreach($items as $item)
                @php
                    $mediaType = $item['media_type'] ?? ($tab === 'now_playing' && $type === 'tv' ? 'tv' : ($type !== 'all' ? $type : 'movie'));
                @endphp
                <div class="w-full">
                    @include('partials.media-card', ['item' => $item, 'type' => $mediaType])
                </div>
            @endforeach
        </div>

        @if(empty($items))
            <div class="py-20 text-center">
                <p class="text-zinc-500">No results found.</p>
            </div>
        @endif

        {{-- Pagination --}}
        @if($totalPages > 1)
            <div class="mt-10 flex items-center justify-center gap-3">
                <button
                    wire:click="prevPage"
                    @if($page <= 1) disabled @endif
                    class="flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    Previous
                </button>

                <div class="flex items-center gap-1">
                    @foreach(range(max(1, $page - 2), min($totalPages, $page + 2)) as $p)
                        <button
                            wire:click="$set('page', {{ $p }})"
                            class="size-9 rounded-lg text-sm font-medium tabular-nums transition {{ $p === $page ? 'bg-red-600 text-white' : 'text-zinc-500 hover:bg-white/[0.06] hover:text-white' }}"
                        >
                            {{ $p }}
                        </button>
                    @endforeach
                </div>

                <button
                    wire:click="nextPage"
                    @if($page >= $totalPages) disabled @endif
                    class="flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                >
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        @endif
    </div>
</div>
