@php
    $primaryNav = [
        ['route' => 'home', 'label' => 'Home', 'match' => 'home'],
        ['route' => 'movies.index', 'label' => 'Movies', 'match' => 'movies.*'],
        ['route' => 'tv.index', 'label' => 'TV Shows', 'match' => 'tv.*'],
        ['route' => 'anime.index', 'label' => 'Anime', 'match' => 'anime.*'],
        ['route' => 'new-releases', 'label' => 'New & Popular', 'match' => ['new-releases', 'trending']],
    ];
    $navGroups = [
        'African Cinema' => [
            ['route' => 'nollywood', 'label' => 'Nollywood', 'hint' => 'Nigeria', 'match' => 'nollywood'],
            ['route' => 'ghallywood', 'label' => 'Ghallywood', 'hint' => 'Ghana', 'match' => 'ghallywood'],
        ],
        'Browse' => [
            ['route' => 'trending', 'label' => 'Trending', 'hint' => 'What everyone is watching', 'match' => 'trending'],
            ['route' => 'coming-soon', 'label' => 'Coming Soon', 'hint' => 'Release dates and trailers', 'match' => 'coming-soon'],
            ['route' => 'genres.index', 'label' => 'Genres', 'hint' => 'Action, drama, horror and more', 'match' => 'genres.*'],
            ['route' => 'discover', 'label' => 'Discover', 'hint' => 'Filter by year, rating, language', 'match' => 'discover'],
            ['route' => 'mood.index', 'label' => 'Mood Picker', 'hint' => 'Pick something for your mood', 'match' => 'mood.*'],
            ['route' => 'trailers', 'label' => 'Trailers', 'hint' => 'Latest trailers', 'match' => 'trailers'],
            ['route' => 'collections.index', 'label' => 'Collections', 'hint' => 'Curated lists', 'match' => 'collections.*'],
        ],
    ];
    $primaryActive = collect($primaryNav)->contains(fn (array $nav): bool => request()->routeIs(...(array) $nav['match']));
    $user = auth()->user();
    $showAdult = $user?->canViewAdultContent() ?? false;
    $adultLabel = $user?->adultStealthEnabled() ? __('Private vault') : __('Adult 18+');
@endphp

<nav
    x-data="{ scrolled: false, menuOpen: false, searchOpen: false }"
    x-init="scrolled = window.scrollY > 8"
    @scroll.window="scrolled = window.scrollY > 8"
    @keydown.escape.window="menuOpen = false; searchOpen = false"
    :class="scrolled && 'is-scrolled'"
    class="site-nav sticky top-0 z-50"
    aria-label="{{ __('Main') }}"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3 sm:gap-6 xl:gap-8">
            <button
                type="button"
                @click="menuOpen = true"
                class="-ml-1 flex size-10 items-center justify-center rounded-lg text-zinc-300 transition hover:bg-white/[0.06] hover:text-white lg:hidden"
                aria-label="{{ __('Open menu') }}"
                :aria-expanded="menuOpen"
                aria-controls="site-menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>

            @include('partials.site-logo', ['compactOnPhones' => true])

            <div class="hidden items-center gap-0.5 lg:flex">
                @foreach($primaryNav as $nav)
                    <a href="{{ route($nav['route']) }}"
                       class="nav-item whitespace-nowrap rounded-lg px-2.5 py-2 text-[13px] font-medium transition-colors xl:px-3 {{ request()->routeIs(...(array) $nav['match']) ? 'active text-white' : 'text-zinc-400 hover:text-white' }}"
                       @if(request()->routeIs(...(array) $nav['match'])) aria-current="page" @endif
                       wire:navigate>
                        {{ $nav['label'] }}
                    </a>
                @endforeach

                @foreach($navGroups as $group => $links)
                    @php($groupActive = ! $primaryActive && collect($links)->contains(fn (array $link): bool => request()->routeIs($link['match'])))
                    <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                        <button
                            type="button"
                            @click="open = ! open"
                            :aria-expanded="open"
                            class="nav-item inline-flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-2 text-[13px] xl:px-3 font-medium transition-colors {{ $groupActive ? 'active text-white' : 'text-zinc-400 hover:text-white' }}"
                        >
                            {{ $group }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute left-0 top-full z-50 mt-2 w-64 rounded-xl border border-white/[0.08] bg-zinc-900/95 p-1.5 shadow-2xl shadow-black/60 backdrop-blur-xl"
                        >
                            @foreach($links as $link)
                                <a href="{{ route($link['route']) }}" @click="open = false" wire:navigate
                                   class="block rounded-lg px-3 py-2 transition hover:bg-white/[0.06] {{ request()->routeIs($link['match']) ? 'bg-white/[0.06]' : '' }}">
                                    <span class="block text-sm font-medium text-white">{{ $link['label'] }}</span>
                                    <span class="block text-xs text-zinc-500">{{ $link['hint'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if($showAdult)
                    <a href="{{ route('adult.browse') }}"
                       class="nav-item rounded-lg px-3 py-2 text-[13px] font-medium text-red-400/80 transition-colors hover:text-red-400 {{ request()->routeIs('adult.*') ? 'active !text-red-400' : '' }}"
                       title="{{ $adultLabel }}"
                       wire:navigate>
                        @if($user->adultStealthEnabled())
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            <span class="sr-only">{{ __('Private vault') }}</span>
                        @else
                            18+
                        @endif
                    </a>
                @endif
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <div class="relative" @click.outside="searchOpen = false">
                <button
                    type="button"
                    @click="searchOpen = ! searchOpen; if (searchOpen) $nextTick(() => $refs.searchPanel.querySelector('input')?.focus())"
                    :aria-expanded="searchOpen"
                    aria-controls="site-search"
                    aria-label="{{ __('Search') }}"
                    class="flex size-10 items-center justify-center rounded-lg text-zinc-300 transition hover:bg-white/[0.06] hover:text-white"
                    :class="searchOpen && 'bg-white/[0.08] text-white'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </button>
                <div
                    x-ref="searchPanel"
                    id="site-search"
                    x-show="searchOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="fixed inset-x-3 top-[4.25rem] z-50 rounded-2xl border border-white/[0.08] bg-zinc-900/95 p-3 shadow-2xl shadow-black/60 backdrop-blur-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[28rem]"
                >
                    @include('partials.smart-search', ['compact' => false, 'fullWidth' => true])
                </div>
            </div>

            <livewire:notification-dropdown />

            @auth
                <div class="ml-1 h-5 w-px bg-white/[0.08]"></div>
                @include('partials.user-account-menu')
            @else
                <div class="ml-1 flex items-center gap-2">
                    <a href="{{ route('login') }}" class="hidden rounded-md px-3.5 py-2 text-sm font-medium text-zinc-300 transition-colors hover:text-white sm:inline-flex" wire:navigate>
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="btn-primary whitespace-nowrap px-3 py-2 sm:px-4" wire:navigate>
                        Get Started
                    </a>
                </div>
            @endauth
        </div>
    </div>

    {{-- Mobile menu drawer --}}
    <div x-show="menuOpen" x-cloak class="fixed inset-0 z-[60] lg:hidden" id="site-menu" role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}">
        <div x-show="menuOpen" x-transition.opacity class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="menuOpen = false"></div>
        <div
            x-show="menuOpen"
            x-trap.noscroll="menuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex w-[min(20rem,85vw)] flex-col overflow-y-auto border-r border-white/[0.06] bg-zinc-950 px-4 pb-8 pt-4"
        >
            <div class="mb-6 flex items-center justify-between">
                @include('partials.site-logo')
                <button type="button" @click="menuOpen = false" class="flex size-10 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white/[0.06] hover:text-white" aria-label="{{ __('Close menu') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <ul class="space-y-0.5">
                @foreach($primaryNav as $nav)
                    <li>
                        <a href="{{ route($nav['route']) }}" @click="menuOpen = false" wire:navigate
                           class="block rounded-lg px-3 py-2.5 text-base font-medium transition {{ request()->routeIs(...(array) $nav['match']) ? 'bg-amber-500/10 text-amber-300' : 'text-zinc-200 hover:bg-white/[0.05]' }}">
                            {{ $nav['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @foreach($navGroups as $group => $links)
                <p class="mb-1 mt-6 px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach($links as $link)
                        <li>
                            <a href="{{ route($link['route']) }}" @click="menuOpen = false" wire:navigate
                               class="block rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs($link['match']) ? 'bg-amber-500/10 text-amber-300' : 'text-zinc-300 hover:bg-white/[0.05]' }}">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach

            @if($showAdult)
                <a href="{{ route('adult.browse') }}" @click="menuOpen = false" wire:navigate class="mt-6 block rounded-lg px-3 py-2 text-sm font-medium text-red-400/90 hover:bg-red-500/10">
                    {{ $adultLabel }}
                </a>
            @endif

            @guest
                <div class="mt-auto grid gap-2 pt-8">
                    <a href="{{ route('register') }}" class="btn-primary" wire:navigate>Get Started</a>
                    <a href="{{ route('login') }}" class="btn-secondary" wire:navigate>Sign in</a>
                </div>
            @endguest
        </div>
    </div>
</nav>
