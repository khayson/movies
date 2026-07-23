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
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-bold">Discover</h1>

        {{-- Filters --}}
        <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-medium text-zinc-400">Filters</h2>
                <button wire:click="resetFilters" class="text-xs text-zinc-500 transition hover:text-zinc-300">Reset All</button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {{-- Type --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Type</label>
                    <select wire:model.live="type" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
                        <option value="movie">Movies</option>
                        <option value="tv">TV Shows</option>
                    </select>
                </div>

                {{-- Genre --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Genre</label>
                    <select wire:model.live="genre" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
                        <option value="">All Genres</option>
                        @foreach($genres as $g)
                            <option value="{{ $g['id'] }}">{{ $g['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Year Range --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Year From</label>
                    <input type="number" wire:model.live.debounce.500ms="yearFrom" placeholder="e.g. 2000" min="1900" max="2030"
                           class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 outline-none focus:border-amber-600">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Year To</label>
                    <input type="number" wire:model.live.debounce.500ms="yearTo" placeholder="e.g. 2026" min="1900" max="2030"
                           class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white placeholder-zinc-600 outline-none focus:border-amber-600">
                </div>

                {{-- Rating --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Min Rating</label>
                    <select wire:model.live="ratingMin" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
                        <option value="">Any</option>
                        @foreach([5, 6, 7, 8, 9] as $r)
                            <option value="{{ $r }}">{{ $r }}+</option>
                        @endforeach
                    </select>
                </div>

                {{-- Runtime (movies only) --}}
                @if($type === 'movie')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Min Runtime</label>
                        <select wire:model.live="runtimeMin" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
                            <option value="">Any</option>
                            <option value="60">1h+</option>
                            <option value="90">1.5h+</option>
                            <option value="120">2h+</option>
                            <option value="150">2.5h+</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Max Runtime</label>
                        <select wire:model.live="runtimeMax" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
                            <option value="">Any</option>
                            <option value="90">Under 1.5h</option>
                            <option value="120">Under 2h</option>
                            <option value="150">Under 2.5h</option>
                            <option value="180">Under 3h</option>
                        </select>
                    </div>
                @endif

                {{-- Language --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Language</label>
                    <select wire:model.live="language" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
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

                {{-- Sort --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-500">Sort By</label>
                    <select wire:model.live="sortBy" class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2 text-sm text-white outline-none focus:border-amber-600">
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
                    <a href="{{ route($type === 'movie' ? 'movies.detail' : 'tv.detail', ['tmdbId' => $item['id']]) }}"
                       class="group" wire:navigate>
                        <div class="aspect-[2/3] overflow-hidden rounded-lg bg-zinc-800">
                            @if(!empty($item['poster_path']))
                                <img src="{{ app(Tmdb::class)->imageUrl($item['poster_path']) }}" alt="{{ $item['title'] ?? $item['name'] ?? '' }}"
                                     class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                            @endif
                        </div>
                        <p class="mt-2 text-sm font-medium text-zinc-300 group-hover:text-amber-400">{{ Str::limit($item['title'] ?? $item['name'] ?? '', 25) }}</p>
                        @if(!empty($item['vote_average']))
                            <p class="flex items-center gap-1 text-xs text-zinc-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($item['vote_average'], 1) }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($totalPages > 1)
                <div class="mt-8 flex items-center justify-center gap-4">
                    <button wire:click="previousPage" @disabled($page <= 1)
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700 disabled:opacity-50">
                        Previous
                    </button>
                    <span class="text-sm text-zinc-500">Page {{ $page }} of {{ $totalPages }}</span>
                    <button wire:click="nextPage" @disabled($page >= $totalPages)
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700 disabled:opacity-50">
                        Next
                    </button>
                </div>
            @endif
        @else
            <p class="text-center text-sm text-zinc-500">No results found. Try adjusting your filters.</p>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
