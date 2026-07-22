<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        {{-- Navigation --}}
        <nav class="sticky top-0 z-50 border-b border-zinc-800/60 bg-zinc-950/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight text-white" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-7 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4zm0 2h16v12H4V6zm2 1v10h1V7H6zm3 0v10h1V7H9zm6 0v10h1V7h-1zm3 0v10h1V7h-1zm-8 2v6l5-3-5-3z"/>
                        </svg>
                        {{ config('app.name') }}
                    </a>

                    <div class="hidden items-center gap-1 lg:flex">
                        @php
                            $navItems = [
                                ['route' => 'movies.index', 'label' => 'Movies', 'match' => 'movies.*'],
                                ['route' => 'tv.index', 'label' => 'TV Shows', 'match' => 'tv.*'],
                                ['route' => 'genres.index', 'label' => 'Genres', 'match' => 'genres.*'],
                                ['route' => 'new-releases', 'label' => 'New', 'match' => 'new-releases'],
                                ['route' => 'upcoming.index', 'label' => 'Upcoming', 'match' => 'upcoming.*'],
                            ];
                        @endphp
                        @foreach($navItems as $nav)
                            <a href="{{ route($nav['route']) }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-800 hover:text-white {{ request()->routeIs($nav['match']) ? '!text-white bg-zinc-800' : '' }}"
                               wire:navigate>
                                {{ $nav['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('search') }}" class="rounded-lg p-2 text-zinc-400 transition hover:bg-zinc-800 hover:text-white" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-800 hover:text-white {{ request()->routeIs('dashboard') ? '!text-white bg-zinc-800' : '' }}" wire:navigate>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-lg px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-800 hover:text-white sm:inline-flex" wire:navigate>
                            Sign in
                        </a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500" wire:navigate>
                            Register
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Mobile nav --}}
            <div class="scrollbar-hide flex items-center gap-1 overflow-x-auto border-t border-zinc-800/60 px-4 lg:hidden">
                @php
                    $mobileNav = [
                        ['route' => 'movies.index', 'label' => 'Movies', 'match' => 'movies.*'],
                        ['route' => 'tv.index', 'label' => 'TV Shows', 'match' => 'tv.*'],
                        ['route' => 'genres.index', 'label' => 'Genres', 'match' => 'genres.*'],
                        ['route' => 'new-releases', 'label' => 'New', 'match' => 'new-releases'],
                        ['route' => 'upcoming.index', 'label' => 'Upcoming', 'match' => 'upcoming.*'],
                    ];
                @endphp
                @foreach($mobileNav as $nav)
                    <a href="{{ route($nav['route']) }}"
                       class="whitespace-nowrap px-3 py-2.5 text-sm font-medium text-zinc-400 transition hover:text-white {{ request()->routeIs($nav['match']) ? '!text-amber-400' : '' }}"
                       wire:navigate>
                        {{ $nav['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>

        {{-- Main content --}}
        <main>
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-800 bg-zinc-950">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Browse</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="{{ route('movies.index') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Movies</a></li>
                            <li><a href="{{ route('tv.index') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>TV Shows</a></li>
                            <li><a href="{{ route('genres.index') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Genres</a></li>
                            <li><a href="{{ route('search') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Search</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Discover</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="{{ route('new-releases') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>New Releases</a></li>
                            <li><a href="{{ route('upcoming.index') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Upcoming</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Legal</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="{{ route('terms') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Terms & Conditions</a></li>
                            <li><a href="{{ route('privacy') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400">Project</h3>
                        <ul class="mt-4 space-y-2">
                            <li><a href="{{ route('architecture') }}" class="text-sm text-zinc-500 transition hover:text-white" wire:navigate>Architecture</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 border-t border-zinc-800 pt-8">
                    <p class="text-center text-xs text-zinc-600">
                        {{ config('app.name') }} does not host, store, or distribute any video content. All streams are provided by third-party external sources.
                        Metadata provided by <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="text-zinc-500 underline hover:text-white">TMDB</a>.
                    </p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
