<?php

use App\Services\AiRecommender;
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

    #[Url]
    public string $mode = 'standard';

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

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
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

    public function with(Tmdb $tmdb, AiRecommender $ai): array
    {
        $hasQuery = strlen($this->query) >= 2;

        if (! $hasQuery) {
            return [
                'results' => [],
                'totalPages' => 0,
                'trending' => $tmdb->trending('all', 'day')['results'] ?? [],
                'aiResults' => [],
            ];
        }

        if ($this->mode === 'ai') {
            $data = $ai->search($this->query);
            $aiResults = $data['movies'] ?? [];

            return [
                'results' => [],
                'totalPages' => 0,
                'trending' => [],
                'aiResults' => $aiResults,
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
            'aiResults' => [],
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Search header --}}
        <div class="mb-8">
            <h1 class="mb-1 text-3xl font-bold tracking-tight">Search</h1>
            <p class="text-sm text-zinc-500">Find movies, TV shows, and more</p>
        </div>

        {{-- Mode toggle --}}
        <div class="mb-4 inline-flex items-center gap-1 rounded-xl border border-white/[0.06] bg-white/[0.02] p-1">
            <button wire:click="setMode('standard')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $mode === 'standard' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'text-zinc-400 hover:text-white' }}">
                Standard
            </button>
            <button wire:click="setMode('ai')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium transition {{ $mode === 'ai' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'text-zinc-400 hover:text-white' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                </svg>
                AI Search
            </button>
        </div>

        {{-- Search input --}}
        <div class="relative mb-6">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                @if($mode === 'ai')
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                @endif
            </div>
            <input
                wire:model.live.debounce.400ms="query"
                type="search"
                placeholder="{{ $mode === 'ai' ? 'Describe what you want to watch... e.g. \'movies like Inception but scarier\'' : 'Search for movies, TV shows...' }}"
                class="w-full rounded-2xl border {{ $mode === 'ai' ? 'border-amber-500/30 focus:border-amber-500 focus:ring-amber-500/30' : 'border-white/[0.08] focus:border-amber-600 focus:ring-amber-600/30' }} bg-white/[0.02] py-4 pl-12 pr-5 text-white placeholder-zinc-500 outline-none transition focus:ring-2"
                autofocus
            >
            @if(strlen($query) > 0)
                <button wire:click="$set('query', '')" class="absolute inset-y-0 right-0 flex items-center pr-4 text-zinc-500 transition hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        {{-- Filters (standard mode only) --}}
        @if($mode === 'standard' && strlen($query) >= 2)
            <div class="mb-6 flex gap-2">
                @foreach(['all' => 'All', 'movie' => 'Movies', 'tv' => 'TV Shows'] as $key => $label)
                    <button
                        wire:click="setFilter('{{ $key }}')"
                        class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === $key ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.02] text-zinc-400 hover:border-white/[0.1] hover:text-white' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Loading --}}
        <div wire:loading class="py-4">
            <div class="flex items-center gap-3 text-sm text-zinc-500">
                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $mode === 'ai' ? 'AI is finding movies for you...' : 'Searching...' }}
            </div>
        </div>

        <div wire:loading.remove>
            {{-- AI Results --}}
            @if($mode === 'ai' && strlen($query) >= 2)
                @if(count($aiResults) > 0)
                    <p class="mb-4 text-sm text-zinc-500">AI found {{ count($aiResults) }} movies for "{{ $query }}"</p>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach($aiResults as $item)
                            <a href="{{ route('movies.detail', ['tmdbId' => $item['id']]) }}"
                               class="group" wire:navigate>
                                <div class="aspect-[2/3] overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-800">
                                    @if(!empty($item['poster_path']))
                                        <img src="https://image.tmdb.org/t/p/w500{{ $item['poster_path'] }}" alt="{{ $item['title'] ?? '' }}"
                                             class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-medium text-zinc-300 transition group-hover:text-white">{{ Str::limit($item['title'] ?? '', 30) }}</p>
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    @if(!empty($item['vote_average']))
                                        <span class="flex items-center gap-1 text-amber-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            {{ number_format($item['vote_average'], 1) }}
                                        </span>
                                    @endif
                                    @if(!empty($item['release_date']))
                                        <span>{{ Str::substr($item['release_date'], 0, 4) }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="py-20 text-center">
                        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-zinc-400">No AI results for "{{ $query }}"</p>
                        <p class="mt-1 text-sm text-zinc-600">Try describing what you're in the mood for, like "funny movies from the 80s"</p>
                    </div>
                @endif

            {{-- Standard Results --}}
            @elseif($mode === 'standard' && strlen($query) >= 2)
                @if(count($results) > 0)
                    <p class="mb-4 text-sm text-zinc-500">{{ count($results) }} results for "{{ $query }}"</p>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach($results as $item)
                            @include('partials.media-card', ['item' => $item, 'showOverview' => true])
                        @endforeach
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-3">
                        @if($page > 1)
                            <button wire:click="previousPage" class="rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">Previous</button>
                        @endif
                        <span class="rounded-xl bg-white/[0.04] px-4 py-2.5 text-sm tabular-nums text-zinc-500">{{ $page }} / {{ $totalPages }}</span>
                        @if($page < $totalPages)
                            <button wire:click="nextPage" class="rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">Next</button>
                        @endif
                    </div>
                @else
                    <div class="py-20 text-center">
                        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-zinc-400">No results found for "{{ $query }}"</p>
                        <p class="mt-1 text-sm text-zinc-600">Try different keywords or check the spelling</p>
                    </div>
                @endif
            @else
                {{-- Trending when no query --}}
                @if(count($trending) > 0)
                    <div class="mt-8">
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold tracking-tight text-white">
                            <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                            Trending Today
                        </h2>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            @foreach(array_slice($trending, 0, 12) as $item)
                                @include('partials.media-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="py-20 text-center">
                        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <p class="text-lg text-zinc-500">Type at least 2 characters to search</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
