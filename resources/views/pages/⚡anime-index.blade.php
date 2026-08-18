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
        View::share('ogDescription', 'Browse ranked anime with synopses, genres, and MyAnimeList data on '.config('app.name').'.');

        $result = $animeDb->browse([
            'page' => $this->page,
            'size' => 24,
            'search' => $this->search !== '' ? $this->search : null,
            'genres' => $this->genre !== '' ? $this->genre : null,
            'types' => $this->type !== '' ? $this->type : null,
            'sortBy' => $this->search === '' ? 'ranking' : null,
            'sortOrder' => $this->search === '' ? 'asc' : null,
        ]);

        $items = $result['data'];
        $meta = $result['meta'];
        $source = 'mal';

        if ($items === []) {
            $source = 'tmdb';
            $tmdbPage = $tmdb->animation('tv', $this->page);
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
    <div class="relative overflow-hidden border-b border-white/[0.06]">
        @if($heroBackdrop)
            <div class="absolute inset-0">
                <img src="{{ $heroBackdrop }}" alt="" class="h-full w-full object-cover opacity-25">
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-fuchsia-950/40 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-fuchsia-400/80">Anime Hub</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight md:text-5xl">
                <span class="bg-gradient-to-r from-fuchsia-400 to-amber-400 bg-clip-text text-transparent">Anime</span>
            </h1>
            <p class="mt-2 max-w-xl text-sm text-zinc-400">
                Ranked series, films, and OVAs — watch from TMDB matches when available.
            </p>
            @if($hero)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <span class="text-sm text-zinc-300">Spotlight: <span class="font-semibold text-white">{{ $hero['title'] }}</span></span>
                    @php
                        $heroMatch = $tmdbByAnimeId[$hero['id'] ?? ''] ?? null;
                        $heroType = $heroMatch['media_type'] ?? ($hero['media_type'] ?? 'tv');
                        $heroHref = $heroMatch
                            ? route($heroType === 'tv' ? 'tv.detail' : 'movies.detail', $heroMatch['id'] ?? $hero['tmdb_id'] ?? $hero['id'])
                            : route('search', ['q' => $hero['title']]);
                    @endphp
                    <a href="{{ $heroHref }}" wire:navigate class="rounded-lg bg-fuchsia-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-fuchsia-500">
                        Open title
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap gap-2">
            <button wire:click="$set('genre', '')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $genre === '' ? 'bg-fuchsia-600 text-white' : 'bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                All
            </button>
            @foreach(array_slice($genres, 0, 12) as $genreOption)
                <button wire:click="$set('genre', '{{ $genreOption }}')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $genre === $genreOption ? 'bg-fuchsia-600 text-white' : 'bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                    {{ $genreOption }}
                </button>
            @endforeach
        </div>

        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                <div class="min-w-0 flex-1">
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Search</label>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search titles…"
                        class="w-full rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-fuchsia-500/40 focus:outline-none focus:ring-1 focus:ring-fuchsia-500/30"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-zinc-500">Type</label>
                    <select
                        wire:model.live="type"
                        class="w-full rounded-xl border border-white/[0.08] bg-zinc-900 px-3 py-2.5 text-sm text-white focus:border-fuchsia-500/40 focus:outline-none sm:w-36"
                    >
                        <option value="">All types</option>
                        @foreach(['TV', 'Movie', 'OVA', 'ONA', 'Special'] as $typeOption)
                            <option value="{{ $typeOption }}">{{ $typeOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="text-xs text-zinc-600">{{ number_format($totalData) }} titles · Page {{ $page }} of {{ $totalPages }}</p>
        </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
                @foreach($items as $anime)
                    @php
                        $tmdbMatch = $tmdbByAnimeId[$anime['id'] ?? ''] ?? null;
                        $mediaType = $tmdbMatch['media_type'] ?? 'tv';
                        $href = $tmdbMatch
                            ? route($mediaType === 'tv' ? 'tv.detail' : 'movies.detail', $tmdbMatch['id'])
                            : route('search', ['q' => $anime['title']]);
                    @endphp
                    <a href="{{ $href }}" wire:navigate class="group block">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800 ring-1 ring-white/[0.06] transition group-hover:ring-fuchsia-500/40">
                            @if(!empty($anime['image']))
                                <img src="{{ $anime['image'] }}" alt="{{ $anime['title'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                            @endif
                            @if(($anime['ranking'] ?? null) !== null)
                                <span class="absolute left-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-bold text-fuchsia-300 backdrop-blur-sm">
                                    #{{ $anime['ranking'] }}
                                </span>
                            @endif
                            @if(!empty($anime['type']))
                                <span class="absolute right-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-300 backdrop-blur-sm">
                                    {{ $anime['type'] }}
                                </span>
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

            @if(empty($items))
                <div class="py-20 text-center">
                    <p class="text-zinc-500">No anime found for these filters.</p>
                </div>
            @endif

            @if($totalPages > 1)
                <div class="mt-10 flex items-center justify-center gap-3">
                    <button
                        wire:click="prevPage"
                        @if($page <= 1) disabled @endif
                        class="flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        Previous
                    </button>
                    <span class="text-sm tabular-nums text-zinc-500">{{ $page }} / {{ $totalPages }}</span>
                    <button
                        wire:click="nextPage"
                        @if($page >= $totalPages) disabled @endif
                        class="flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white disabled:cursor-not-allowed disabled:opacity-30"
                    >
                        Next
                    </button>
                </div>
            @endif
    </div>
</div>
