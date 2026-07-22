<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Search — StreamVault')]
class extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public string $filter = 'all';

    public int $page = 1;

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function updatedFilter(): void
    {
        $this->page = 1;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
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
        $hasQuery = strlen($this->query) >= 2;

        if (! $hasQuery) {
            return [
                'results' => [],
                'totalPages' => 0,
                'trending' => $tmdb->trending('all', 'day')['results'] ?? [],
            ];
        }

        $data = $tmdb->search($this->query, $this->page);

        $canViewAdult = auth()->check() && auth()->user()->canViewAdultContent();

        $results = collect($data['results'] ?? [])->filter(function ($item) use ($canViewAdult) {
            $mediaType = $item['media_type'] ?? '';
            if (! in_array($mediaType, ['movie', 'tv'])) {
                return false;
            }
            if (! $canViewAdult && ! empty($item['adult'])) {
                return false;
            }
            if ($this->filter === 'movie') {
                return $mediaType === 'movie';
            }
            if ($this->filter === 'tv') {
                return $mediaType === 'tv';
            }

            return true;
        })->values()->all();

        return [
            'results' => $results,
            'totalPages' => min($data['total_pages'] ?? 1, 500),
            'trending' => [],
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Search header --}}
        <div class="mb-8">
            <h1 class="mb-2 text-3xl font-bold">Search</h1>
            <p class="text-sm text-zinc-400">Find movies, TV shows, and more</p>
        </div>

        {{-- Search input --}}
        <div class="relative mb-6">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <input
                wire:model.live.debounce.400ms="query"
                type="search"
                placeholder="Search for movies, TV shows..."
                class="w-full rounded-xl border border-zinc-700 bg-zinc-900 py-3.5 pl-12 pr-5 text-white placeholder-zinc-500 outline-none transition focus:border-amber-600 focus:ring-1 focus:ring-amber-600"
                autofocus
            >
            @if(strlen($query) > 0)
                <button wire:click="$set('query', '')" class="absolute inset-y-0 right-0 flex items-center pr-4 text-zinc-500 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Filters --}}
        @if(strlen($query) >= 2)
            <div class="mb-6 flex gap-2">
                @foreach(['all' => 'All', 'movie' => 'Movies', 'tv' => 'TV Shows'] as $key => $label)
                    <button
                        wire:click="setFilter('{{ $key }}')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === $key ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif

        @if(strlen($query) >= 2)
            @if(count($results) > 0)
                {{-- Loading indicator --}}
                <div wire:loading class="mb-4 flex items-center gap-2 text-sm text-zinc-500">
                    <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Searching...
                </div>

                <p class="mb-4 text-sm text-zinc-500">{{ count($results) }} results for "{{ $query }}"</p>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach($results as $item)
                        @include('partials.media-card', ['item' => $item, 'showOverview' => true])
                    @endforeach
                </div>

                <div class="mt-8 flex items-center justify-center gap-4">
                    @if($page > 1)
                        <button wire:click="previousPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Previous</button>
                    @endif
                    <span class="text-sm text-zinc-500">Page {{ $page }} of {{ $totalPages }}</span>
                    @if($page < $totalPages)
                        <button wire:click="nextPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Next</button>
                    @endif
                </div>
            @else
                <div class="py-16 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 size-12 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p class="text-lg text-zinc-400">No results found for "{{ $query }}"</p>
                    <p class="mt-1 text-sm text-zinc-600">Try different keywords or check the spelling</p>
                </div>
            @endif
        @else
            {{-- Trending suggestions when no query --}}
            @if(count($trending) > 0)
                <div class="mt-8">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold text-zinc-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                        </svg>
                        Trending Today
                    </h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach(array_slice($trending, 0, 12) as $item)
                            @include('partials.media-card', ['item' => $item])
                        @endforeach
                    </div>
                </div>
            @else
                <div class="py-16 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 size-12 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <p class="text-lg text-zinc-500">Type at least 2 characters to search</p>
                </div>
            @endif
        @endif
    </div>
</div>
