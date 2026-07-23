<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('StreamVault — Free Movie & TV Streaming')]
class extends Component
{
    public int $heroIndex = 0;

    public function nextHero(int $total): void
    {
        $this->heroIndex = ($this->heroIndex + 1) % $total;
    }

    public function prevHero(int $total): void
    {
        $this->heroIndex = ($this->heroIndex - 1 + $total) % $total;
    }

    public function setHero(int $index): void
    {
        $this->heroIndex = $index;
    }

    public function with(Tmdb $tmdb): array
    {
        $continueWatching = auth()->check()
            ? auth()->user()->watchHistory()->limit(10)->get()
            : collect();

        $hiddenGems = $tmdb->get('/discover/movie', [
            'sort_by' => 'vote_average.desc',
            'vote_count.gte' => 100,
            'vote_count.lte' => 1000,
            'vote_average.gte' => 7.5,
            'page' => (int) (now()->dayOfYear % 10) + 1,
        ])['results'] ?? [];

        return [
            'trending' => $tmdb->trending('all', 'week')['results'] ?? [],
            'popularMovies' => $tmdb->popular('movie')['results'] ?? [],
            'popularTv' => $tmdb->popular('tv')['results'] ?? [],
            'topRated' => $tmdb->topRated('movie')['results'] ?? [],
            'upcoming' => $tmdb->upcoming()['results'] ?? [],
            'nowPlaying' => $tmdb->nowPlaying()['results'] ?? [],
            'airingToday' => $tmdb->airingToday()['results'] ?? [],
            'continueWatching' => $continueWatching,
            'hiddenGems' => $hiddenGems,
        ];
    }
};
?>

<div>
    {{-- Hero Carousel --}}
    @php $heroItems = array_slice($trending, 0, 5); @endphp
    @if(count($heroItems) > 0)
        @php $hero = $heroItems[$heroIndex % count($heroItems)]; @endphp
        <div class="relative h-[75vh] min-h-[550px] w-full overflow-hidden" wire:key="hero-{{ $heroIndex }}">
            @if(!empty($hero['backdrop_path']))
                <img
                    src="{{ app(Tmdb::class)->backdropUrl($hero['backdrop_path']) }}"
                    alt="{{ $hero['title'] ?? $hero['name'] ?? '' }}"
                    class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700"
                >
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/50 to-zinc-950/20"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/90 via-zinc-950/40 to-transparent"></div>

            <div class="absolute bottom-0 left-0 right-0 p-8 md:p-16">
                <div class="mx-auto max-w-7xl">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-block rounded bg-amber-600 px-2.5 py-1 text-xs font-bold uppercase tracking-wider">
                            #{{ $heroIndex + 1 }} Trending
                        </span>
                        @php $heroMediaType = $hero['media_type'] ?? 'movie'; @endphp
                        <span class="inline-block rounded bg-zinc-700/80 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-zinc-300">
                            {{ $heroMediaType === 'tv' ? 'TV Show' : 'Movie' }}
                        </span>
                    </div>
                    <h1 class="mb-3 max-w-2xl text-4xl font-bold leading-tight md:text-5xl lg:text-6xl">
                        {{ $hero['title'] ?? $hero['name'] ?? 'Untitled' }}
                    </h1>

                    <div class="mb-4 flex items-center gap-4 text-sm text-zinc-300">
                        @if(!empty($hero['vote_average']) && $hero['vote_average'] > 0)
                            <span class="flex items-center gap-1 text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($hero['vote_average'], 1) }}
                            </span>
                        @endif
                        @php
                            $heroDate = $hero['release_date'] ?? $hero['first_air_date'] ?? '';
                        @endphp
                        @if($heroDate)
                            <span>{{ Str::substr($heroDate, 0, 4) }}</span>
                        @endif
                    </div>

                    <p class="mb-6 max-w-xl text-sm leading-relaxed text-zinc-300 md:text-base">
                        {{ Str::limit($hero['overview'] ?? '', 200) }}
                    </p>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('watch', ['type' => $heroMediaType, 'tmdbId' => $hero['id']]) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:bg-amber-500 hover:shadow-amber-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Watch Now
                        </a>
                        @php $detailRoute = $heroMediaType === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                        <a href="{{ route($detailRoute, $hero['id']) }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-zinc-600 bg-zinc-800/50 px-6 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-zinc-700" wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            More Info
                        </a>
                    </div>
                </div>
            </div>

            {{-- Carousel navigation --}}
            <div class="absolute right-4 top-1/2 flex -translate-y-1/2 flex-col gap-2 md:right-8">
                <button wire:click="prevHero({{ count($heroItems) }})" class="rounded-full bg-black/40 p-2 text-white/70 backdrop-blur-sm transition hover:bg-black/60 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button wire:click="nextHero({{ count($heroItems) }})" class="rounded-full bg-black/40 p-2 text-white/70 backdrop-blur-sm transition hover:bg-black/60 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>

            {{-- Carousel indicators --}}
            <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2 md:bottom-8">
                @foreach($heroItems as $i => $item)
                    <button
                        wire:click="setHero({{ $i }})"
                        class="h-1 rounded-full transition-all duration-300 {{ $i === ($heroIndex % count($heroItems)) ? 'w-8 bg-amber-500' : 'w-4 bg-white/30 hover:bg-white/50' }}"
                    ></button>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Continue Watching --}}
        @if($continueWatching->isNotEmpty())
            <section class="mb-10">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                    Continue Watching
                </h2>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($continueWatching as $item)
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
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity group-hover:opacity-100">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-amber-600/90 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <span class="absolute left-2 top-2 rounded bg-zinc-900/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300">{{ $item->media_type }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-medium text-zinc-300 transition group-hover:text-white">{{ Str::limit($item->title, 25) }}</h3>
                            @if($item->media_type === 'tv' && $item->season)
                                <p class="text-xs text-zinc-500">S{{ $item->season }} E{{ $item->episode }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Trending This Week — scrollable --}}
        @include('partials.media-row', ['title' => 'Trending This Week', 'items' => $trending, 'style' => 'scroll'])

        {{-- Now Playing — grid --}}
        @include('partials.media-row', [
            'title' => 'Now Playing in Theaters',
            'items' => $nowPlaying,
            'type' => 'movie',
            'seeAllRoute' => route('new-releases'),
        ])

        {{-- Upcoming Movies — special style --}}
        @if(count($upcoming) > 0)
            @include('partials.upcoming-row', ['title' => 'Upcoming Movies', 'items' => $upcoming, 'type' => 'movie'])
        @endif

        {{-- Popular Movies --}}
        @include('partials.media-row', [
            'title' => 'Popular Movies',
            'items' => $popularMovies,
            'type' => 'movie',
            'seeAllRoute' => route('movies.index'),
        ])

        {{-- Airing Today --}}
        @include('partials.media-row', [
            'title' => 'Airing Today',
            'items' => $airingToday,
            'type' => 'tv',
            'style' => 'scroll',
            'seeAllRoute' => route('new-releases', ['tab' => 'tv']),
        ])

        {{-- Popular TV Shows --}}
        @include('partials.media-row', [
            'title' => 'Popular TV Shows',
            'items' => $popularTv,
            'type' => 'tv',
            'seeAllRoute' => route('tv.index'),
        ])

        {{-- Top Rated --}}
        @include('partials.media-row', [
            'title' => 'Top Rated Movies',
            'items' => $topRated,
            'type' => 'movie',
            'style' => 'scroll',
        ])

        {{-- Hidden Gems / Daily Picks --}}
        @if(count($hiddenGems) > 0)
            @include('partials.media-row', [
                'title' => 'Hidden Gems — Daily Picks',
                'items' => $hiddenGems,
                'type' => 'movie',
                'style' => 'scroll',
            ])
        @endif
    </div>
</div>
