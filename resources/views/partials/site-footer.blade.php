@php
    $footerColumns = [
        'Watch' => [
            ['route' => 'movies.index', 'label' => 'Movies'],
            ['route' => 'tv.index', 'label' => 'TV Shows'],
            ['route' => 'anime.index', 'label' => 'Anime'],
            ['route' => 'new-releases', 'label' => 'New Releases'],
            ['route' => 'coming-soon', 'label' => 'Coming Soon'],
            ['route' => 'trending', 'label' => 'Trending'],
        ],
        'African Cinema' => [
            ['route' => 'nollywood', 'label' => 'Nollywood'],
            ['route' => 'ghallywood', 'label' => 'Ghallywood'],
        ],
        'Discover' => [
            ['route' => 'genres.index', 'label' => 'Genres'],
            ['route' => 'discover', 'label' => 'Advanced Search'],
            ['route' => 'mood.index', 'label' => 'Mood Picker'],
            ['route' => 'trailers', 'label' => 'Trailers'],
            ['route' => 'collections.index', 'label' => 'Collections'],
            ['route' => 'people.index', 'label' => 'People'],
        ],
        'Community' => [
            ['route' => 'leaderboard', 'label' => 'Leaderboard'],
            ['route' => 'activity.feed', 'label' => 'Activity Feed'],
            ['route' => 'watch-parties', 'label' => 'Watch Parties'],
            ['route' => 'quiz', 'label' => 'Trivia'],
        ],
        'StreamVault' => [
            ['route' => 'welcome', 'label' => 'Why StreamVault'],
            ['route' => 'terms', 'label' => 'Terms'],
            ['route' => 'privacy', 'label' => 'Privacy'],
            ['route' => 'architecture', 'label' => 'Architecture'],
        ],
    ];
@endphp

<footer class="footer-glow mt-16 border-t border-white/[0.06]">
    @guest
        <div class="border-b border-white/[0.06]">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 py-10 sm:px-6 md:flex-row md:items-center lg:px-8">
                <div>
                    <p class="text-xl font-bold tracking-tight text-white sm:text-2xl">Make it yours. It's free.</p>
                    <p class="mt-1 text-sm text-zinc-400">Profiles for everyone at home, a list that follows you, and picking up right where you stopped.</p>
                </div>
                <div class="flex shrink-0 gap-3">
                    <a href="{{ route('register') }}" class="btn-primary" wire:navigate>Create free account</a>
                    <a href="{{ route('welcome') }}" class="btn-secondary" wire:navigate>Learn more</a>
                </div>
            </div>
        </div>
    @endguest

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1.4fr_4fr] lg:gap-12">
            <div>
                @include('partials.site-logo')
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">
                    New movies, series, anime and African cinema in one place, with profiles for the whole household.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($footerColumns as $heading => $links)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ $heading }}</h3>
                        <ul class="mt-3 space-y-2">
                            @foreach($links as $link)
                                <li><a href="{{ route($link['route']) }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>{{ $link['label'] }}</a></li>
                            @endforeach
                            @if($heading === 'StreamVault')
                                @auth
                                    <li><a href="{{ route('profile.edit') }}" class="text-sm text-zinc-500 transition-colors hover:text-white" wire:navigate>Settings</a></li>
                                @endauth
                                @if(filled(config('services.support.url')))
                                    <li>
                                        <a href="{{ config('services.support.url') }}" target="_blank" rel="noopener" class="text-sm font-medium text-amber-400/90 transition-colors hover:text-amber-300">
                                            {{ config('services.support.label') }}
                                        </a>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center gap-4 border-t border-white/[0.06] pt-8 sm:flex-row sm:justify-between">
            <p class="max-w-lg text-center text-xs leading-relaxed text-zinc-600 sm:text-left">
                {{ config('app.name') }} does not host, store, or distribute any video content. All streams are provided by third-party external sources.
                Metadata provided by <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="text-zinc-500 underline decoration-zinc-700 underline-offset-2 transition-colors hover:text-white">TMDB</a>.
            </p>
            <div class="flex flex-col items-center gap-2 sm:items-end">
                <button
                    type="button"
                    x-data="{ canInstall: false, isIos: /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream, showIosTip: false }"
                    x-init="
                        canInstall = !!(window.streamVaultInstall?.available);
                        window.addEventListener('pwa-installable', () => { canInstall = true });
                    "
                    x-show="canInstall || isIos"
                    x-cloak
                    @click="
                        if (canInstall) { window.streamVaultInstall.prompt(); }
                        else { showIosTip = !showIosTip; }
                    "
                    class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-300 transition hover:bg-amber-500/20"
                >
                    <span x-text="isIos && !canInstall ? 'Add to Home Screen' : 'Install app'"></span>
                    <span x-show="showIosTip" x-cloak class="mt-2 block max-w-[14rem] text-[11px] font-normal leading-snug text-zinc-400">
                        On iPhone: tap Share, then “Add to Home Screen”.
                    </span>
                </button>
                <p class="text-xs text-zinc-700">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
</footer>
