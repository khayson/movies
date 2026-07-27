{{-- Settings sidebar search --}}
<div
    class="relative mb-3 lg:mb-4"
    x-data="{
        index: @js(\App\Support\SettingsSearch::clientIndex()),
        query: '',
        results: [],
        active: 0,
        open: false,
        onInput() {
            this.results = this.match(this.query);
            this.active = 0;
            this.open = this.query.trim().length > 0;
        },
        match(raw) {
            const query = raw.trim().toLowerCase();
            if (! query) return [];
            const tokens = query.split(/\s+/).filter((t) => t.length >= 2);

            return this.index
                .map((item) => {
                    let score = 0;
                    const label = item.label.toLowerCase();
                    const haystack = item.haystack;

                    if (label === query) score += 100;
                    else if (label.startsWith(query)) score += 70;
                    else if (label.includes(query)) score += 45;
                    if (haystack.includes(query)) score += 25;

                    let matched = 0;
                    for (const token of tokens) {
                        if (haystack.includes(token)) {
                            matched++;
                            score += 12;
                        }
                    }

                    if (tokens.length && matched === tokens.length) score += 20;
                    if (tokens.length && matched === 0 && ! haystack.includes(query)) score = 0;

                    return { ...item, score };
                })
                .filter((item) => item.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, 8);
        },
        move(delta) {
            if (! this.results.length) return;
            this.open = true;
            this.active = (this.active + delta + this.results.length) % this.results.length;
        },
        go() {
            if (! this.results.length) return;
            this.select(this.results[this.active]);
        },
        select(item) {
            this.open = false;
            this.query = '';
            this.results = [];

            if (window.Livewire?.navigate) {
                window.Livewire.navigate(item.url);
            } else {
                window.location.href = item.url;
            }

            queueMicrotask(() => window.dispatchEvent(new CustomEvent('settings:focus-anchor', { detail: item.anchor })));
        },
        close() {
            this.open = false;
        },
    }"
    @keydown.escape.window="close()"
>
    <label class="sr-only" for="settings-search">{{ __('Search settings') }}</label>
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input
            id="settings-search"
            type="search"
            x-model="query"
            @focus="open = query.length > 0"
            @input="onInput()"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="go()"
            @keydown.tab.prevent="go()"
            autocomplete="off"
            placeholder="{{ __('Search settings…') }}"
            class="w-full rounded-xl border border-white/[0.08] bg-zinc-950/60 py-2 pl-9 pr-3 text-sm text-white placeholder:text-zinc-500 outline-none transition focus:border-amber-500/40 focus:ring-2 focus:ring-amber-500/20"
            role="combobox"
            :aria-expanded="open && results.length > 0"
            aria-controls="settings-search-results"
            aria-autocomplete="list"
        >
    </div>

    <div
        id="settings-search-results"
        x-show="open && query.trim().length > 0"
        x-cloak
        x-transition.opacity.duration.150ms
        @click.outside="close()"
        class="absolute z-50 mt-2 w-[min(100%,22rem)] overflow-hidden rounded-xl border border-white/[0.08] bg-zinc-950 shadow-2xl shadow-black/50 ring-1 ring-white/[0.04] lg:w-full"
        role="listbox"
    >
        <template x-if="results.length === 0">
            <p class="px-3 py-4 text-center text-xs text-zinc-500">{{ __('No matching settings') }}</p>
        </template>

        <ul class="max-h-80 overflow-y-auto py-1" x-show="results.length > 0">
            <template x-for="(item, index) in results" :key="item.id">
                <li role="option" :aria-selected="index === active">
                    <a
                        :href="item.url"
                        wire:navigate
                        @click.prevent="select(item)"
                        @mouseenter="active = index"
                        class="block px-3 py-2.5 transition"
                        :class="index === active ? 'bg-amber-500/10 text-white' : 'text-zinc-300 hover:bg-white/[0.04]'"
                    >
                        <span class="flex items-start justify-between gap-2">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium" x-text="item.label"></span>
                                <span class="mt-0.5 block truncate text-[11px] text-zinc-500" x-text="item.description"></span>
                            </span>
                            <span
                                class="shrink-0 rounded-md bg-white/[0.04] px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500"
                                x-text="item.group.split(' · ')[0]"
                            ></span>
                        </span>
                    </a>
                </li>
            </template>
        </ul>

        <p class="border-t border-white/[0.06] px-3 py-2 text-[10px] text-zinc-600">
            {{ __('↑↓ navigate · Tab / Enter open') }}
        </p>
    </div>
</div>

@once
    <script>
        if (! window.__streamVaultSettingsSearch) {
            window.__streamVaultSettingsSearch = true;

            window.focusSettingsAnchor = function (anchor) {
                const hash = anchor || (window.location.hash || '').replace(/^#/, '');
                if (! hash) return;

                const el = document.getElementById(hash);
                if (! el) return;

                if (hash === 'settings-advanced') {
                    window.dispatchEvent(new CustomEvent('settings:open-advanced'));
                }

                requestAnimationFrame(() => {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    el.classList.add('ring-2', 'ring-amber-500/40', 'transition');
                    setTimeout(() => el.classList.remove('ring-2', 'ring-amber-500/40'), 1600);
                });
            };

            document.addEventListener('DOMContentLoaded', () => window.focusSettingsAnchor());
            document.addEventListener('livewire:navigated', () => {
                setTimeout(() => window.focusSettingsAnchor(), 50);
            });
            window.addEventListener('settings:focus-anchor', (e) => window.focusSettingsAnchor(e.detail));
            window.addEventListener('hashchange', () => window.focusSettingsAnchor());
        }
    </script>
@endonce
