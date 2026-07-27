<?php

use App\Models\WatchParty;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Dashboard — StreamVault')]
class extends Component
{
    public function removeFavorite(int $favoriteId): void
    {
        auth()->user()->favorites()->where('id', $favoriteId)->delete();
    }

    public function removeFromWatchlist(int $watchlistId): void
    {
        auth()->user()->watchlist()->where('id', $watchlistId)->delete();
    }

    public function clearHistory(): void
    {
        auth()->user()->watchHistory()->delete();
    }

    public function with(Tmdb $tmdb): array
    {
        $user = auth()->user();
        $watchHistory = $user->watchHistory()->latest('updated_at')->take(12)->get();
        $watchlistItems = $user->watchlist()->latest()->get();
        $favorites = $user->favorites()->latest()->get();

        $trending = [];
        $heroItems = [];
        $genreMap = [];

        try {
            $prefs = $user->preferences ?? [];
            $preferredType = \App\Support\UserPreferences::bool($prefs, 'disable_content_personalization', false)
                ? 'all'
                : ($prefs['preferred_type'] ?? 'all');
            $trendingType = $preferredType === 'all' ? 'all' : ($preferredType === 'tv' ? 'tv' : 'movie');

            $hour = (int) now()->format('G');
            $trendingWindow = $hour % 2 === 0 ? 'day' : 'week';
            $trendingResults = $tmdb->trending($trendingType, $trendingWindow)['results'] ?? [];

            $extraPool = [];
            if ($hour % 3 === 0) {
                $extraPool = $tmdb->popular('movie')['results'] ?? [];
            } elseif ($hour % 3 === 1) {
                $extraPool = $tmdb->topRated('movie')['results'] ?? [];
            } else {
                $extraPool = $tmdb->popular('tv')['results'] ?? [];
            }

            $seenIds = [];
            $merged = [];
            foreach ($trendingResults as $item) {
                $key = ($item['media_type'] ?? 'movie').'-'.$item['id'];
                if (! isset($seenIds[$key])) {
                    $seenIds[$key] = true;
                    $merged[] = $item;
                }
            }
            $extraType = $hour % 3 === 2 ? 'tv' : 'movie';
            foreach ($extraPool as $item) {
                $item['media_type'] = $item['media_type'] ?? $extraType;
                $key = $item['media_type'].'-'.$item['id'];
                if (! isset($seenIds[$key])) {
                    $seenIds[$key] = true;
                    $merged[] = $item;
                }
            }

            $offset = $hour % max(1, count($merged) - 12);
            $trending = array_slice($merged, $offset, 12);

            $movieGenres = $tmdb->genres('movie')['genres'] ?? [];
            $tvGenres = $tmdb->genres('tv')['genres'] ?? [];
            foreach (array_merge($movieGenres, $tvGenres) as $g) {
                $genreMap[$g['id']] = $g['name'];
            }

            $heroSlice = array_slice($merged, $offset, 6);
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
                    'poster' => ! empty($item['poster_path']) ? $tmdb->imageUrl($item['poster_path'], 'w92') : '',
                    'rating' => number_format($item['vote_average'] ?? 0, 1),
                    'lang' => strtoupper($item['original_language'] ?? 'en'),
                    'genres' => collect($item['genre_ids'] ?? [])->map(fn ($id) => $genreMap[$id] ?? null)->filter()->take(2)->values()->all(),
                    'type' => $type,
                    'overview' => Str::limit($item['overview'] ?? '', 150),
                    'year' => Str::substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
                    'watchUrl' => route('watch', ['type' => $type, 'tmdbId' => $item['id']]),
                    'detailUrl' => route($type === 'tv' ? 'tv.detail' : 'movies.detail', $item['id']),
                ];
            }
        } catch (\Throwable) {
        }

        $parties = WatchParty::with('host')
            ->where('is_active', true)
            ->latest('starts_at')
            ->limit(6)
            ->get();

        $recommendations = [];
        $recSource = '';
        if ($watchHistory->isNotEmpty()) {
            $sources = $watchHistory->take(3);
            $seenRecIds = [];
            foreach ($sources as $source) {
                try {
                    $details = $tmdb->details($source->media_type, $source->tmdb_id);
                    foreach (array_merge($details['recommendations']['results'] ?? [], $details['similar']['results'] ?? []) as $rec) {
                        $recKey = ($rec['media_type'] ?? $source->media_type).'-'.$rec['id'];
                        if (! isset($seenRecIds[$recKey])) {
                            $seenRecIds[$recKey] = true;
                            $rec['media_type'] = $rec['media_type'] ?? $source->media_type;
                            $recommendations[] = $rec;
                        }
                    }
                } catch (\Throwable) {
                }
            }
            shuffle($recommendations);
            $recommendations = array_slice($recommendations, 0, 12);
            $recSource = $sources->first()->title ?? '';
        }

        $tvProgress = [];
        $showsInProgress = $user->watchHistory()
            ->where('media_type', 'tv')
            ->latest('updated_at')
            ->get()
            ->unique('tmdb_id')
            ->take(6);
        foreach ($showsInProgress as $show) {
            $episodesWatched = $user->episodeWatches()->where('tmdb_id', $show->tmdb_id)->count();
            $totalEpisodes = 0;
            try {
                $showDetails = $tmdb->details('tv', $show->tmdb_id);
                $totalEpisodes = $showDetails['number_of_episodes'] ?? 0;
            } catch (\Throwable) {
            }
            if ($totalEpisodes > 0 && $episodesWatched > 0 && $episodesWatched < $totalEpisodes) {
                $tvProgress[] = [
                    'tmdb_id' => $show->tmdb_id,
                    'title' => $show->title,
                    'poster_path' => $show->poster_path,
                    'episodes_watched' => $episodesWatched,
                    'total_episodes' => $totalEpisodes,
                    'percent' => round(($episodesWatched / $totalEpisodes) * 100),
                    'season' => $show->season,
                    'episode' => $show->episode,
                ];
            }
        }

        $friendsWatchingCount = 0;
        if (! empty($heroItems)) {
            $followingIds = $user->following()->pluck('users.id');
            if ($followingIds->isNotEmpty()) {
                $friendsWatchingCount = \App\Models\WatchHistory::whereIn('user_id', $followingIds)
                    ->where('tmdb_id', $heroItems[0]['id'])
                    ->distinct('user_id')
                    ->count('user_id');
            }
        }

        return [
            'heroItems' => $heroItems,
            'trending' => $trending,
            'genreMap' => $genreMap,
            'parties' => $parties,
            'watchHistory' => $watchHistory,
            'showContinueWatching' => \App\Support\UserPreferences::bool($user->preferences, 'show_continue_watching', true),
            'watchlistItems' => $watchlistItems,
            'favorites' => $favorites,
            'recommendations' => $recommendations,
            'recSource' => $recSource,
            'tvProgress' => $tvProgress,
            'friendsWatchingCount' => $friendsWatchingCount,
        ];
    }
};
?>

<div>
    {{-- Hero Carousel --}}
    @if(count($heroItems) > 0)
        <section
            x-data="{
                items: @js($heroItems),
                active: 0,
                transitioning: false,
                progress: 0,
                duration: 8000,
                tick: null,
                friendsCount: {{ $friendsWatchingCount }},
                player: null,
                playerReady: false,
                trailerPlaying: false,
                muted: true,
                isMobile: window.innerWidth < 640,
                showMobileModal: false,
                ytApiLoaded: window.ytApiReady || false,
                init() {
                    this.startTimer();
                    window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 640; });
                    if (this.ytApiLoaded) {
                        setTimeout(() => this.initTrailer(), 800);
                    } else {
                        window.addEventListener('yt-api-ready', () => {
                            this.ytApiLoaded = true;
                            this.initTrailer();
                        }, { once: true });
                    }
                },
                destroy() {
                    this.destroyPlayer();
                    this.stopTimer();
                },
                startTimer() {
                    this.stopTimer();
                    this.progress = 0;
                    const step = 50;
                    this.tick = setInterval(() => {
                        this.progress += (step / this.duration) * 100;
                        if (this.progress >= 100) this.next();
                    }, step);
                },
                stopTimer() {
                    if (this.tick) clearInterval(this.tick);
                    this.tick = null;
                },
                goTo(idx) {
                    if (idx === this.active || this.transitioning) return;
                    this.destroyPlayer();
                    this.transitioning = true;
                    this.active = idx;
                    this.progress = 0;
                    this.startTimer();
                    setTimeout(() => {
                        this.transitioning = false;
                        this.initTrailer();
                    }, 800);
                },
                next() {
                    this.goTo((this.active + 1) % this.items.length);
                },
                prev() {
                    this.goTo((this.active - 1 + this.items.length) % this.items.length);
                },
                get current() { return this.items[this.active]; },
                initTrailer() {
                    if (this.isMobile || !this.ytApiLoaded || !this.current.trailerKey) return;
                    const containerId = 'yt-hero-' + this.current.id;
                    const el = document.getElementById(containerId);
                    if (!el) return;
                    el.innerHTML = '';
                    const playerDiv = document.createElement('div');
                    playerDiv.id = containerId + '-player';
                    el.appendChild(playerDiv);
                    this.player = new YT.Player(playerDiv.id, {
                        videoId: this.current.trailerKey,
                        playerVars: {
                            autoplay: 1,
                            mute: 1,
                            controls: 0,
                            modestbranding: 1,
                            rel: 0,
                            showinfo: 0,
                            iv_load_policy: 3,
                            disablekb: 1,
                            fs: 0,
                            playsinline: 1,
                            loop: 0,
                            origin: window.location.origin
                        },
                        events: {
                            onReady: (e) => {
                                this.playerReady = true;
                                this.muted = true;
                            },
                            onStateChange: (e) => {
                                if (e.data === YT.PlayerState.PLAYING) {
                                    this.trailerPlaying = true;
                                    this.stopTimer();
                                } else if (e.data === YT.PlayerState.ENDED) {
                                    this.trailerPlaying = false;
                                    this.next();
                                } else if (e.data === YT.PlayerState.PAUSED) {
                                    this.trailerPlaying = false;
                                }
                            }
                        }
                    });
                },
                destroyPlayer() {
                    if (this.player) {
                        try { this.player.destroy(); } catch(e) {}
                        this.player = null;
                    }
                    this.playerReady = false;
                    this.trailerPlaying = false;
                },
                toggleMute() {
                    if (!this.player || !this.playerReady) return;
                    this.muted = !this.muted;
                    this.muted ? this.player.mute() : this.player.unMute();
                },
                openMobileTrailer() {
                    if (!this.current.trailerKey) return;
                    this.showMobileModal = true;
                    this.stopTimer();
                },
                closeMobileTrailer() {
                    this.showMobileModal = false;
                    this.startTimer();
                }
            }"
            @mouseenter="stopTimer()"
            @mouseleave="if (!trailerPlaying) startTimer()"
            @touchstart.passive="stopTimer()"
            @touchend.passive="if (!trailerPlaying) startTimer()"
            class="relative mb-6 overflow-hidden rounded-xl border border-white/[0.06] bg-zinc-900 sm:mb-8 sm:rounded-2xl"
            style="min-height: 320px;"
        >
            {{-- Backdrop layers --}}
            <template x-for="(item, idx) in items" :key="item.id">
                <div
                    x-show="active === idx"
                    x-transition:enter="transition-opacity ease-out duration-700"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity ease-in duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0"
                >
                    <img x-show="item.backdrop" :src="item.backdrop" alt="" class="h-full w-full object-cover object-top">
                    {{-- YouTube player container --}}
                    <div
                        x-show="active === idx && trailerPlaying"
                        :id="'yt-hero-' + item.id"
                        class="absolute inset-0 z-10 [&_iframe]:h-full [&_iframe]:w-full [&_iframe]:object-cover"
                        style="pointer-events: none;"
                    ></div>
                    <div class="absolute inset-0 z-20 bg-gradient-to-r from-zinc-900 via-zinc-900/90 to-zinc-900/30 sm:via-zinc-900/80 sm:to-zinc-900/10"></div>
                    <div class="absolute inset-0 z-20 bg-gradient-to-t from-zinc-900 via-zinc-900/50 to-transparent sm:via-zinc-900/30"></div>
                </div>
            </template>

            {{-- Mobile trailer play button --}}
            <button
                x-show="isMobile && current.trailerKey"
                @click="openMobileTrailer()"
                class="absolute right-3 top-3 z-40 flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm transition active:scale-95 sm:hidden"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                Trailer
            </button>

            {{-- Content --}}
            <div class="relative z-30 flex min-h-[320px] flex-col justify-end p-4 sm:min-h-[380px] sm:p-8 lg:min-h-[420px] lg:p-10">
                <div class="max-w-xl">
                    {{-- Trending badge --}}
                    <div class="mb-2 flex items-center gap-1.5 sm:mb-3 sm:gap-2">
                        <span class="rounded bg-red-600 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white sm:px-2 sm:text-[10px]">
                            #<span x-text="active + 1"></span> Trending
                        </span>
                        <span class="rounded bg-white/[0.1] px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-300 backdrop-blur-sm sm:px-2 sm:text-[10px]" x-text="current.type === 'tv' ? 'TV Show' : 'Movie'"></span>
                    </div>

                    {{-- Title: logo image when available, text fallback --}}
                    <template x-if="current.logo">
                        <div class="my-1 sm:my-2">
                            <img :src="current.logo" :alt="current.title" class="max-h-14 max-w-[280px] drop-shadow-[0_4px_24px_rgba(0,0,0,0.7)] sm:max-h-24 sm:max-w-sm md:max-h-28 lg:max-h-36 lg:max-w-md">
                        </div>
                    </template>
                    <template x-if="!current.logo">
                        <h1 class="text-2xl font-black uppercase leading-[1.1] tracking-tight text-white sm:text-4xl md:text-5xl lg:text-6xl">
                            <span x-text="current.title"></span>
                        </h1>
                    </template>

                    {{-- Meta --}}
                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs sm:mt-3 sm:gap-2.5 sm:text-sm">
                        <span class="flex items-center gap-1 rounded-md bg-amber-500/20 px-1.5 py-0.5 font-bold text-amber-400 sm:gap-1.5 sm:px-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3 sm:size-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span x-text="current.rating"></span>
                        </span>
                        <span class="hidden text-zinc-600 sm:inline">&bull;</span>
                        <span class="hidden font-medium text-zinc-300 sm:inline" x-text="current.lang"></span>
                        <template x-if="current.year">
                            <span class="flex items-center gap-1.5 sm:gap-2.5">
                                <span class="text-zinc-600">&bull;</span>
                                <span class="text-zinc-400" x-text="current.year"></span>
                            </span>
                        </template>
                        <template x-if="current.genres.length">
                            <span class="hidden items-center gap-2.5 sm:flex">
                                <span class="text-zinc-600">&bull;</span>
                                <span class="text-zinc-400" x-text="current.genres.join(', ')"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Overview --}}
                    <p x-show="current.overview && !trailerPlaying" x-text="current.overview" class="mt-2 hidden text-sm leading-relaxed text-zinc-400 sm:mt-3 sm:line-clamp-2 sm:block lg:line-clamp-3"></p>

                    {{-- Actions --}}
                    <div class="mt-3 flex items-center gap-2 sm:mt-5 sm:flex-wrap sm:gap-3">
                        <a :href="current.watchUrl"
                           class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-red-600/30 transition hover:bg-red-500 hover:shadow-red-500/40 sm:gap-2 sm:px-6 sm:py-3 sm:text-sm"
                           wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 sm:size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Watch
                        </a>
                        <a :href="current.detailUrl"
                           class="inline-flex items-center gap-1.5 rounded-full border border-white/[0.15] bg-white/[0.06] px-3.5 py-2 text-xs font-medium text-zinc-200 backdrop-blur-sm transition hover:border-white/[0.25] hover:bg-white/[0.1] hover:text-white sm:gap-2 sm:px-5 sm:py-3 sm:text-sm"
                           wire:navigate>
                            More Info
                        </a>

                        {{-- Mute/unmute toggle --}}
                        <button
                            x-show="trailerPlaying && !isMobile"
                            @click="toggleMute()"
                            x-cloak
                            class="hidden items-center justify-center rounded-full border border-white/[0.15] bg-black/40 p-2.5 text-white backdrop-blur-sm transition hover:bg-black/60 sm:inline-flex"
                            :title="muted ? 'Unmute' : 'Mute'"
                        >
                            <svg x-show="muted" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m17 14-4-4m0 0 4-4m-4 4h0" x-show="false" /><line x1="18" y1="9" x2="22" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><line x1="22" y1="9" x2="18" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                            <svg x-show="!muted" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M5.586 15H4a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15Z" /></svg>
                        </button>

                        @if($friendsWatchingCount > 0)
                            <div x-show="active === 0 && !trailerPlaying" class="hidden items-center gap-2 text-sm text-zinc-400 sm:flex">
                                <div class="flex -space-x-2">
                                    @for($i = 0; $i < min($friendsWatchingCount, 3); $i++)
                                        <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-zinc-600 to-zinc-700 text-[9px] font-bold text-white ring-2 ring-zinc-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                                        </div>
                                    @endfor
                                </div>
                                <span>+{{ $friendsWatchingCount }} {{ Str::plural('friend', $friendsWatchingCount) }} watching</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Thumbnail carousel with progress indicators --}}
                <div class="mt-4 flex items-center gap-2 sm:mt-6 sm:gap-3">
                    <button @click="prev()" class="hidden size-8 shrink-0 items-center justify-center rounded-full border border-white/[0.1] bg-white/[0.04] text-zinc-400 transition hover:border-red-500/40 hover:bg-red-500/10 hover:text-red-400 sm:flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>

                    <div class="scrollbar-hide flex items-center gap-1.5 overflow-x-auto sm:gap-2">
                        <template x-for="(item, idx) in items" :key="'thumb-' + item.id">
                            <button @click="goTo(idx)" class="relative block shrink-0 overflow-hidden rounded-md transition-all duration-300 sm:rounded-lg" :class="active === idx ? 'h-12 w-9 scale-105 sm:h-16 sm:w-12' : 'h-10 w-7 opacity-60 hover:opacity-100 sm:h-16 sm:w-12'">
                                <img :src="item.poster" alt="" class="h-full w-full object-cover" x-show="item.poster">
                                {{-- Progress border overlay --}}
                                <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 48 64" fill="none" x-show="active === idx && !trailerPlaying">
                                    <rect x="1" y="1" width="46" height="62" rx="7" stroke-width="2.5"
                                          class="stroke-red-500"
                                          :stroke-dasharray="(46+62)*2"
                                          :stroke-dashoffset="((46+62)*2) - (((46+62)*2) * (progress / 100))"
                                          style="transition: stroke-dashoffset 50ms linear;"
                                    />
                                </svg>
                                {{-- Solid border when trailer is playing --}}
                                <div x-show="active === idx && trailerPlaying" class="absolute inset-0 rounded-md border-2 border-red-500 sm:rounded-lg"></div>
                                <div class="absolute inset-0 rounded-md border-2 transition-colors duration-300 sm:rounded-lg" :class="active === idx ? 'border-transparent' : 'border-white/[0.08] hover:border-white/[0.2]'" x-show="!(active === idx && trailerPlaying)"></div>
                            </button>
                        </template>
                    </div>

                    <button @click="next()" class="hidden size-8 shrink-0 items-center justify-center rounded-full border border-white/[0.1] bg-white/[0.04] text-zinc-400 transition hover:border-red-500/40 hover:bg-red-500/10 hover:text-red-400 sm:flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>

                    {{-- Mobile dot indicators --}}
                    <div class="ml-auto flex items-center gap-1 sm:hidden">
                        <template x-for="(item, idx) in items" :key="'dot-' + item.id">
                            <button @click="goTo(idx)" class="rounded-full transition-all duration-300" :class="active === idx ? 'h-1.5 w-4 bg-red-500' : 'size-1.5 bg-white/30'"></button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Mobile trailer modal --}}
            <template x-teleport="body">
                <div
                    x-show="showMobileModal"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click.self="closeMobileTrailer()"
                    @keydown.escape.window="closeMobileTrailer()"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 p-4"
                    x-cloak
                >
                    <div class="w-full max-w-lg">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-sm font-medium text-white" x-text="current.title"></span>
                            <button @click="closeMobileTrailer()" class="flex items-center gap-1 text-sm text-zinc-400 transition hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="aspect-video overflow-hidden rounded-xl bg-zinc-900">
                            <iframe
                                x-show="showMobileModal && current.trailerKey"
                                :src="showMobileModal && current.trailerKey ? 'https://www.youtube.com/embed/' + current.trailerKey + '?autoplay=1&rel=0&modestbranding=1' : ''"
                                frameborder="0"
                                allowfullscreen
                                allow="autoplay; encrypted-media"
                                class="h-full w-full"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </template>
        </section>
    @endif

    {{-- Parties --}}
    @if($parties->isNotEmpty())
        <section class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-white">Parties</h2>
                <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                <a href="{{ route('watch-parties') }}" class="text-sm font-medium text-zinc-500 transition hover:text-white" wire:navigate>See All</a>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                @foreach($parties as $party)
                    @include('partials.party-card', ['party' => $party, 'variant' => 'compact'])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Continue Watching --}}
    @if($showContinueWatching && $watchHistory->isNotEmpty())
        <section class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-white">Continue Watching</h2>
                <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                <button wire:click="clearHistory" wire:confirm="Clear all watch history?" class="text-sm font-medium text-zinc-500 transition hover:text-white">Clear</button>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                @foreach($watchHistory as $item)
                    <div class="w-36 shrink-0 sm:w-40">
                        @include('partials.media-card', ['item' => $item, 'variant' => 'watch'])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Watchlist --}}
    @if($watchlistItems->isNotEmpty())
        <section class="mb-8" id="watchlist">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-white">My Watchlist</h2>
                <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                <span class="text-sm tabular-nums text-zinc-500">{{ $watchlistItems->count() }} {{ Str::plural('title', $watchlistItems->count()) }}</span>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                @foreach($watchlistItems as $wl)
                    <div class="w-36 shrink-0 sm:w-40">
                        @include('partials.media-card', ['item' => $wl, 'removable' => true, 'removeAction' => 'removeFromWatchlist', 'removeId' => $wl->id])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Favorites --}}
    @if($favorites->isNotEmpty())
        <section class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-white">Favorites</h2>
                <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                <span class="text-sm tabular-nums text-zinc-500">{{ $favorites->count() }} {{ Str::plural('title', $favorites->count()) }}</span>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                @foreach($favorites as $fav)
                    <div class="w-36 shrink-0 sm:w-40">
                        @include('partials.media-card', ['item' => $fav, 'removable' => true, 'removeAction' => 'removeFavorite', 'removeId' => $fav->id])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- TV Show Progress --}}
    @if(count($tvProgress) > 0)
        <section class="mb-8">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-white">Episode Progress</h2>
                <span class="size-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
                <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                @foreach($tvProgress as $show)
                    <a href="{{ route('tv.detail', $show['tmdb_id']) }}" class="group w-36 shrink-0 sm:w-40" wire:navigate>
                        <div class="relative overflow-hidden rounded-xl border border-white/[0.06] transition group-hover:border-white/[0.12]">
                            @if($show['poster_path'])
                                <img src="https://image.tmdb.org/t/p/w342{{ $show['poster_path'] }}" alt="{{ $show['title'] }}" class="aspect-[2/3] w-full object-cover">
                            @else
                                <div class="flex aspect-[2/3] w-full items-center justify-center bg-zinc-800 text-zinc-600 text-xs">No Poster</div>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-3 pt-8">
                                <div class="mb-1.5 h-1 overflow-hidden rounded-full bg-white/[0.15]">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ $show['percent'] }}%"></div>
                                </div>
                                <p class="text-[10px] font-medium tabular-nums text-zinc-300">{{ $show['episodes_watched'] }}/{{ $show['total_episodes'] }} episodes</p>
                            </div>
                        </div>
                        <p class="mt-2 truncate text-sm font-medium text-zinc-300 group-hover:text-white">{{ $show['title'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Recommendations --}}
    @if(count($recommendations) > 0)
        @include('partials.media-row', [
            'title' => $recSource ? "Because you watched {$recSource}" : 'Recommended for You',
            'items' => $recommendations,
            'style' => 'scroll',
        ])
    @endif

    {{-- Trending Today --}}
    @if(count($trending) > 0)
        <div class="mt-8">
            @include('partials.media-row', ['title' => 'Trending Today', 'items' => $trending, 'style' => 'scroll'])
        </div>
    @endif

    <div class="pb-8"></div>
</div>
