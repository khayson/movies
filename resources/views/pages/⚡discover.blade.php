<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Discover — StreamVault')]
class extends Component
{
    #[Url]
    public string $type = 'movie';

    #[Url]
    public string $genre = '';

    #[Url]
    public string $yearFrom = '';

    #[Url]
    public string $yearTo = '';

    #[Url]
    public string $ratingMin = '';

    #[Url]
    public string $runtimeMin = '';

    #[Url]
    public string $runtimeMax = '';

    #[Url]
    public string $language = '';

    #[Url]
    public string $sortBy = 'popularity.desc';

    #[Url]
    public int $page = 1;

    public function resetFilters(): void
    {
        $this->genre = '';
        $this->yearFrom = '';
        $this->yearTo = '';
        $this->ratingMin = '';
        $this->runtimeMin = '';
        $this->runtimeMax = '';
        $this->language = '';
        $this->sortBy = 'popularity.desc';
        $this->page = 1;
    }

    public function updatedType(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function with(Tmdb $tmdb): array
    {
        $params = [
            'sort_by' => $this->sortBy,
            'page' => $this->page,
            'vote_count.gte' => 50,
        ];

        if ($this->genre) {
            $params['with_genres'] = $this->genre;
        }
        if ($this->ratingMin) {
            $params['vote_average.gte'] = (float) $this->ratingMin;
        }
        if ($this->language) {
            $params['with_original_language'] = $this->language;
        }

        if ($this->type === 'movie') {
            if ($this->yearFrom) {
                $params['primary_release_date.gte'] = $this->yearFrom.'-01-01';
            }
            if ($this->yearTo) {
                $params['primary_release_date.lte'] = $this->yearTo.'-12-31';
            }
            if ($this->runtimeMin) {
                $params['with_runtime.gte'] = (int) $this->runtimeMin;
            }
            if ($this->runtimeMax) {
                $params['with_runtime.lte'] = (int) $this->runtimeMax;
            }
        } else {
            if ($this->yearFrom) {
                $params['first_air_date.gte'] = $this->yearFrom.'-01-01';
            }
            if ($this->yearTo) {
                $params['first_air_date.lte'] = $this->yearTo.'-12-31';
            }
        }

        $data = $tmdb->get("/discover/{$this->type}", $params);
        $genres = $tmdb->genres($this->type);

        return [
            'results' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
            'genres' => $genres['genres'] ?? [],
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-indigo-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-400/80">Advanced</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Discover</h1>
            <p class="mt-2 text-sm text-zinc-400">Filter and find exactly what you're looking for</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Filter Panel --}}
        <div class="mb-8 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                    Filters
                </h2>
                <button wire:click="resetFilters" class="rounded-lg border border-white/[0.06] bg-white/[0.03] px-3 py-1 text-xs font-medium text-zinc-400 transition hover:border-white/[0.12] hover:text-white">Reset All</button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Type</label>
                    <select wire:model.live="type" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                        <option value="movie">Movies</option>
                        <option value="tv">TV Shows</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Genre</label>
                    <select wire:model.live="genre" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                        <option value="">All Genres</option>
                        @foreach($genres as $g)
                            <option value="{{ $g['id'] }}">{{ $g['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Year From</label>
                    <input type="number" wire:model.live.debounce.500ms="yearFrom" placeholder="e.g. 2000" min="1900" max="2030"
                           class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder-zinc-600 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Year To</label>
                    <input type="number" wire:model.live.debounce.500ms="yearTo" placeholder="e.g. 2026" min="1900" max="2030"
                           class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white placeholder-zinc-600 outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Min Rating</label>
                    <select wire:model.live="ratingMin" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                        <option value="">Any</option>
                        @foreach([5, 6, 7, 8, 9] as $r)
                            <option value="{{ $r }}">{{ $r }}+</option>
                        @endforeach
                    </select>
                </div>
                @if($type === 'movie')
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-zinc-500">Min Runtime</label>
                        <select wire:model.live="runtimeMin" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                            <option value="">Any</option>
                            <option value="60">1h+</option>
                            <option value="90">1.5h+</option>
                            <option value="120">2h+</option>
                            <option value="150">2.5h+</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-zinc-500">Max Runtime</label>
                        <select wire:model.live="runtimeMax" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                            <option value="">Any</option>
                            <option value="90">Under 1.5h</option>
                            <option value="120">Under 2h</option>
                            <option value="150">Under 2.5h</option>
                            <option value="180">Under 3h</option>
                        </select>
                    </div>
                @endif
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Language</label>
                    <select wire:model.live="language" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                        <option value="">Any</option>
                        <option value="en">English</option>
                        <option value="ko">Korean</option>
                        <option value="ja">Japanese</option>
                        <option value="fr">French</option>
                        <option value="es">Spanish</option>
                        <option value="de">German</option>
                        <option value="hi">Hindi</option>
                        <option value="zh">Chinese</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Sort By</label>
                    <select wire:model.live="sortBy" class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-3 py-2.5 text-sm text-white outline-none transition focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                        <option value="popularity.desc">Most Popular</option>
                        <option value="vote_average.desc">Highest Rated</option>
                        <option value="primary_release_date.desc">Newest First</option>
                        <option value="primary_release_date.asc">Oldest First</option>
                        <option value="revenue.desc">Highest Revenue</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Results --}}
        @if(count($results) > 0)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($results as $item)
                    @include('partials.media-card', ['item' => $item, 'type' => $type])
                @endforeach
            </div>

            @if($totalPages > 1)
                <div class="mt-10 flex items-center justify-center gap-3">
                    @if($page > 1)
                        <button wire:click="previousPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            Previous
                        </button>
                    @endif
                    <span class="rounded-xl bg-white/[0.04] px-5 py-2.5 text-sm tabular-nums text-zinc-500">{{ $page }} / {{ $totalPages }}</span>
                    @if($page < $totalPages)
                        <button wire:click="nextPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    @endif
                </div>
            @endif
        @else
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <p class="text-lg font-medium text-zinc-400">No results found</p>
                <p class="mt-1 text-sm text-zinc-600">Try adjusting your filters</p>
            </div>
        @endif
    </div>
</div>
