<?php

use App\Services\AnimeDb;
use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Anime — StreamVault')]
class extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $genre = '';

    #[Url]
    public string $type = '';

    #[Url]
    public int $page = 1;

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedGenre(): void
    {
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

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function with(AnimeDb $animeDb, Tmdb $tmdb): array
    {
        View::share('ogTitle', 'Anime — '.config('app.name'));
        View::share('ogDescription', 'New and currently airing anime, plus the full MyAnimeList catalogue, on '.config('app.name').'.');

        $filtering = $this->search !== '' || $this->genre !== '' || $this->type !== '';
        $result = $filtering
            ? $animeDb->browse([
                'page' => $this->page,
                'size' => 24,
                'search' => $this->search !== '' ? $this->search : null,
                'genres' => $this->genre !== '' ? $this->genre : null,
                'types' => $this->type !== '' ? $this->type : null,
                'sortBy' => $this->search === '' ? 'ranking' : null,
                'sortOrder' => $this->search === '' ? 'asc' : null,
            ])
            : ['data' => [], 'meta' => []];

        $items = $result['data'];
        $meta = $result['meta'];
        $source = 'mal';

        if ($items === []) {
            $source = 'tmdb';
            $tmdbPage = $tmdb->anime('tv', $this->page);
            $items = array_map(function (array $item) use ($tmdb): array {
                return [
                    'id' => (string) $item['id'],
                    'title' => (string) ($item['name'] ?? $item['title'] ?? 'Untitled'),
                    'image' => ! empty($item['poster_path']) ? $tmdb->imageUrl($item['poster_path']) : null,
                    'ranking' => null,
                    'type' => 'TV',
                    'episodes' => null,
                    'status' => null,
                    'synopsis' => $item['overview'] ?? null,
                    'tmdb_id' => $item['id'],
                    'media_type' => 'tv',
                    'backdrop_path' => $item['backdrop_path'] ?? null,
                    'vote_average' => $item['vote_average'] ?? null,
                ];
            }, $tmdbPage['results'] ?? []);
            $meta = [
                'page' => $this->page,
                'size' => 24,
                'totalData' => (int) ($tmdbPage['total_results'] ?? count($items)),
                'totalPage' => max(1, min((int) ($tmdbPage['total_pages'] ?? 1), 500)),
            ];
            $tmdbByAnimeId = [];

            foreach ($tmdbPage['results'] ?? [] as $card) {
                if (isset($card['id'])) {
                    $tmdbByAnimeId[(string) $card['id']] = array_merge($card, ['media_type' => 'tv']);
                }
            }
        } else {
            $tmdbCards = $animeDb->toTmdbCards($items, $tmdb, count($items));
            $tmdbByAnimeId = [];

            foreach ($tmdbCards as $card) {
                $animeId = $card['anime_id'] ?? null;

                if (is_string($animeId) && $animeId !== '') {
                    $tmdbByAnimeId[$animeId] = $card;
                }
            }
        }

        $hero = $items[0] ?? null;
        $heroBackdrop = null;

        if (is_array($hero)) {
            $heroMatch = $tmdbByAnimeId[$hero['id'] ?? ''] ?? null;
            $backdrop = $hero['backdrop_path'] ?? $heroMatch['backdrop_path'] ?? null;
            $heroBackdrop = is_string($backdrop) && $backdrop !== ''
                ? (str_starts_with($backdrop, 'http') ? $backdrop : $tmdb->backdropUrl($backdrop, 'w1280'))
                : ($hero['image'] ?? null);
        }

        $genres = $animeDb->genres();

        if ($genres === []) {
            $genres = ['Action', 'Adventure', 'Comedy', 'Drama', 'Fantasy', 'Romance', 'Sci-Fi', 'Slice of Life', 'Sports', 'Supernatural'];
        }

        return [
            'items' => $items,
            'genres' => $genres,
            'tmdbByAnimeId' => $tmdbByAnimeId,
            'totalPages' => max(1, (int) ($meta['totalPage'] ?? 1)),
            'totalData' => (int) ($meta['totalData'] ?? 0),
            'source' => $source,
            'hero' => $hero,
            'heroBackdrop' => $heroBackdrop,
        ];
    }
};
?>

<div>
    <x-page-header eyebrow="Browse" title="Anime" subtitle="New and currently airing anime. Search or filter to explore the full MyAnimeList catalogue." :backdrop="$heroBackdrop">
        <div class="flex flex-col gap-3 sm:flex-row">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Search anime</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search the full anime catalogue…"
                       class="w-full rounded-full border border-white/[0.08] bg-white/[0.06] py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-zinc-500 focus:border-amber-400/50 focus:outline-none focus:ring-1 focus:ring-amber-400/40">
            </label>
            <label>
                <span class="sr-only">Type</span>
                <select wire:model.live="type" class="w-full rounded-full border border-white/[0.08] bg-zinc-900 px-4 py-2.5 text-sm text-white focus:border-amber-400/50 focus:outline-none sm:w-40">
                    <option value="">All types</option>
                    @foreach(['TV', 'Movie', 'OVA', 'ONA', 'Special'] as $typeOption)
                        <option value="{{ $typeOption }}">{{ $typeOption }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="scrollbar-hide -mx-4 mt-4 flex gap-2 overflow-x-auto px-4">
            <x-tab-button :active="$genre === ''" wire:click="$set('genre', '')">All</x-tab-button>
            @foreach(array_slice($genres, 0, 12) as $genreOption)
                <x-tab-button :active="$genre === $genreOption" wire:click="$set('genre', '{{ $genreOption }}')">{{ $genreOption }}</x-tab-button>
            @endforeach
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        @if($totalData > 0 && ($search !== '' || $genre !== '' || $type !== ''))
            <p class="mb-5 text-sm text-zinc-500">{{ number_format($totalData) }} titles</p>
        @endif

        @if(empty($items))
            <p class="py-20 text-center text-zinc-500">No anime found for these filters.</p>
        @else
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
                @foreach($items as $anime)
                    @php
                        $tmdbMatch = $tmdbByAnimeId[$anime['id'] ?? ''] ?? null;
                        $mediaType = $tmdbMatch['media_type'] ?? 'tv';
                        $href = $tmdbMatch
                            ? route($mediaType === 'tv' ? 'tv.detail' : 'movies.detail', $tmdbMatch['id'])
                            : route('search', ['q' => $anime['title']]);
                    @endphp
                    <a href="{{ $href }}"
                       @if($tmdbMatch)
                           @click="if (! $event.ctrlKey && ! $event.metaKey && ! $event.shiftKey && ! $event.altKey && $event.button === 0) { $event.preventDefault(); $dispatch('open-title', { type: '{{ $mediaType }}', id: {{ (int) $tmdbMatch['id'] }} }) }"
                       @else
                           wire:navigate
                       @endif
                       class="group block">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800 ring-1 ring-white/[0.06] transition group-hover:ring-amber-400/50">
                            @if(!empty($anime['image']))
                                <img src="{{ $anime['image'] }}" alt="{{ $anime['title'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                            @endif
                            @if(($anime['ranking'] ?? null) !== null)
                                <span class="absolute left-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-bold text-amber-300 backdrop-blur-sm">#{{ $anime['ranking'] }}</span>
                            @endif
                            @if(!empty($anime['type']))
                                <span class="absolute right-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-300 backdrop-blur-sm">{{ $anime['type'] }}</span>
                            @endif
                        </div>
                        <p class="mt-2 line-clamp-2 text-sm font-medium text-zinc-200 transition group-hover:text-white">{{ $anime['title'] }}</p>
                        <p class="mt-0.5 text-[11px] text-zinc-500">
                            @if(($anime['episodes'] ?? null) !== null)
                                {{ $anime['episodes'] }} eps
                            @endif
                            @if(!empty($anime['status']))
                                @if(($anime['episodes'] ?? null) !== null) · @endif
                                {{ $anime['status'] }}
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        @endif

        <x-pager :page="$page" :total-pages="$totalPages" previous="prevPage" />
    </div>
</div>
