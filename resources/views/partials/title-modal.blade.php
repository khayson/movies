{{-- Netflix-style details modal. Open from anywhere with: $dispatch('open-title', { type: 'movie', id: 550 }) --}}
<div
    x-data="{
        open: false,
        loading: false,
        failed: false,
        detail: null,
        requestId: 0,
        async show({ type, id }) {
            if (! ['movie', 'tv'].includes(type) || ! Number.isInteger(Number(id))) return;
            const requestId = ++this.requestId;
            this.open = true;
            this.loading = true;
            this.failed = false;
            this.detail = null;
            this.$nextTick(() => this.$refs.panel?.scrollTo({ top: 0 }));
            this.syncUrl(type + '-' + id);
            try {
                const url = @js(route('api.media.show', ['type' => '__TYPE__', 'id' => '__ID__']))
                    .replace('__TYPE__', type).replace('__ID__', id);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error(response.status);
                const detail = await response.json();
                if (requestId === this.requestId) this.detail = detail;
            } catch (e) {
                if (requestId === this.requestId) this.failed = true;
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },
        close() {
            this.open = false;
            this.requestId++;
            this.syncUrl(null);
        },
        syncUrl(value) {
            const url = new URL(window.location.href);
            value ? url.searchParams.set('title', value) : url.searchParams.delete('title');
            history.replaceState(history.state, '', url);
        },
        openFromUrl() {
            const match = (new URL(window.location.href).searchParams.get('title') || '').match(/^(movie|tv)-(\d+)$/);
            if (match) this.show({ type: match[1], id: Number(match[2]) });
        },
        async toggle(action) {
            if (! this.detail) return;
            const routes = {
                watchlist: @js(route('api.media.watchlist', ['type' => '__TYPE__', 'id' => '__ID__'])),
                favorite: @js(route('api.media.favorite', ['type' => '__TYPE__', 'id' => '__ID__'])),
            };
            const response = await fetch(routes[action].replace('__TYPE__', this.detail.type).replace('__ID__', this.detail.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? @js(csrf_token()), Accept: 'application/json' },
            });
            if (response.status === 401) {
                window.location.href = @js(route('login'));
                return;
            }
            const data = await response.json();
            this.detail[action === 'watchlist' ? 'is_watchlisted' : 'is_favorited'] = data.added;
        },
        formatRuntime(minutes) {
            if (! minutes) return '';
            const hours = Math.floor(minutes / 60);
            return hours > 0 ? hours + 'h ' + (minutes % 60 ? (minutes % 60) + 'm' : '') : minutes + 'm';
        },
    }"
    x-init="openFromUrl()"
    @open-title.window="show($event.detail)"
    @keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[70] overflow-hidden"
    role="dialog"
    aria-modal="true"
    :aria-label="detail ? detail.title : 'Title details'"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="close()"></div>

    <div x-ref="panel" class="relative h-full overflow-y-auto overscroll-contain px-2 py-6 sm:px-4 sm:py-10" @click.self="close()">
        <div
            x-show="open"
            x-trap.noscroll="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative mx-auto w-full max-w-4xl overflow-hidden rounded-xl bg-zinc-900 shadow-2xl shadow-black/60 ring-1 ring-white/10"
        >
            <button type="button" @click="close()" aria-label="Close" class="absolute right-3 top-3 z-20 flex size-9 items-center justify-center rounded-full bg-zinc-900/80 text-white transition hover:bg-zinc-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>

            {{-- Hero: trailer over backdrop --}}
            <div class="relative aspect-video w-full overflow-hidden bg-zinc-950">
                <template x-if="detail && detail.backdrop">
                    <img :src="detail.backdrop" alt="" class="absolute inset-0 size-full object-cover">
                </template>
                <template x-if="detail && detail.trailer_key && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches">
                    <iframe
                        :src="`https://www.youtube-nocookie.com/embed/${detail.trailer_key}?autoplay=1&mute=1&controls=0&modestbranding=1&rel=0&playsinline=1&loop=1&playlist=${detail.trailer_key}`"
                        :title="detail.title + ' trailer'"
                        tabindex="-1"
                        allow="autoplay; encrypted-media"
                        class="pointer-events-none absolute left-1/2 top-1/2 h-full w-[177.78%] min-w-full -translate-x-1/2 -translate-y-1/2 scale-110"
                    ></iframe>
                </template>
                <div x-show="loading" class="absolute inset-0 animate-pulse bg-zinc-800"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-900 via-zinc-900/20 to-transparent"></div>

                <template x-if="detail">
                    <div class="absolute inset-x-0 bottom-0 p-5 sm:p-8">
                        <h2 class="max-w-2xl text-2xl font-black tracking-tight text-white drop-shadow-lg sm:text-4xl" x-text="detail.title"></h2>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <a :href="detail.watch_url" class="inline-flex items-center gap-2 rounded-md bg-white px-6 py-2 text-sm font-bold text-black transition hover:bg-white/80">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Play
                            </a>
                            <button type="button" @click="toggle('watchlist')" :aria-pressed="detail.is_watchlisted" :aria-label="detail.is_watchlisted ? 'Remove from My List' : 'Add to My List'" class="flex size-10 items-center justify-center rounded-full border-2 border-white/50 bg-zinc-900/60 text-white transition hover:border-white">
                                <svg x-show="! detail.is_watchlisted" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                <svg x-show="detail.is_watchlisted" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </button>
                            <button type="button" @click="toggle('favorite')" :aria-pressed="detail.is_favorited" :aria-label="detail.is_favorited ? 'Remove from favorites' : 'Add to favorites'" class="flex size-10 items-center justify-center rounded-full border-2 border-white/50 bg-zinc-900/60 text-white transition hover:border-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" :fill="detail.is_favorited ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Body --}}
            <div class="px-5 pb-8 pt-2 sm:px-8">
                <p x-show="failed" class="py-10 text-center text-sm text-zinc-400">We couldn't load this title. Please try again.</p>

                <template x-if="detail">
                    <div>
                        <div class="grid gap-6 md:grid-cols-[2fr_1fr]">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span x-show="detail.rating > 0" class="font-semibold text-emerald-400" x-text="Math.round(detail.rating * 10) + '% match'"></span>
                                    <span x-show="detail.year" class="text-zinc-400" x-text="detail.year"></span>
                                    <span x-show="detail.runtime" class="text-zinc-400" x-text="formatRuntime(detail.runtime)"></span>
                                    <span x-show="detail.seasons" class="text-zinc-400" x-text="detail.seasons + (detail.seasons === 1 ? ' Season' : ' Seasons')"></span>
                                    <span class="rounded border border-white/30 px-1.5 text-[11px] text-zinc-300" x-text="detail.type === 'tv' ? 'Series' : 'Movie'"></span>
                                </div>
                                <p x-show="detail.tagline" class="mt-3 text-sm italic text-zinc-400" x-text="detail.tagline"></p>
                                <p class="mt-3 text-sm leading-relaxed text-zinc-200" x-text="detail.overview"></p>
                            </div>
                            <div class="space-y-2 text-sm">
                                <p x-show="detail.cast.length"><span class="text-zinc-500">Cast:</span> <span class="text-zinc-300" x-text="detail.cast.map(person => person.name).join(', ')"></span></p>
                                <p x-show="detail.genres.length"><span class="text-zinc-500">Genres:</span> <span class="text-zinc-300" x-text="detail.genres.join(', ')"></span></p>
                                <a :href="detail.detail_url" wire:navigate @click="open = false" class="inline-flex items-center gap-1 pt-2 text-sm font-medium text-zinc-300 underline-offset-4 hover:text-white hover:underline">
                                    Full details, episodes &amp; reviews
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </a>
                            </div>
                        </div>

                        <template x-if="detail.more_like_this.length">
                            <section class="mt-8">
                                <h3 class="text-xl font-bold text-white">More Like This</h3>
                                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <template x-for="item in detail.more_like_this" :key="item.type + item.id">
                                        <button type="button" @click="show({ type: item.type, id: item.id })" class="group overflow-hidden rounded-md bg-zinc-800 text-left ring-1 ring-white/5 transition hover:ring-white/30">
                                            <img :src="item.poster" :alt="item.title" loading="lazy" class="aspect-[2/3] w-full object-cover transition duration-300 group-hover:scale-105">
                                            <span class="block truncate px-2 py-1.5 text-xs text-zinc-300" x-text="item.title + (item.year ? ' (' + item.year + ')' : '')"></span>
                                        </button>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
