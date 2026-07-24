@php
    $compact = $compact ?? false;
    $isModel = $item instanceof \Illuminate\Database\Eloquent\Model;
    $tmdb = app(\App\Services\Tmdb::class);

    $mediaType = $isModel ? $item->media_type : ($item['media_type'] ?? ($type ?? 'movie'));
    $itemTitle = $isModel ? $item->title : ($item['title'] ?? $item['name'] ?? 'Untitled');
    $posterPath = $isModel ? $item->poster_path : ($item['poster_path'] ?? '');
    $tmdbId = $isModel ? $item->tmdb_id : $item['id'];
    $rating = $isModel ? 0 : ($item['vote_average'] ?? 0);
    $releaseDate = $isModel
        ? ($item->release_date ?? '')
        : ($item['release_date'] ?? $item['first_air_date'] ?? '');
    $year = $releaseDate ? Str::substr($releaseDate, 0, 4) : '';
    $isUpcoming = $releaseDate && $releaseDate > now()->toDateString();
    $overview = $isModel ? '' : ($item['overview'] ?? '');

    $hasProgress = $isModel && isset($item->duration_seconds) && $item->duration_seconds > 0;
    $progressPercent = $hasProgress ? $item->progressPercent() : 0;
    $season = $isModel ? ($item->season ?? null) : null;
    $episode = $isModel ? ($item->episode ?? null) : null;

    $variant = $variant ?? 'default';
    $showOverview = $showOverview ?? false;
    $removable = $removable ?? false;
    $removeAction = $removeAction ?? null;
    $removeId = $removeId ?? null;

    $detailRoute = $mediaType === 'tv' ? 'tv.detail' : 'movies.detail';

    if ($variant === 'watch' && $isModel) {
        $href = $mediaType === 'tv'
            ? route('watch', ['type' => 'tv', 'tmdbId' => $tmdbId, 'season' => $season ?? 1, 'episode' => $episode ?? 1])
            : route('watch', ['type' => 'movie', 'tmdbId' => $tmdbId]);
    } else {
        $href = route($detailRoute, $tmdbId);
    }
@endphp

<div
    class="group/card relative"
    x-data="{
        hover: false,
        loaded: false,
        loading: false,
        detail: null,
        hoverTimer: null,
        leaveTimer: null,
        trailerPlaying: false,
        enter() {
            clearTimeout(this.leaveTimer);
            this.hoverTimer = setTimeout(() => {
                this.hover = true;
                if (!this.loaded && !this.loading) this.fetchDetail();
            }, 400);
        },
        leave() {
            clearTimeout(this.hoverTimer);
            this.leaveTimer = setTimeout(() => {
                this.hover = false;
                this.trailerPlaying = false;
            }, 200);
        },
        async fetchDetail() {
            this.loading = true;
            try {
                const res = await fetch(`{{ route('api.media.show', ['type' => '__TYPE__', 'id' => '__ID__']) }}`.replace('__TYPE__', '{{ $mediaType }}').replace('__ID__', '{{ $tmdbId }}'));
                this.detail = await res.json();
                this.loaded = true;
            } catch(e) {} finally {
                this.loading = false;
            }
        },
        async toggleAction(action) {
            const url = action === 'watchlist'
                ? `{{ route('api.media.watchlist', ['type' => '__TYPE__', 'id' => '__ID__']) }}`.replace('__TYPE__', '{{ $mediaType }}').replace('__ID__', '{{ $tmdbId }}')
                : `{{ route('api.media.favorite', ['type' => '__TYPE__', 'id' => '__ID__']) }}`.replace('__TYPE__', '{{ $mediaType }}').replace('__ID__', '{{ $tmdbId }}');
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                if (res.status === 401) {
                    Livewire.navigate('{{ route('login') }}');
                    return;
                }
                const data = await res.json();
                if (this.detail) {
                    if (action === 'watchlist') this.detail.is_watchlisted = data.added;
                    else this.detail.is_favorited = data.added;
                }
            } catch(e) {}
        },
        formatRuntime(min) {
            if (!min) return '';
            const h = Math.floor(min / 60);
            const m = min % 60;
            return h > 0 ? h + 'h ' + (m > 0 ? m + 'm' : '') : m + 'm';
        }
    }"
    @mouseenter="enter()"
    @mouseleave="leave()"
    @touchstart.passive="enter()"
>
    {{-- Base card --}}
    <a href="{{ $href }}" class="block" wire:navigate>
        <div class="relative aspect-[2/3] overflow-hidden rounded-xl border border-white/[0.06] bg-zinc-800/50">
            @if($posterPath)
                <img
                    src="{{ $tmdb->imageUrl($posterPath, 'w342') }}"
                    alt="{{ $itemTitle }}"
                    class="h-full w-full object-cover transition duration-500 group-hover/card:scale-105"
                    loading="lazy"
                >
            @else
                <div class="flex h-full items-center justify-center text-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
            @endif

            {{-- Gradient overlay --}}
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover/card:opacity-100"></div>

            {{-- Play button --}}
            <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-all duration-300 group-hover/card:opacity-100">
                <div class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-md transition-transform duration-200 group-hover/card:scale-100 hover:bg-white/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>

            {{-- Progress bar --}}
            @if($hasProgress)
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                    <div class="h-full rounded-r-full bg-red-500" style="width: {{ $progressPercent }}%"></div>
                </div>
            @endif

            {{-- Rating badge --}}
            @if($rating > 0 && !$hasProgress)
                <div class="absolute right-2 top-2 flex items-center gap-0.5 rounded-md bg-black/60 px-1.5 py-0.5 text-xs font-bold tabular-nums text-amber-400 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    {{ number_format($rating, 1) }}
                </div>
            @endif

            {{-- Season/episode badge --}}
            @if($season && $episode)
                <span class="absolute left-2 top-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-bold text-zinc-300 backdrop-blur-sm">S{{ $season }}E{{ $episode }}</span>
            @endif

            {{-- Upcoming badge --}}
            @if($isUpcoming)
                <div class="absolute left-2 top-2 rounded-md bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                    Coming Soon
                </div>
            @endif

            {{-- Media type pill --}}
            <div class="absolute bottom-2 left-2 opacity-0 transition-opacity duration-200 group-hover/card:opacity-100">
                <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase backdrop-blur-sm {{ $mediaType === 'tv' ? 'bg-purple-500/20 text-purple-300' : 'bg-blue-500/20 text-blue-300' }}">{{ $mediaType }}</span>
            </div>
        </div>

        <div class="mt-2">
            <h3 class="truncate text-sm font-medium text-zinc-300 transition group-hover/card:text-white">{{ Str::limit($itemTitle, 28) }}</h3>
            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                @if($hasProgress)
                    <span class="tabular-nums">{{ $progressPercent }}% watched</span>
                @elseif($year)
                    <span>{{ $year }}</span>
                    @if($isUpcoming && $releaseDate)
                        <span class="text-red-400">{{ \Carbon\Carbon::parse($releaseDate)->format('M d') }}</span>
                    @endif
                @endif
            </div>
        </div>
    </a>

    {{-- Remove button --}}
    @if($removable && $removeAction && $removeId)
        <button
            wire:click="{{ $removeAction }}({{ $removeId }})"
            class="absolute right-2 top-2 z-10 rounded-full bg-black/60 p-1.5 text-zinc-400 opacity-0 backdrop-blur-sm transition hover:text-red-400 group-hover/card:opacity-100"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    @endif

    {{-- Expanded hover popover (desktop only) --}}
    <div
        x-show="hover"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        x-cloak
        class="pointer-events-auto absolute -left-4 -right-4 top-0 z-40 hidden origin-top overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900 shadow-2xl shadow-black/70 ring-1 ring-black/20 sm:block"
        @mouseenter="clearTimeout(leaveTimer)"
        @mouseleave="leave()"
    >
        {{-- Trailer / Backdrop preview --}}
        <div class="relative aspect-video w-full overflow-hidden bg-zinc-800">
            <template x-if="detail && detail.trailer_key && trailerPlaying">
                <iframe
                    :src="'https://www.youtube.com/embed/' + detail.trailer_key + '?autoplay=1&mute=1&controls=0&modestbranding=1&rel=0&showinfo=0&loop=1&playlist=' + detail.trailer_key"
                    class="h-full w-full"
                    allow="autoplay; encrypted-media"
                    frameborder="0"
                ></iframe>
            </template>
            <template x-if="!trailerPlaying">
                <div class="relative h-full w-full">
                    @if($posterPath)
                        <img
                            x-bind:src="detail?.backdrop || '{{ $tmdb->imageUrl($posterPath, 'w780') }}'"
                            alt="{{ $itemTitle }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="flex h-full items-center justify-center bg-zinc-800 text-zinc-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="0.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-zinc-900/30 to-transparent"></div>

                    {{-- Play trailer button --}}
                    <template x-if="detail && detail.trailer_key">
                        <button
                            @click.prevent.stop="trailerPlaying = true"
                            class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center gap-1.5 rounded-full bg-white/15 py-1.5 pl-3.5 pr-4 text-xs font-semibold text-white backdrop-blur-md transition hover:bg-white/25"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Trailer
                        </button>
                    </template>
                </div>
            </template>

            {{-- Loading shimmer --}}
            <div x-show="loading" class="absolute inset-0 animate-pulse bg-zinc-800"></div>
        </div>

        {{-- Content --}}
        <div class="p-3.5">
            {{-- Title + rating row --}}
            <div class="flex items-start justify-between gap-2">
                <a href="{{ $href }}" wire:navigate class="min-w-0 flex-1 transition hover:text-white">
                    <h3 class="truncate text-sm font-semibold text-white">{{ $itemTitle }}</h3>
                </a>
                <template x-if="detail">
                    <div x-show="detail.rating > 0" class="flex shrink-0 items-center gap-0.5 rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span x-text="detail.rating"></span>
                    </div>
                </template>
            </div>

            {{-- Meta row: year, runtime, genres --}}
            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-zinc-400">
                @if($year)
                    <span>{{ $year }}</span>
                @endif
                <template x-if="detail && detail.runtime">
                    <span x-text="formatRuntime(detail.runtime)"></span>
                </template>
                <template x-if="detail && detail.seasons">
                    <span x-text="detail.seasons + (detail.seasons === 1 ? ' Season' : ' Seasons')"></span>
                </template>
                <template x-if="detail && detail.genres.length">
                    <span class="flex items-center gap-1">
                        <template x-for="(g, i) in detail.genres" :key="g">
                            <span>
                                <span x-show="i > 0" class="text-zinc-700">&middot;</span>
                                <span x-text="g"></span>
                            </span>
                        </template>
                    </span>
                </template>
            </div>

            {{-- Tagline --}}
            <template x-if="detail && detail.tagline">
                <p class="mt-2 text-[11px] italic text-zinc-500" x-text="detail.tagline"></p>
            </template>

            {{-- Overview --}}
            <template x-if="detail && detail.overview">
                <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-zinc-400" x-text="detail.overview"></p>
            </template>

            {{-- Cast row --}}
            <template x-if="detail && detail.cast.length">
                <div class="mt-3 flex items-center gap-1.5">
                    <template x-for="person in detail.cast" :key="person.name">
                        <div class="group/tip relative">
                            <div class="size-7 overflow-hidden rounded-full bg-zinc-800 ring-1 ring-white/[0.06]">
                                <template x-if="person.image">
                                    <img :src="person.image" :alt="person.name" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!person.image">
                                    <div class="flex h-full w-full items-center justify-center text-zinc-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                                    </div>
                                </template>
                            </div>
                            <div class="pointer-events-none absolute -top-8 left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-md bg-zinc-800 px-2 py-1 text-[10px] text-zinc-300 opacity-0 shadow-lg transition group-hover/tip:opacity-100" x-text="person.name"></div>
                        </div>
                    </template>
                    <span class="ml-1 text-[10px] text-zinc-600">Cast</span>
                </div>
            </template>

            {{-- Loading skeleton for detail --}}
            <div x-show="loading" class="mt-3 space-y-2">
                <div class="h-3 w-3/4 animate-pulse rounded bg-zinc-800/80"></div>
                <div class="h-3 w-1/2 animate-pulse rounded bg-zinc-800/60"></div>
            </div>

            {{-- Action buttons --}}
            <div class="mt-3 flex items-center gap-2">
                <a href="{{ $href }}" wire:navigate class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-red-600 py-2 text-xs font-semibold text-white transition hover:bg-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    {{ $variant === 'watch' ? 'Continue' : 'Details' }}
                </a>

                <template x-if="detail">
                    <div class="flex items-center gap-1.5">
                        {{-- Watchlist --}}
                        <button
                            @click.prevent.stop="toggleAction('watchlist')"
                            class="flex size-8 items-center justify-center rounded-lg border transition"
                            :class="detail.is_watchlisted ? 'border-green-500/30 bg-green-500/10 text-green-400' : 'border-white/[0.08] bg-white/[0.04] text-zinc-400 hover:text-white hover:border-white/[0.15]'"
                            :title="detail.is_watchlisted ? 'Remove from Watchlist' : 'Add to Watchlist'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" :fill="detail.is_watchlisted ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                        </button>
                        {{-- Favorite --}}
                        <button
                            @click.prevent.stop="toggleAction('favorite')"
                            class="flex size-8 items-center justify-center rounded-lg border transition"
                            :class="detail.is_favorited ? 'border-red-500/30 bg-red-500/10 text-red-400' : 'border-white/[0.08] bg-white/[0.04] text-zinc-400 hover:text-white hover:border-white/[0.15]'"
                            :title="detail.is_favorited ? 'Remove from Favorites' : 'Add to Favorites'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" :fill="detail.is_favorited ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
