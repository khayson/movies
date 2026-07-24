@php $compact = $compact ?? false; @endphp

<div
    class="relative flex flex-1 items-center"
    x-data="{
        query: '',
        type: 'multi',
        results: [],
        trending: false,
        total: 0,
        open: false,
        loading: false,
        selected: -1,
        debounceTimer: null,
        init() {
            this.$watch('query', (val, oldVal) => {
                this.selected = -1;
                if (val !== oldVal) {
                    this.results = [];
                    this.loading = true;
                    this.open = true;
                }
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.search(), 300);
            });
            this.$watch('type', () => {
                this.results = [];
                this.loading = true;
                this.search();
            });
        },
        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ q: this.query, type: this.type });
                const res = await fetch(`{{ route('api.search') }}?${params}`);
                const data = await res.json();
                this.results = data.results || [];
                this.total = data.total || 0;
                this.trending = data.trending || false;
                this.open = true;
            } catch(e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        navigate(url) {
            this.open = false;
            this.query = '';
            Livewire.navigate(url);
        },
        onKeydown(e) {
            if (!this.open || !this.results.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selected = (this.selected + 1) % this.results.length;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selected = this.selected <= 0 ? this.results.length - 1 : this.selected - 1;
            } else if (e.key === 'Enter' && this.selected >= 0) {
                e.preventDefault();
                this.navigate(this.results[this.selected].url);
            }
        },
        fullSearch() {
            if (!this.query.trim()) return;
            this.open = false;
            Livewire.navigate('{{ route('search') }}?q=' + encodeURIComponent(this.query));
        },
        typeLabel(t) {
            return { movie: 'Movie', tv: 'TV', person: 'Person' }[t] || t;
        }
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
>
    <div class="relative w-full {{ $compact ? 'max-w-xs' : 'max-w-md' }}">
        {{-- Search input --}}
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
        </div>
        <input
            type="text"
            x-model="query"
            @focus="if (!results.length) search(); else open = true"
            @keydown="onKeydown"
            @keydown.enter="if (selected < 0) fullSearch()"
            placeholder="Search movies, shows, people..."
            autocomplete="off"
            class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] py-2 pl-10 pr-4 text-sm text-white placeholder-zinc-500 outline-none transition-colors focus:border-red-500/30 focus:bg-white/[0.06] focus:ring-0 {{ $compact ? '' : 'sm:py-2.5' }}"
        >

        {{-- Dropdown --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[0.98] -translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-[0.98] -translate-y-1"
            x-cloak
            class="absolute left-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-950 shadow-2xl shadow-black/60 ring-1 ring-black/10 {{ $compact ? 'right-0 sm:w-[420px] sm:right-auto' : 'right-0' }}"
        >
            {{-- Header bar --}}
            <div class="flex items-center justify-between border-b border-white/[0.06] px-4 py-2">
                <div class="flex items-center gap-2">
                    <template x-if="trending">
                        <span class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-red-400" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 0 0-1.071-.136 9.742 9.742 0 0 0-3.539 6.176 7.547 7.547 0 0 1-1.705-1.715.75.75 0 0 0-1.152-.082A9 9 0 1 0 15.68 4.534a7.46 7.46 0 0 1-2.717-2.248ZM15.75 14.25a3.75 3.75 0 1 1-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 0 1 1.925-3.546 3.75 3.75 0 0 1 3.255 3.718Z" clip-rule="evenodd" /></svg>
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Trending</span>
                        </span>
                    </template>
                    <template x-if="!trending && query.length >= 2">
                        <span class="flex items-center gap-1.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                                Results<span x-show="total > 0" class="text-zinc-600"> &middot; <span x-text="total"></span></span>
                            </span>
                        </span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Type filter chips --}}
                    <div class="flex items-center gap-0.5">
                        <template x-for="t in ['multi', 'movie', 'tv', 'person']" :key="t">
                            <button
                                type="button"
                                @click="type = t"
                                class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider transition-colors"
                                :class="type === t ? 'bg-red-600/20 text-red-400' : 'text-zinc-600 hover:text-zinc-400'"
                                x-text="t === 'multi' ? 'All' : typeLabel(t)"
                            ></button>
                        </template>
                    </div>

                    <div x-show="loading" class="flex items-center gap-1.5">
                        <div class="size-3 animate-spin rounded-full border-[1.5px] border-zinc-700 border-t-red-500"></div>
                    </div>
                </div>
            </div>

            {{-- Skeleton loading state --}}
            <div x-show="loading && !results.length" class="divide-y divide-white/[0.04] p-1">
                <template x-for="i in 4" :key="'skel-' + i">
                    <div class="flex items-center gap-3 px-3 py-3">
                        <div class="size-11 shrink-0 animate-pulse rounded-lg bg-zinc-800/80"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3.5 w-3/4 animate-pulse rounded bg-zinc-800/80"></div>
                            <div class="h-2.5 w-1/3 animate-pulse rounded bg-zinc-800/60"></div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Results list --}}
            <div x-show="results.length" class="max-h-[420px] overflow-y-auto scrollbar-hide p-1">
                <template x-for="(item, idx) in results" :key="item.type + '-' + item.id">
                    <button
                        @click="navigate(item.url)"
                        @mouseenter="selected = idx"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-all duration-150"
                        :class="selected === idx ? 'bg-white/[0.07]' : 'hover:bg-white/[0.04]'"
                    >
                        {{-- Poster/avatar --}}
                        <div class="relative size-11 shrink-0 overflow-hidden bg-zinc-800/80" :class="item.type === 'person' ? 'rounded-full' : 'rounded-lg'">
                            <img x-show="item.image" :src="item.image" :alt="item.title" class="h-full w-full object-cover" loading="lazy">
                            <div x-show="!item.image" class="flex h-full w-full items-center justify-center">
                                <template x-if="item.type === 'person'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                                </template>
                                <template x-if="item.type !== 'person'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                </template>
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="truncate text-sm font-medium text-white" x-text="item.title"></span>
                                <span x-show="item.rating" class="flex shrink-0 items-center gap-0.5 rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-amber-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <span x-text="item.rating"></span>
                                </span>
                            </div>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                                <span
                                    class="rounded px-1.5 py-px text-[10px] font-semibold uppercase"
                                    :class="{
                                        'bg-blue-500/10 text-blue-400': item.type === 'movie',
                                        'bg-purple-500/10 text-purple-400': item.type === 'tv',
                                        'bg-emerald-500/10 text-emerald-400': item.type === 'person'
                                    }"
                                    x-text="typeLabel(item.type)"
                                ></span>
                                <span x-show="item.subtitle" class="text-zinc-600" x-text="item.subtitle"></span>
                            </div>
                        </div>

                        {{-- Arrow / keyboard hint --}}
                        <div class="flex shrink-0 items-center">
                            <svg x-show="selected !== idx" xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            <kbd x-show="selected === idx" class="rounded bg-white/[0.06] px-1.5 py-0.5 font-mono text-[9px] text-zinc-500">Enter</kbd>
                        </div>
                    </button>
                </template>
            </div>

            {{-- No results --}}
            <div x-show="!loading && !results.length && query.length >= 2" class="px-4 py-10 text-center">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-zinc-800/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <p class="text-sm font-medium text-zinc-400">No results found</p>
                <p class="mt-1 text-xs text-zinc-600">Try a different search term or filter</p>
            </div>

            {{-- Footer --}}
            <div x-show="query.length >= 2 && total > 0" class="border-t border-white/[0.06]">
                <button @click="fullSearch()" class="group flex w-full items-center justify-center gap-2 px-4 py-3 text-xs font-medium text-zinc-400 transition-colors hover:bg-white/[0.04] hover:text-white">
                    <span>View all <span x-text="total" class="tabular-nums text-zinc-300"></span> results</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </button>
            </div>

            {{-- Keyboard hints footer --}}
            <div x-show="results.length" class="flex items-center justify-center gap-4 border-t border-white/[0.04] bg-white/[0.01] px-4 py-2">
                <span class="flex items-center gap-1 text-[10px] text-zinc-600">
                    <kbd class="rounded bg-white/[0.06] px-1 py-0.5 font-mono text-[9px]">&uarr;</kbd>
                    <kbd class="rounded bg-white/[0.06] px-1 py-0.5 font-mono text-[9px]">&darr;</kbd>
                    navigate
                </span>
                <span class="flex items-center gap-1 text-[10px] text-zinc-600">
                    <kbd class="rounded bg-white/[0.06] px-1 py-0.5 font-mono text-[9px]">Enter</kbd>
                    select
                </span>
                <span class="flex items-center gap-1 text-[10px] text-zinc-600">
                    <kbd class="rounded bg-white/[0.06] px-1 py-0.5 font-mono text-[9px]">ESC</kbd>
                    close
                </span>
            </div>
        </div>
    </div>
</div>
