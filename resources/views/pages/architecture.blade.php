<x-layouts::guest>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="mb-10 border-b border-zinc-800 pb-8">
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-amber-500">Architecture Document</p>
            <h1 class="mb-3 font-serif text-3xl font-bold md:text-4xl">How {{ config('app.name') }} Works</h1>
            <p class="max-w-xl text-sm leading-relaxed text-zinc-400">A zero-budget, open-source streaming platform built on Laravel, Livewire, and TMDB. This page explains the technical architecture for developers and contributors.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded bg-amber-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-amber-400">Laravel 13</span>
                <span class="rounded bg-teal-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-teal-400">Livewire 4</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">TMDB API</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">SQLite</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">Zero Cost</span>
            </div>
        </header>

        <div class="prose-invert prose-sm max-w-none [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-sm [&_h3]:font-bold [&_li]:text-zinc-300 [&_p]:mb-3 [&_p]:leading-relaxed [&_p]:text-zinc-300 [&_strong]:text-white [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">I</p>
            <h2>Overview</h2>
            <p>{{ config('app.name') }} is a monolithic Laravel application that proxies TMDB for metadata, stores user data in SQLite, and delegates video playback to an embedded client-side player that fetches streams from external sources. No media is stored or transcoded locally.</p>

            {{-- Data flow diagram --}}
            <div class="mb-6 overflow-x-auto rounded-lg border border-zinc-800 bg-zinc-900 p-6">
                <div class="flex min-w-[500px] items-center justify-center gap-3 text-sm">
                    <div class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 font-semibold">Browser</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-amber-700 bg-amber-900/20 px-4 py-2 font-semibold text-amber-400">Laravel + Livewire</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-teal-700 bg-teal-900/20 px-4 py-2 font-semibold text-teal-400">TMDB API</div>
                </div>
                <div class="mt-4 flex min-w-[500px] items-center justify-center gap-3 text-sm">
                    <div class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 font-semibold">Video Player</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-amber-700 bg-amber-900/20 px-4 py-2 font-semibold text-amber-400">Source Resolver</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-teal-700 bg-teal-900/20 px-4 py-2 font-semibold text-teal-400">External Stream</div>
                </div>
            </div>

            <ul>
                <li><strong>Server-rendered UI</strong> via Livewire — no SPA complexity, no separate frontend deploy.</li>
                <li><strong>TMDB as the source of truth</strong> for all metadata — no local movie/show tables needed.</li>
                <li><strong>Aggressive caching</strong> — TMDB responses cached in SQLite via Laravel's cache.</li>
                <li><strong>Source resolution is abstracted</strong> — providers are swappable via configuration.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">II</p>
            <h2>Tech Stack</h2>
            <div class="mb-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 text-left text-xs uppercase tracking-wider text-zinc-500">
                            <th class="pb-2 pr-4">Layer</th>
                            <th class="pb-2 pr-4">Tool</th>
                            <th class="pb-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-300 [&_td]:border-b [&_td]:border-zinc-800 [&_td]:py-2 [&_td]:pr-4">
                        <tr><td class="font-medium text-white">Backend</td><td class="text-teal-400">Laravel 13 + PHP 8.4</td><td class="text-zinc-500">Routing, caching, auth, API proxy</td></tr>
                        <tr><td class="font-medium text-white">Frontend</td><td class="text-teal-400">Livewire 4 + Flux UI 2</td><td class="text-zinc-500">Server-rendered reactive components</td></tr>
                        <tr><td class="font-medium text-white">Styling</td><td class="text-teal-400">Tailwind CSS 4</td><td class="text-zinc-500">Utility-first, zero runtime cost</td></tr>
                        <tr><td class="font-medium text-white">Database</td><td class="text-teal-400">SQLite</td><td class="text-zinc-500">Users, favorites, watch history, cache</td></tr>
                        <tr><td class="font-medium text-white">Metadata</td><td class="text-teal-400">TMDB API v3</td><td class="text-zinc-500">Free tier: 40 req/sec, unlimited daily</td></tr>
                        <tr><td class="font-medium text-white">Auth</td><td class="text-teal-400">Laravel Fortify</td><td class="text-zinc-500">Registration, login, 2FA</td></tr>
                        <tr><td class="!border-b-0 font-medium text-white">Caching</td><td class="!border-b-0 text-teal-400">Laravel Cache (SQLite)</td><td class="!border-b-0 text-zinc-500">Configurable TTL per endpoint</td></tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">III</p>
            <h2>Data Flow</h2>
            <p>The TMDB service class (<code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs text-zinc-300">app/Services/Tmdb.php</code>) wraps all API calls with automatic caching. Each endpoint has a configured cache TTL:</p>
            <div class="mb-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 text-left text-xs uppercase tracking-wider text-zinc-500">
                            <th class="pb-2 pr-4">Data</th>
                            <th class="pb-2 pr-4">Cache TTL</th>
                            <th class="pb-2">Rationale</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-300 [&_td]:border-b [&_td]:border-zinc-800 [&_td]:py-2 [&_td]:pr-4">
                        <tr><td class="font-medium text-white">Trending / Popular</td><td class="text-teal-400">6 hours</td><td class="text-zinc-500">TMDB updates daily</td></tr>
                        <tr><td class="font-medium text-white">Movie / TV details</td><td class="text-teal-400">24 hours</td><td class="text-zinc-500">Metadata rarely changes</td></tr>
                        <tr><td class="font-medium text-white">Search results</td><td class="text-teal-400">1 hour</td><td class="text-zinc-500">New content appears quickly</td></tr>
                        <tr><td class="!border-b-0 font-medium text-white">Video sources</td><td class="!border-b-0 text-teal-400">1 hour</td><td class="!border-b-0 text-zinc-500">Stream URLs expire</td></tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">IV</p>
            <h2>Source Resolution</h2>
            <p>The source resolver (<code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs text-zinc-300">app/Services/SourceResolver.php</code>) maps TMDB IDs to playable video URLs by querying configured providers in priority order. The provider interface is swappable — you can add or remove providers without touching the rest of the app.</p>
            <p>Current provider chain:</p>
            <ul>
                <li><strong>Embed provider</strong> — builds iframe URLs from a configurable base URL + TMDB ID</li>
                <li><strong>Trailer fallback</strong> — extracts YouTube trailer keys from TMDB's video data</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">V</p>
            <h2>Database Schema</h2>
            <p>Only user-specific data is stored locally. Movie metadata lives in TMDB; we cache but never persist it.</p>
            <div class="rounded-lg bg-zinc-900 p-4 font-mono text-xs text-zinc-300">
                <p class="mb-2 font-bold text-amber-400">favorites</p>
                <p class="pl-4">id, user_id, tmdb_id, media_type, title, poster_path, timestamps</p>
                <p class="mb-2 mt-4 font-bold text-amber-400">watch_histories</p>
                <p class="pl-4">id, user_id, tmdb_id, media_type, title, poster_path,</p>
                <p class="pl-4">progress_seconds, duration_seconds, season, episode, timestamps</p>
            </div>

            <p class="mt-10 text-xs font-bold uppercase tracking-wider text-amber-500">VI</p>
            <h2>Contributing</h2>
            <p>{{ config('app.name') }} is open source. Contributions are welcome — whether it's adding new source providers, improving the UI, writing tests, or fixing bugs. Fork the repo, make your changes, and open a pull request.</p>
        </div>

        <div class="mt-12 border-t border-zinc-800 pt-6">
            <p class="text-xs text-zinc-600">{{ config('app.name') }} is an open-source project. Built with Laravel, Livewire, and TMDB.</p>
        </div>
    </div>
</x-layouts::guest>
