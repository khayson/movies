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
    public function with(Tmdb $tmdb): array
    {
        $continueWatching = auth()->check()
            ? auth()->user()->watchHistory()->limit(10)->get()
            : collect();

        $trending = $tmdb->trending('all', 'week')['results'] ?? [];
        $trendingDay = $tmdb->trending('all', 'day')['results'] ?? [];
        $popularMovies = $tmdb->popular('movie')['results'] ?? [];
        $popularTv = $tmdb->popular('tv')['results'] ?? [];
        $topRated = $tmdb->topRated('movie')['results'] ?? [];
        $topRatedTv = $tmdb->topRated('tv')['results'] ?? [];
        $nowPlaying = $tmdb->nowPlaying()['results'] ?? [];
        $today = now()->toDateString();
        $upcoming = collect($tmdb->upcoming()['results'] ?? [])
            ->filter(fn (array $m) => ($m['release_date'] ?? '') > $today)
            ->values()
            ->all();
        $airingToday = $tmdb->airingToday()['results'] ?? [];

        $movieGenres = $tmdb->genres('movie')['genres'] ?? [];
        $tvGenres = $tmdb->genres('tv')['genres'] ?? [];
        $genreMap = [];
        foreach (array_merge($movieGenres, $tvGenres) as $g) {
            $genreMap[$g['id']] = $g['name'];
        }

        $heroSlice = array_slice($trending, 0, 5);
        $heroItems = [];
        foreach ($heroSlice as $item) {
            $type = $item['media_type'] ?? 'movie';
            $logo = '';
            $trailerKey = null;

            try {
                $images = $tmdb->images($type, $item['id']);
                $logos = $images['logos'] ?? [];
                if (! empty($logos)) {
                    usort($logos, fn ($a, $b) => ($b['vote_average'] ?? 0) <=> ($a['vote_average'] ?? 0));
                    $logo = $tmdb->imageUrl($logos[0]['file_path'], 'w500');
                }
            } catch (\Throwable) {
            }

            try {
                $videos = $tmdb->videos($type, $item['id']);
                $trailerKey = Tmdb::extractTrailerKey($videos['results'] ?? []);
            } catch (\Throwable) {
            }

            $heroItems[] = [
                'id' => $item['id'],
                'title' => $item['title'] ?? $item['name'] ?? 'Untitled',
                'logo' => $logo,
                'trailerKey' => $trailerKey,
                'backdrop' => ! empty($item['backdrop_path']) ? $tmdb->backdropUrl($item['backdrop_path'], 'w1280') : '',
                'rating' => number_format($item['vote_average'] ?? 0, 1),
                'genres' => collect($item['genre_ids'] ?? [])->map(fn ($id) => $genreMap[$id] ?? null)->filter()->take(3)->values()->all(),
                'type' => $type,
                'overview' => Str::limit($item['overview'] ?? '', 180),
                'year' => Str::substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
                'watchUrl' => route('watch', ['type' => $type, 'tmdbId' => $item['id']]),
                'detailUrl' => route($type === 'tv' ? 'tv.detail' : 'movies.detail', $item['id']),
            ];
        }

        $featuredItem = $trending[5] ?? $trending[0] ?? null;
        $featured = null;
        if ($featuredItem) {
            $fType = $featuredItem['media_type'] ?? 'movie';
            $featured = [
                'id' => $featuredItem['id'],
                'title' => $featuredItem['title'] ?? $featuredItem['name'] ?? '',
                'backdrop' => ! empty($featuredItem['backdrop_path']) ? $tmdb->backdropUrl($featuredItem['backdrop_path'], 'w1280') : '',
                'rating' => number_format($featuredItem['vote_average'] ?? 0, 1),
                'genres' => collect($featuredItem['genre_ids'] ?? [])->map(fn ($id) => $genreMap[$id] ?? null)->filter()->take(3)->values()->all(),
                'type' => $fType,
                'overview' => Str::limit($featuredItem['overview'] ?? '', 200),
                'year' => Str::substr($featuredItem['release_date'] ?? $featuredItem['first_air_date'] ?? '', 0, 4),
                'watchUrl' => route('watch', ['type' => $fType, 'tmdbId' => $featuredItem['id']]),
                'detailUrl' => route($fType === 'tv' ? 'tv.detail' : 'movies.detail', $featuredItem['id']),
            ];
        }

        return [
            'heroItems' => $heroItems,
            'trending' => $trending,
            'trendingDay' => $trendingDay,
            'popularMovies' => $popularMovies,
            'popularTv' => $popularTv,
            'topRated' => $topRated,
            'topRatedTv' => $topRatedTv,
            'nowPlaying' => $nowPlaying,
            'upcoming' => $upcoming,
            'airingToday' => $airingToday,
            'continueWatching' => $continueWatching,
            'genreMap' => $genreMap,
            'movieGenres' => $movieGenres,
            'tvGenres' => $tvGenres,
            'featured' => $featured,
        ];
    }
};
?>

<div>
    {{-- Hero Carousel --}}
    @if(count($heroItems) > 0)
        <div
            x-data="{
                current: 0,
                items: {{ Js::from($heroItems) }},
                total: {{ count($heroItems) }},
                timer: null,
                transitioning: false,
                init() {
                    this.startTimer();
                },
                startTimer() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.next(), 8000);
                },
                next() {
                    this.transitioning = true;
                    setTimeout(() => {
                        this.current = (this.current + 1) % this.total;
                        this.transitioning = false;
                    }, 300);
                    this.startTimer();
                },
                prev() {
                    this.transitioning = true;
                    setTimeout(() => {
                        this.current = (this.current - 1 + this.total) % this.total;
                        this.transitioning = false;
                    }, 300);
                    this.startTimer();
                },
                goTo(i) {
                    if (i === this.current) return;
                    this.transitioning = true;
                    setTimeout(() => {
                        this.current = i;
                        this.transitioning = false;
                    }, 300);
                    this.startTimer();
                },
                get cur() { return this.items[this.current]; }
            }"
            class="relative w-full overflow-hidden"
            style="height: clamp(500px, 80vh, 800px);"
        >
            {{-- Backdrop images --}}
            @foreach($heroItems as $i => $hero)
                <div
                    x-show="current === {{ $i }}"
                    x-transition:enter="transition-opacity ease-out duration-700"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity ease-in duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0"
                >
                    @if($hero['backdrop'])
                        <img src="{{ $hero['backdrop'] }}" alt="{{ $hero['title'] }}" class="h-full w-full object-cover">
                    @endif
                </div>
            @endforeach

            {{-- Gradients --}}
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/40 to-zinc-950/10"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/80 via-zinc-950/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-zinc-950 to-transparent"></div>

            {{-- Hero Content --}}
            <div class="absolute bottom-0 left-0 right-0 px-4 pb-20 sm:px-8 md:px-16 md:pb-24">
                <div class="mx-auto max-w-7xl">
                    {{-- Badges --}}
                    <div class="mb-3 flex items-center gap-2.5">
                        <span class="flex items-center gap-1 rounded-md bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span x-text="cur.rating"></span>
                        </span>
                        <span class="rounded-md bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-zinc-300 backdrop-blur-sm" x-text="cur.year"></span>
                        <span
                            class="rounded-md px-2 py-0.5 text-[11px] font-semibold backdrop-blur-sm"
                            :class="cur.type === 'tv' ? 'bg-purple-500/20 text-purple-300' : 'bg-blue-500/20 text-blue-300'"
                            x-text="cur.type === 'tv' ? 'TV Show' : 'Movie'"
                        ></span>
                        <template x-for="g in cur.genres" :key="g">
                            <span class="hidden rounded-md bg-white/10 px-2 py-0.5 text-[11px] font-medium text-zinc-400 backdrop-blur-sm sm:inline" x-text="g"></span>
                        </template>
                    </div>

                    {{-- Title (logo or text) --}}
                    <div class="mb-4 max-w-2xl">
                        <template x-if="cur.logo">
                            <img :src="cur.logo" :alt="cur.title" class="max-h-20 w-auto drop-shadow-[0_2px_10px_rgba(0,0,0,0.7)] sm:max-h-24 md:max-h-28">
                        </template>
                        <template x-if="!cur.logo">
                            <h1 class="text-3xl font-black uppercase tracking-[0.15em] text-white drop-shadow-lg sm:text-4xl md:text-5xl lg:text-6xl" x-text="cur.title"></h1>
                        </template>
                    </div>

                    {{-- Overview --}}
                    <p class="mb-6 hidden max-w-xl text-sm leading-relaxed text-zinc-300/90 sm:line-clamp-3 md:line-clamp-none" x-text="cur.overview"></p>

                    {{-- Buttons --}}
                    <div class="flex items-center gap-3">
                        <a :href="cur.watchUrl"
                           class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-500 sm:px-7 sm:py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            <span class="hidden sm:inline">WATCH</span>
                        </a>
                        <a :href="cur.detailUrl"
                           wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/10 sm:px-6 sm:py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            <span class="hidden sm:inline">MORE INFO</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Thumbnail navigation (right side) --}}
            <div class="absolute bottom-20 right-4 hidden flex-col gap-2 md:right-10 md:flex lg:right-16">
                @foreach($heroItems as $i => $hero)
                    <button
                        @click="goTo({{ $i }})"
                        class="relative overflow-hidden rounded-lg transition-all duration-300"
                        :class="current === {{ $i }} ? 'ring-2 ring-red-500 w-16 h-10' : 'w-14 h-9 opacity-50 hover:opacity-80'"
                    >
                        @if($hero['backdrop'])
                            <img src="{{ app(Tmdb::class)->imageUrl(str_replace('https://image.tmdb.org/t/p/w1280', '', $hero['backdrop']), 'w185') }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full bg-zinc-700"></div>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Mobile indicators --}}
            <div class="absolute bottom-10 left-1/2 flex -translate-x-1/2 gap-2 md:hidden">
                @foreach($heroItems as $i => $hero)
                    <button
                        @click="goTo({{ $i }})"
                        class="h-1 rounded-full transition-all duration-300"
                        :class="current === {{ $i }} ? 'w-8 bg-red-500' : 'w-3 bg-white/30'"
                    ></button>
                @endforeach
            </div>

            {{-- Arrow nav --}}
            <button @click="prev()" class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/30 p-2.5 text-white/60 transition hover:bg-black/50 hover:text-white sm:left-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>
            <button @click="next()" class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/30 p-2.5 text-white/60 transition hover:bg-black/50 hover:text-white sm:right-4 md:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>
    @endif

    {{-- Main content --}}
    <div class="relative z-10 -mt-10">
        {{-- Category tabs --}}
        <div
            x-data="{ activeTab: 'trending' }"
            class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
        >
            <div class="scrollbar-hide mb-6 flex items-center gap-1 overflow-x-auto border-b border-white/[0.06] pb-px">
                @php
                    $tabs = [
                        'trending' => 'Trending Now',
                        'popular' => 'Popular',
                        'toprated' => 'Top Rated',
                        'nowplaying' => 'Now Playing',
                        'recent' => 'Recently Added',
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <button
                        @click="activeTab = '{{ $key }}'"
                        class="relative whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === '{{ $key }}' ? 'text-white' : 'text-zinc-500 hover:text-zinc-300'"
                    >
                        {{ $label }}
                        <span
                            x-show="activeTab === '{{ $key }}'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="scale-x-0"
                            x-transition:enter-end="scale-x-100"
                            class="absolute bottom-0 left-2 right-2 h-0.5 rounded-full bg-red-500"
                        ></span>
                    </button>
                @endforeach
            </div>

            {{-- Genre filter pills --}}
            @php
                $genreColors = [
                    'Action' => 'bg-red-500/15 text-red-400 hover:bg-red-500/25',
                    'Romance' => 'bg-pink-500/15 text-pink-400 hover:bg-pink-500/25',
                    'Animation' => 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25',
                    'Comedy' => 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25',
                    'Crime' => 'bg-orange-500/15 text-orange-400 hover:bg-orange-500/25',
                    'Documentary' => 'bg-cyan-500/15 text-cyan-400 hover:bg-cyan-500/25',
                    'Drama' => 'bg-violet-500/15 text-violet-400 hover:bg-violet-500/25',
                    'Horror' => 'bg-rose-500/15 text-rose-400 hover:bg-rose-500/25',
                    'Thriller' => 'bg-yellow-500/15 text-yellow-400 hover:bg-yellow-500/25',
                    'Sci-Fi' => 'bg-sky-500/15 text-sky-400 hover:bg-sky-500/25',
                    'Science Fiction' => 'bg-sky-500/15 text-sky-400 hover:bg-sky-500/25',
                    'Adventure' => 'bg-teal-500/15 text-teal-400 hover:bg-teal-500/25',
                    'Fantasy' => 'bg-indigo-500/15 text-indigo-400 hover:bg-indigo-500/25',
                    'Music' => 'bg-fuchsia-500/15 text-fuchsia-400 hover:bg-fuchsia-500/25',
                    'Family' => 'bg-lime-500/15 text-lime-400 hover:bg-lime-500/25',
                    'Mystery' => 'bg-purple-500/15 text-purple-400 hover:bg-purple-500/25',
                    'War' => 'bg-stone-500/15 text-stone-400 hover:bg-stone-500/25',
                    'History' => 'bg-amber-600/15 text-amber-500 hover:bg-amber-600/25',
                    'Western' => 'bg-orange-600/15 text-orange-500 hover:bg-orange-600/25',
                ];
                $defaultColor = 'bg-zinc-500/15 text-zinc-400 hover:bg-zinc-500/25';
                $displayGenres = array_slice($movieGenres, 0, 10);
            @endphp
            <div class="scrollbar-hide mb-8 flex gap-2 overflow-x-auto pb-2">
                @foreach($displayGenres as $genre)
                    <a
                        href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                        wire:navigate
                        class="shrink-0 rounded-full px-4 py-1.5 text-xs font-semibold transition {{ $genreColors[$genre['name']] ?? $defaultColor }}"
                    >
                        {{ $genre['name'] }}
                    </a>
                @endforeach
            </div>

            {{-- Tab: Trending --}}
            <div x-show="activeTab === 'trending'">
                @include('partials.home-scroll-row', ['items' => $trending, 'genreMap' => $genreMap])
            </div>
            <div x-show="activeTab === 'popular'" x-cloak>
                @include('partials.home-scroll-row', ['items' => array_merge($popularMovies, $popularTv), 'genreMap' => $genreMap])
            </div>
            <div x-show="activeTab === 'toprated'" x-cloak>
                @include('partials.home-scroll-row', ['items' => array_merge($topRated, $topRatedTv), 'genreMap' => $genreMap])
            </div>
            <div x-show="activeTab === 'nowplaying'" x-cloak>
                @include('partials.home-scroll-row', ['items' => $nowPlaying, 'genreMap' => $genreMap, 'type' => 'movie'])
            </div>
            <div x-show="activeTab === 'recent'" x-cloak>
                @include('partials.home-scroll-row', ['items' => $airingToday, 'genreMap' => $genreMap, 'type' => 'tv'])
            </div>
        </div>

        {{-- Continue Watching --}}
        @if($continueWatching->isNotEmpty())
            <div class="mx-auto mt-12 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex items-center gap-3">
                    <h2 class="text-lg font-bold text-white">Continue Watching</h2>
                    <span class="size-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                    <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                </div>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($continueWatching as $item)
                        <div class="w-36 shrink-0 sm:w-40">
                            @include('partials.media-card', ['item' => $item, 'variant' => 'watch'])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tabbed content: MOVIES / SERIES --}}
        <div
            x-data="{
                contentTab: 'movies',
                sortBy: 'popular',
                viewMode: 'grid'
            }"
            class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8"
        >
            {{-- Tabs header --}}
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-1">
                    <button @click="contentTab = 'movies'" class="px-4 py-2 text-sm font-bold uppercase tracking-wider transition" :class="contentTab === 'movies' ? 'text-white' : 'text-zinc-600 hover:text-zinc-400'">
                        Movies
                    </button>
                    <span class="text-zinc-700">|</span>
                    <button @click="contentTab = 'series'" class="px-4 py-2 text-sm font-bold uppercase tracking-wider transition" :class="contentTab === 'series' ? 'text-white' : 'text-zinc-600 hover:text-zinc-400'">
                        Series
                    </button>
                </div>

                {{-- Sort + view controls --}}
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1 rounded-lg border border-white/[0.06] bg-white/[0.02] p-0.5">
                        <button @click="sortBy = 'popular'" class="rounded-md px-3 py-1 text-[11px] font-semibold transition" :class="sortBy === 'popular' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300'">Popular</button>
                        <button @click="sortBy = 'new'" class="rounded-md px-3 py-1 text-[11px] font-semibold transition" :class="sortBy === 'new' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300'">New</button>
                        <button @click="sortBy = 'az'" class="rounded-md px-3 py-1 text-[11px] font-semibold transition" :class="sortBy === 'az' ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-zinc-300'">A-Z</button>
                    </div>

                    <div class="flex items-center gap-1">
                        <button @click="viewMode = 'grid'" class="rounded-md p-1.5 transition" :class="viewMode === 'grid' ? 'bg-white/10 text-white' : 'text-zinc-600 hover:text-zinc-400'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                        </button>
                        <button @click="viewMode = 'list'" class="rounded-md p-1.5 transition" :class="viewMode === 'list' ? 'bg-white/10 text-white' : 'text-zinc-600 hover:text-zinc-400'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Genre pills for content section --}}
            <div class="scrollbar-hide mb-6 flex gap-2 overflow-x-auto pb-2">
                @foreach($displayGenres as $genre)
                    <a
                        href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                        wire:navigate
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-[11px] font-semibold transition {{ $genreColors[$genre['name']] ?? $defaultColor }}"
                    >
                        {{ $genre['name'] }}
                    </a>
                @endforeach
            </div>

            {{-- Movies grid --}}
            <div x-show="contentTab === 'movies'">
                <div
                    x-data="{
                        movies: {{ Js::from(array_map(fn ($m) => array_merge($m, ['media_type' => $m['media_type'] ?? 'movie']), array_slice($popularMovies, 0, 18))) }},
                        get sorted() {
                            let items = [...this.movies];
                            if (this.sortBy === 'az') items.sort((a, b) => (a.title || a.name || '').localeCompare(b.title || b.name || ''));
                            if (this.sortBy === 'new') items.sort((a, b) => (b.release_date || '').localeCompare(a.release_date || ''));
                            return items;
                        }
                    }"
                >
                    {{-- Grid view --}}
                    <div x-show="viewMode === 'grid'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach(array_slice($popularMovies, 0, 18) as $movie)
                            @include('partials.media-card', ['item' => $movie, 'type' => 'movie', 'showOverview' => false])
                        @endforeach
                    </div>

                    {{-- List view --}}
                    <div x-show="viewMode === 'list'" x-cloak class="space-y-2">
                        @foreach(array_slice($popularMovies, 0, 12) as $i => $movie)
                            @php
                                $mTitle = $movie['title'] ?? $movie['name'] ?? 'Untitled';
                                $mYear = Str::substr($movie['release_date'] ?? '', 0, 4);
                                $mRating = number_format($movie['vote_average'] ?? 0, 1);
                                $mGenres = collect($movie['genre_ids'] ?? [])->map(fn ($id) => $genreMap[$id] ?? null)->filter()->take(3)->implode(', ');
                            @endphp
                            <a href="{{ route('movies.detail', $movie['id']) }}" wire:navigate
                               class="flex items-center gap-4 rounded-xl border border-white/[0.04] bg-white/[0.02] p-3 transition hover:bg-white/[0.05]">
                                <span class="w-8 text-center text-sm font-bold tabular-nums text-zinc-600">{{ $i + 1 }}</span>
                                <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                                    @if(!empty($movie['poster_path']))
                                        <img src="{{ app(Tmdb::class)->imageUrl($movie['poster_path'], 'w92') }}" alt="{{ $mTitle }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-sm font-medium text-white">{{ $mTitle }}</h4>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $mGenres }}</p>
                                </div>
                                <div class="hidden items-center gap-4 text-xs text-zinc-500 sm:flex">
                                    <span>{{ $mYear }}</span>
                                    <span class="flex items-center gap-1 text-amber-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        {{ $mRating }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="{{ route('movies.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-white/[0.08] bg-white/[0.03] px-6 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white">
                        View All Movies
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>

            {{-- Series grid --}}
            <div x-show="contentTab === 'series'" x-cloak>
                <div x-show="viewMode === 'grid'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach(array_slice($popularTv, 0, 18) as $show)
                        @include('partials.media-card', ['item' => $show, 'type' => 'tv', 'showOverview' => false])
                    @endforeach
                </div>

                <div x-show="viewMode === 'list'" x-cloak class="space-y-2">
                    @foreach(array_slice($popularTv, 0, 12) as $i => $show)
                        @php
                            $sTitle = $show['name'] ?? $show['title'] ?? 'Untitled';
                            $sYear = Str::substr($show['first_air_date'] ?? '', 0, 4);
                            $sRating = number_format($show['vote_average'] ?? 0, 1);
                            $sGenres = collect($show['genre_ids'] ?? [])->map(fn ($id) => $genreMap[$id] ?? null)->filter()->take(3)->implode(', ');
                        @endphp
                        <a href="{{ route('tv.detail', $show['id']) }}" wire:navigate
                           class="flex items-center gap-4 rounded-xl border border-white/[0.04] bg-white/[0.02] p-3 transition hover:bg-white/[0.05]">
                            <span class="w-8 text-center text-sm font-bold tabular-nums text-zinc-600">{{ $i + 1 }}</span>
                            <div class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                                @if(!empty($show['poster_path']))
                                    <img src="{{ app(Tmdb::class)->imageUrl($show['poster_path'], 'w92') }}" alt="{{ $sTitle }}" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate text-sm font-medium text-white">{{ $sTitle }}</h4>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $sGenres }}</p>
                            </div>
                            <div class="hidden items-center gap-4 text-xs text-zinc-500 sm:flex">
                                <span>{{ $sYear }}</span>
                                <span class="flex items-center gap-1 text-amber-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ $sRating }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6 text-center">
                    <a href="{{ route('tv.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-white/[0.08] bg-white/[0.03] px-6 py-2.5 text-sm font-medium text-zinc-400 transition hover:bg-white/[0.06] hover:text-white">
                        View All Series
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Upcoming Movies --}}
        <div class="mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(count($upcoming) > 0)
                @include('partials.upcoming-row', ['title' => 'Upcoming Movies', 'items' => $upcoming, 'type' => 'movie'])
            @endif
        </div>

        {{-- Featured banner --}}
        @if($featured)
            <div class="mx-auto mt-16 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-2xl border border-white/[0.06]" style="height: clamp(280px, 40vh, 420px);">
                    @if($featured['backdrop'])
                        <img src="{{ $featured['backdrop'] }}" alt="{{ $featured['title'] }}" class="h-full w-full object-cover">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-zinc-900/50 to-transparent"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-zinc-900/90 via-zinc-900/40 to-transparent"></div>

                    <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-10">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex items-center gap-1 rounded-md bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $featured['rating'] }}
                            </span>
                            <span class="text-xs text-zinc-400">{{ $featured['year'] }}</span>
                            @foreach($featured['genres'] as $g)
                                <span class="hidden text-xs text-zinc-500 sm:inline">{{ $g }}</span>
                            @endforeach
                        </div>
                        <h2 class="mb-2 text-2xl font-black uppercase tracking-[0.1em] text-white sm:text-3xl md:text-4xl">{{ $featured['title'] }}</h2>
                        <p class="mb-4 hidden max-w-lg text-sm leading-relaxed text-zinc-300/80 sm:line-clamp-2">{{ $featured['overview'] }}</p>
                        <div class="flex items-center gap-3">
                            <a href="{{ $featured['watchUrl'] }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                WATCH
                            </a>
                            <a href="{{ $featured['detailUrl'] }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                                MORE INFO
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Top Rated row --}}
        <div class="mx-auto mt-14 max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            @include('partials.media-row', [
                'title' => 'Top Rated Movies',
                'items' => $topRated,
                'type' => 'movie',
                'style' => 'scroll',
            ])

            @include('partials.media-row', [
                'title' => 'Airing Today',
                'items' => $airingToday,
                'type' => 'tv',
                'style' => 'scroll',
                'seeAllRoute' => route('new-releases', ['tab' => 'tv']),
            ])
        </div>
    </div>
</div>
