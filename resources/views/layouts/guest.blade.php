<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            .nav-glass { background: rgba(9, 9, 11, 0.7); backdrop-filter: blur(20px) saturate(1.8); -webkit-backdrop-filter: blur(20px) saturate(1.8); }
            .nav-item { position: relative; }
            .nav-item::after { content: ''; position: absolute; bottom: -1px; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #f59e0b, #d97706); transition: all 0.3s ease; transform: translateX(-50%); border-radius: 1px; }
            .nav-item.active::after, .nav-item:hover::after { width: 100%; }
            .footer-glow { background: radial-gradient(ellipse 80% 50% at 50% 100%, rgba(245,158,11,0.04) 0%, transparent 70%); }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        {{-- Navigation --}}
        <nav class="nav-glass sticky top-0 z-50 border-b border-white/[0.06]">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {{-- Left: Logo + Nav --}}
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2.5" wire:navigate>
                        <div class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-700 shadow-lg shadow-amber-600/20 transition group-hover:shadow-amber-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                    </a>

                    <div class="hidden items-center gap-0.5 lg:flex">
                        @php
                            $navItems = [
                                ['route' => 'movies.index', 'label' => 'Movies', 'match' => 'movies.*'],
                                ['route' => 'tv.index', 'label' => 'TV Shows', 'match' => 'tv.*'],
                                ['route' => 'genres.index', 'label' => 'Genres', 'match' => 'genres.*'],
                                ['route' => 'discover', 'label' => 'Discover', 'match' => 'discover'],
                                ['route' => 'people.index', 'label' => 'People', 'match' => 'people.*'],
                                ['route' => 'trailers', 'label' => 'Trailers', 'match' => 'trailers'],
                            ];
                        @endphp
                        @foreach($navItems as $nav)
                            <a href="{{ route($nav['route']) }}"
                               class="nav-item rounded-lg px-3 py-2 text-[13px] font-medium text-zinc-400 transition-colors hover:text-white {{ request()->routeIs($nav['match']) ? 'active text-white' : '' }}"
                               wire:navigate>
                                {{ $nav['label'] }}
                            </a>
                        @endforeach
                        @if(auth()->user()?->canViewAdultContent())
                            <a href="{{ route('adult.browse') }}"
                               class="nav-item rounded-lg px-3 py-2 text-[13px] font-medium text-red-400/80 transition-colors hover:text-red-400 {{ request()->routeIs('adult.*') ? 'active !text-red-400' : '' }}"
                               wire:navigate>
                                18+
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('search') }}" class="flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white" wire:navigate title="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </a>

                    @auth
                        <a href="{{ route('notifications') }}" class="relative flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white" wire:navigate title="Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            @if(\App\Models\UserNotification::where('user_id', auth()->id())->whereNull('read_at')->exists())
                                <span class="absolute right-1.5 top-1.5 flex size-2">
                                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex size-2 rounded-full bg-amber-500"></span>
                                </span>
                            @endif
                        </a>

                        <div class="ml-1 h-5 w-px bg-white/[0.08]"></div>

                        <a href="{{ route('dashboard') }}" class="ml-1 flex items-center gap-2 rounded-lg px-3 py-1.5 transition-colors hover:bg-white/[0.06] {{ request()->routeIs('dashboard') ? 'bg-white/[0.06]' : '' }}" wire:navigate>
                            <div class="flex size-7 items-center justify-center rounded-md bg-gradient-to-br from-amber-500/80 to-amber-700/80 text-xs font-bold text-white">
                                {{ auth()->user()->initials() }}
                            </div>
                            <span class="hidden text-sm font-medium text-zinc-300 sm:inline">{{ Str::words(auth()->user()->name, 1, '') }}</span>
                        </a>
                    @else
                        <div class="ml-1 flex items-center gap-2">
                            <a href="{{ route('login') }}" class="hidden rounded-lg px-3.5 py-2 text-sm font-medium text-zinc-400 transition-colors hover:text-white sm:inline-flex" wire:navigate>
                                Sign in
                            </a>
                            <a href="{{ route('register') }}" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-600/20 transition hover:from-amber-500 hover:to-amber-600 hover:shadow-amber-500/25" wire:navigate>
                                Get Started
                            </a>
                        </div>
                    @endauth
                </div>
            </div>

            {{-- Mobile nav --}}
            <div class="scrollbar-hide flex items-center gap-0.5 overflow-x-auto border-t border-white/[0.04] px-4 lg:hidden">
                @php
                    $mobileNav = [
                        ['route' => 'movies.index', 'label' => 'Movies', 'match' => 'movies.*'],
                        ['route' => 'tv.index', 'label' => 'TV Shows', 'match' => 'tv.*'],
                        ['route' => 'genres.index', 'label' => 'Genres', 'match' => 'genres.*'],
                        ['route' => 'discover', 'label' => 'Discover', 'match' => 'discover'],
                        ['route' => 'people.index', 'label' => 'People', 'match' => 'people.*'],
                        ['route' => 'trailers', 'label' => 'Trailers', 'match' => 'trailers'],
                    ];
                @endphp
                @foreach($mobileNav as $nav)
                    <a href="{{ route($nav['route']) }}"
                       class="nav-item whitespace-nowrap px-3 py-2.5 text-[13px] font-medium text-zinc-500 transition-colors hover:text-white {{ request()->routeIs($nav['match']) ? 'active text-amber-400' : '' }}"
                       wire:navigate>
                        {{ $nav['label'] }}
                    </a>
                @endforeach
                @if(auth()->user()?->canViewAdultContent())
                    <a href="{{ route('adult.browse') }}"
                       class="nav-item whitespace-nowrap px-3 py-2.5 text-[13px] font-medium text-red-400/80 transition-colors hover:text-red-400 {{ request()->routeIs('adult.*') ? 'active !text-red-400' : '' }}"
                       wire:navigate>
                        18+
                    </a>
                @endif
            </div>
        </nav>

        {{-- Main content --}}
        <main>
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="footer-glow border-t border-white/[0.06]">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                {{-- Top section: Logo + columns --}}
                <div class="grid gap-10 lg:grid-cols-6 lg:gap-8">
                    {{-- Brand --}}
                    <div class="lg:col-span-2">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" wire:navigate>
                            <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                            <span class="text-base font-bold text-white">{{ config('app.name') }}</span>
                        </a>
                        <p class="mt-3 max-w-xs text-sm leading-relaxed text-zinc-500">
                            Your open-source companion for discovering, tracking, and enjoying movies and TV shows.
                        </p>
                    </div>

                    {{-- Link columns --}}
                    <div class="grid grid-cols-2 gap-8 sm:grid-cols-4 lg:col-span-4">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Browse</h3>
                            <ul class="mt-3 space-y-2">
                                <li><a href="{{ route('movies.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Movies</a></li>
                                <li><a href="{{ route('tv.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>TV Shows</a></li>
                                <li><a href="{{ route('genres.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Genres</a></li>
                                <li><a href="{{ route('search') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Search</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Discover</h3>
                            <ul class="mt-3 space-y-2">
                                <li><a href="{{ route('discover') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Advanced Search</a></li>
                                <li><a href="{{ route('mood.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Mood Picker</a></li>
                                <li><a href="{{ route('new-releases') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>New Releases</a></li>
                                <li><a href="{{ route('upcoming.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Upcoming</a></li>
                                <li><a href="{{ route('trailers') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Trailers</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Community</h3>
                            <ul class="mt-3 space-y-2">
                                <li><a href="{{ route('people.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>People</a></li>
                                <li><a href="{{ route('collections.index') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Collections</a></li>
                                <li><a href="{{ route('leaderboard') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Leaderboard</a></li>
                                <li><a href="{{ route('activity.feed') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Activity Feed</a></li>
                                <li><a href="{{ route('watch-parties') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Watch Parties</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Legal</h3>
                            <ul class="mt-3 space-y-2">
                                <li><a href="{{ route('terms') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Terms</a></li>
                                <li><a href="{{ route('privacy') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Privacy</a></li>
                                <li><a href="{{ route('architecture') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Architecture</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Bottom bar --}}
                <div class="mt-12 flex flex-col items-center gap-4 border-t border-white/[0.06] pt-8 sm:flex-row sm:justify-between">
                    <p class="max-w-lg text-center text-xs leading-relaxed text-zinc-600 sm:text-left">
                        {{ config('app.name') }} does not host, store, or distribute any video content. All streams are provided by third-party external sources.
                        Metadata provided by <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="text-zinc-500 underline decoration-zinc-700 underline-offset-2 transition-colors hover:text-white">TMDB</a>.
                    </p>
                    <p class="text-xs text-zinc-700">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
