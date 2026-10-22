<?php

use App\Services\PersonalizedRows;
use App\Services\RottenTomatoes;
use App\Services\TopTen;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('StreamVault — Free Movie & TV Streaming')]
class extends Component
{
    public function with(Tmdb $tmdb, RottenTomatoes $rottenTomatoes, PersonalizedRows $personalizedRows, TopTen $topTen): array
    {
        $continueWatching = auth()->check()
            ? auth()->user()->watchHistory()->visible()->limit(10)->get()
            : collect();
        $personalRows = auth()->check() ? $personalizedRows->forUser(auth()->user()) : [];

        $trending = $tmdb->trending('all', 'week')['results'] ?? [];
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
        $topTenMovies = $topTen->forType('movie');
        $topTenShows = $topTen->forType('tv');

        $rtNetflixTop = $rottenTomatoes->toTmdbCards($rottenTomatoes->netflixTopTv(24), $tmdb, 12);
        $rtQueryMovies = $rottenTomatoes->toTmdbCards($rottenTomatoes->queryMovies(1, null, 24), $tmdb, 12);
        $tmdbAnimeTv = array_map(
            fn (array $item): array => array_merge($item, ['media_type' => 'tv']),
            $tmdb->anime('tv')['results'] ?? []
        );
        $tmdbAnimeMovies = array_map(
            fn (array $item): array => array_merge($item, ['media_type' => 'movie']),
            $tmdb->anime('movie')['results'] ?? []
        );
        $topAnime = array_slice($tmdbAnimeTv, 0, 12);
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
            'popularMovies' => $popularMovies,
            'popularTv' => $popularTv,
            'topRated' => $topRated,
            'topRatedTv' => $topRatedTv,
            'nowPlaying' => $nowPlaying,
            'upcoming' => $upcoming,
            'airingToday' => $airingToday,
            'topTenMovies' => $topTenMovies,
            'topTenShows' => $topTenShows,
            'continueWatching' => $continueWatching,
            'personalRows' => $personalRows,
            'genreMap' => $genreMap,
            'movieGenres' => $movieGenres,
            'featured' => $featured,
            'rtNetflixTop' => $rtNetflixTop,
            'rtQueryMovies' => $rtQueryMovies,
            'topAnime' => $topAnime,
            'tmdbAnimeMovies' => $tmdbAnimeMovies,
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
                playing: false,
                muted: true,
                trailerTimer: null,
                canAutoplay: window.matchMedia('(min-width: 768px)').matches
                    && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                init() {
                    this.startTimer();
                    this.scheduleTrailer();
                },
                startTimer() {
                    clearInterval(this.timer);
                    this.timer = setInterval(() => this.next(), 8000);
                },
                scheduleTrailer() {
                    clearTimeout(this.trailerTimer);
                    this.playing = false;
                    if (! this.canAutoplay || ! this.cur.trailerKey) return;
                    this.trailerTimer = setTimeout(() => {
                        clearInterval(this.timer);
                        this.playing = true;
                        this.trailerTimer = setTimeout(() => this.next(), 60000);
                    }, 3000);
                },
                stopTrailer() {
                    clearTimeout(this.trailerTimer);
                    this.playing = false;
                    this.startTimer();
                },
                toggleMute() {
                    this.muted = ! this.muted;
                    this.$refs.heroTrailer?.contentWindow?.postMessage(
                        JSON.stringify({ event: 'command', func: this.muted ? 'mute' : 'unMute', args: [] }),
                        '*'
                    );
                },
                show(index) {
                    this.transitioning = true;
                    this.playing = false;
                    this.muted = true;
                    setTimeout(() => {
                        this.current = index;
                        this.transitioning = false;
                        this.scheduleTrailer();
                    }, 300);
                    this.startTimer();
                },
                next() {
                    this.show((this.current + 1) % this.total);
                },
                prev() {
                    this.show((this.current - 1 + this.total) % this.total);
                },
                goTo(i) {
                    if (i === this.current) return;
                    this.show(i);
                },
                get cur() { return this.items[this.current]; }
            }"
            x-intersect:leave="stopTrailer()"
            x-intersect:enter="playing || scheduleTrailer()"
            class="hero-bleed relative h-[clamp(500px,80vh,800px)] w-full overflow-hidden lg:h-[clamp(564px,calc(80vh_+_4rem),864px)]"
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

            {{-- Trailer: replaces the backdrop after a short pause, like a Netflix billboard --}}
            <template x-if="playing && cur.trailerKey">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <iframe
                        x-ref="heroTrailer"
                        :src="`https://www.youtube-nocookie.com/embed/${cur.trailerKey}?autoplay=1&mute=1&controls=0&modestbranding=1&rel=0&playsinline=1&iv_load_policy=3&disablekb=1&enablejsapi=1`"
                        :title="cur.title + ' trailer'"
                        tabindex="-1"
                        allow="autoplay; encrypted-media"
                        class="absolute left-1/2 top-1/2 h-[56.25vw] min-h-full w-[177.78vh] min-w-full -translate-x-1/2 -translate-y-1/2 scale-125"
                    ></iframe>
                </div>
            </template>

            {{-- Gradients --}}
            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/40 to-zinc-950/10"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/80 via-zinc-950/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-zinc-950 to-transparent"></div>

            {{-- Hero Content --}}
            <div class="absolute bottom-0 left-0 right-0 px-4 pb-20 sm:px-8 md:px-16 md:pb-24">
                <div class="mx-auto max-w-7xl">
                    {{-- Metadata line --}}
                    <div class="mb-4 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-sm font-medium text-zinc-200 drop-shadow">
                        <span class="text-xs font-bold uppercase tracking-[0.2em] text-amber-300" x-text="cur.type === 'tv' ? 'Series' : 'Film'"></span>
                        <span class="text-zinc-500">•</span>
                        <span class="font-semibold text-emerald-400" x-show="cur.rating > 0" x-text="Math.round(cur.rating * 10) + '% match'"></span>
                        <span x-text="cur.year"></span>
                        <template x-for="g in cur.genres" :key="g">
                            <span class="hidden text-zinc-300 before:mr-2.5 before:text-zinc-500 before:content-['•'] sm:inline" x-text="g"></span>
                        </template>
                    </div>

                    {{-- Title (logo or text) --}}
                    <div class="mb-4 max-w-2xl">
                        <template x-if="cur.logo">
                            <img :src="cur.logo" :alt="cur.title" class="max-h-20 w-auto drop-shadow-[0_2px_10px_rgba(0,0,0,0.7)] sm:max-h-24 md:max-h-28">
                        </template>
                        <template x-if="!cur.logo">
                            <h1 class="text-4xl font-black leading-none tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl" x-text="cur.title"></h1>
                        </template>
                    </div>

                    {{-- Overview --}}
                    <p class="mb-6 hidden max-w-xl text-sm leading-relaxed text-zinc-300/90 sm:line-clamp-3 md:line-clamp-none" x-text="cur.overview"></p>

                    {{-- Buttons --}}
                    <div class="flex items-center gap-3">
                        <a :href="cur.watchUrl"
                           class="inline-flex items-center gap-2 rounded-md bg-white px-5 py-2.5 text-sm font-bold text-black shadow-lg transition hover:bg-white/80 sm:px-7 sm:py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            <span>Play</span>
                        </a>
                        <a :href="cur.detailUrl"
                           @click="if (! $event.ctrlKey && ! $event.metaKey && ! $event.shiftKey && ! $event.altKey && $event.button === 0) { $event.preventDefault(); $event.stopImmediatePropagation(); $dispatch('open-title', { type: cur.type, id: cur.id }) }"
                           class="inline-flex items-center gap-2 rounded-md bg-zinc-500/40 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-zinc-500/30 sm:px-6 sm:py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            <span>More Info</span>
                        </a>
                        <button
                            type="button"
                            x-show="playing"
                            x-transition.opacity
                            @click="toggleMute()"
                            :aria-label="muted ? 'Unmute trailer' : 'Mute trailer'"
                            class="ml-auto inline-flex size-10 items-center justify-center rounded-full border border-white/40 bg-black/30 text-white transition hover:border-white hover:bg-black/50"
                            style="display: none;"
                        >
                            <svg x-show="muted" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75 19.5 12m0 0 2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6 4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" /></svg>
                            <svg x-show="! muted" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z" /></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Thumbnail navigation (right side) --}}
            <div class="absolute bottom-20 right-4 hidden flex-col gap-2 md:right-10 md:flex lg:right-16">
                @foreach($heroItems as $i => $hero)
                    <button
                        @click="goTo({{ $i }})"
                        class="relative overflow-hidden rounded-lg transition-all duration-300"
                        :class="current === {{ $i }} ? 'ring-2 ring-amber-400 w-16 h-10' : 'w-14 h-9 opacity-50 hover:opacity-80'"
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
                        :class="current === {{ $i }} ? 'w-8 bg-amber-400' : 'w-3 bg-white/30'"
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

    {{-- Rows --}}
    <div class="relative z-10 mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:-mt-16 lg:px-8">
        @if($continueWatching->isNotEmpty())
            @include('partials.media-row', ['title' => 'Continue Watching', 'items' => $continueWatching, 'variant' => 'watch', 'style' => 'scroll'])
        @endif

        @foreach($personalRows as $row)
            <div wire:key="personal-row-{{ $loop->index }}">
                @include('partials.media-row', ['title' => $row['title'], 'items' => $row['items'], 'style' => 'scroll'])
            </div>
        @endforeach

        @include('partials.media-row', ['title' => 'Trending Now', 'items' => $trending, 'style' => 'scroll', 'seeAllRoute' => route('trending')])
    </div>

    @include('partials.top-ten-row', ['title' => 'Top 10 Movies This Week', 'items' => $topTenMovies, 'type' => 'movie'])

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.media-row', ['title' => 'New & Airing Anime', 'items' => $topAnime, 'type' => 'tv', 'style' => 'scroll', 'seeAllRoute' => route('anime.index')])
        @include('partials.media-row', ['title' => 'Popular Movies', 'items' => $popularMovies, 'type' => 'movie', 'style' => 'scroll', 'seeAllRoute' => route('movies.index')])
    </div>

    @include('partials.top-ten-row', ['title' => 'Top 10 Shows This Week', 'items' => $topTenShows, 'type' => 'tv'])

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.media-row', ['title' => 'Popular Series', 'items' => $popularTv, 'type' => 'tv', 'style' => 'scroll', 'seeAllRoute' => route('tv.index')])
        @include('partials.media-row', ['title' => 'In Cinemas Now', 'items' => $nowPlaying, 'type' => 'movie', 'style' => 'scroll', 'seeAllRoute' => route('new-releases')])

        @if(count($upcoming) > 0)
            @include('partials.upcoming-row', ['title' => 'Coming Soon', 'items' => $upcoming, 'type' => 'movie'])
        @endif
    </div>

    {{-- Featured spotlight --}}
    @if($featured)
        <section class="reveal mx-auto mt-14 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Spotlight">
            <div class="relative isolate overflow-hidden rounded-2xl ring-1 ring-white/[0.06]" style="min-height: clamp(300px, 42vh, 460px);">
                @if($featured['backdrop'])
                    <img src="{{ $featured['backdrop'] }}" alt="" loading="lazy" class="absolute inset-0 -z-10 size-full object-cover">
                @endif
                <div class="absolute inset-0 -z-10 bg-gradient-to-r from-zinc-950 via-zinc-950/70 to-transparent"></div>
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-zinc-950/80 to-transparent"></div>

                <div class="flex h-full min-h-[inherit] max-w-xl flex-col justify-end p-6 sm:p-10">
                    <p class="eyebrow">Spotlight</p>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $featured['title'] }}</h2>
                    <div class="mt-2 flex flex-wrap items-center gap-x-2.5 text-sm text-zinc-300">
                        @if((float) $featured['rating'] > 0)
                            <span class="font-semibold text-emerald-400">{{ round((float) $featured['rating'] * 10) }}% match</span>
                        @endif
                        <span>{{ $featured['year'] }}</span>
                        @foreach($featured['genres'] as $genre)
                            <span class="hidden before:mr-2.5 before:text-zinc-500 before:content-['•'] sm:inline">{{ $genre }}</span>
                        @endforeach
                    </div>
                    <p class="mt-3 hidden text-sm leading-relaxed text-zinc-300 sm:line-clamp-3">{{ $featured['overview'] }}</p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ $featured['watchUrl'] }}" class="btn-play">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            Play
                        </a>
                        <a href="{{ $featured['detailUrl'] }}"
                           @click="if (! $event.ctrlKey && ! $event.metaKey && ! $event.shiftKey && ! $event.altKey && $event.button === 0) { $event.preventDefault(); $dispatch('open-title', { type: '{{ $featured['type'] }}', id: {{ (int) $featured['id'] }} }) }"
                           class="btn-secondary">
                            More Info
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @include('partials.media-row', ['title' => 'Critically Acclaimed Movies', 'items' => $topRated, 'type' => 'movie', 'style' => 'scroll', 'seeAllRoute' => route('movies.index', ['category' => 'top_rated'])])
        @include('partials.media-row', ['title' => 'Critically Acclaimed Series', 'items' => $topRatedTv, 'type' => 'tv', 'style' => 'scroll'])
        @include('partials.media-row', ['title' => 'Anime Movies', 'items' => $tmdbAnimeMovies, 'type' => 'movie', 'style' => 'scroll', 'seeAllRoute' => route('anime.index')])
        @include('partials.media-row', ['title' => 'New Episodes Today', 'items' => $airingToday, 'type' => 'tv', 'style' => 'scroll', 'seeAllRoute' => route('new-releases', ['tab' => 'tv'])])
        @include('partials.media-row', ['title' => 'Netflix Top on Rotten Tomatoes', 'items' => $rtNetflixTop, 'type' => 'tv', 'style' => 'scroll'])
        @include('partials.media-row', ['title' => 'Fresh on Rotten Tomatoes', 'items' => $rtQueryMovies, 'type' => 'movie', 'style' => 'scroll'])

        {{-- Browse by genre --}}
        @if(count($movieGenres) > 0)
            <section class="reveal mt-14" aria-label="Browse by genre">
                <div class="mb-3 flex items-end justify-between gap-4">
                    <h2 class="section-title">Browse by genre</h2>
                    <a href="{{ route('genres.index') }}" class="text-xs font-semibold text-zinc-400 transition hover:text-amber-300" wire:navigate>All genres</a>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach(array_slice($movieGenres, 0, 12) as $genre)
                        <a href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}" wire:navigate
                           class="group relative overflow-hidden rounded-xl border border-white/[0.06] bg-gradient-to-br from-zinc-800/80 to-zinc-900 px-4 py-5 transition hover:-translate-y-0.5 hover:border-amber-400/40">
                            <span class="relative font-bold text-white">{{ $genre['name'] }}</span>
                            <span class="absolute -bottom-6 -right-4 text-7xl font-black text-white/[0.04] transition group-hover:text-amber-400/10" aria-hidden="true">{{ mb_substr($genre['name'], 0, 1) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
