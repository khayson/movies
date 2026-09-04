<x-layouts::guest>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="mb-10 border-b border-zinc-800 pb-8">
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-amber-500">Architecture Document</p>
            <h1 class="mb-3 font-serif text-3xl font-bold md:text-4xl">How {{ config('app.name') }} Works</h1>
            <p class="max-w-xl text-sm leading-relaxed text-zinc-400">A streaming companion powered by live metadata APIs and a multi-provider playback layer. No media is stored or transcoded on our servers.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded bg-amber-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-amber-400">PHP 8.4</span>
                <span class="rounded bg-teal-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-teal-400">Laravel 13</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">Livewire 4</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">TMDB</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">Postgres</span>
            </div>
        </header>

        <div class="prose-invert prose-sm max-w-none [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-sm [&_h3]:font-bold [&_li]:text-zinc-300 [&_p]:mb-3 [&_p]:leading-relaxed [&_p]:text-zinc-300 [&_strong]:text-white [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">I</p>
            <h2>Overview</h2>
            <p>{{ config('app.name') }} is a Laravel monolith: Livewire for UI, TMDB for metadata, Fortify for auth, and a source resolver that picks among many third-party embed/HLS providers with health scoring and auto-fallback.</p>

            <div class="mb-6 overflow-x-auto rounded-lg border border-zinc-800 bg-zinc-900 p-6">
                <div class="flex min-w-[560px] items-center justify-center gap-3 text-sm">
                    <div class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 font-semibold">Browser</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-amber-700 bg-amber-900/20 px-4 py-2 font-semibold text-amber-400">{{ config('app.name') }}</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-teal-700 bg-teal-900/20 px-4 py-2 font-semibold text-teal-400">TMDB + enrichers</div>
                </div>
                <div class="mt-4 flex min-w-[560px] items-center justify-center gap-3 text-sm">
                    <div class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 font-semibold">Watch page</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-amber-700 bg-amber-900/20 px-4 py-2 font-semibold text-amber-400">SourceResolver</div>
                    <span class="text-zinc-600">&rarr;</span>
                    <div class="rounded-lg border border-teal-700 bg-teal-900/20 px-4 py-2 font-semibold text-teal-400">Embed / HLS providers</div>
                </div>
            </div>

            <ul>
                <li><strong>Server-rendered UI</strong> via Livewire — no separate SPA deploy.</li>
                <li><strong>TMDB as catalog truth</strong> — no local movie tables; only user data is stored.</li>
                <li><strong>Multi-provider playback</strong> — balanced defaults, health probes, analytics, auto-fallback.</li>
                <li><strong>Optional CineSrc Direct</strong> — HLS when <code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs">CINESRC_RESOLVER_URL</code> is set.</li>
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
                        <tr><td class="font-medium text-white">Backend</td><td class="text-teal-400">Laravel 13 / PHP 8.4</td><td class="text-zinc-500">Routing, cache, queues, auth</td></tr>
                        <tr><td class="font-medium text-white">Frontend</td><td class="text-teal-400">Livewire 4 + Flux UI</td><td class="text-zinc-500">Reactive pages + Alpine on watch</td></tr>
                        <tr><td class="font-medium text-white">Styling</td><td class="text-teal-400">Tailwind CSS 4</td><td class="text-zinc-500">Vite-built assets</td></tr>
                        <tr><td class="font-medium text-white">Database</td><td class="text-teal-400">SQLite / Postgres</td><td class="text-zinc-500">Users, history, analytics</td></tr>
                        <tr><td class="font-medium text-white">Metadata</td><td class="text-teal-400">TMDB (+ TvMaze fallback)</td><td class="text-zinc-500">Cached per endpoint TTL</td></tr>
                        <tr><td class="font-medium text-white">Cache / queue</td><td class="text-teal-400">Database (Redis optional)</td><td class="text-zinc-500">Cron drains queued jobs; Redis via <code class="rounded bg-zinc-800 px-1 text-xs">CACHE_STORE=redis</code></td></tr>
                        <tr><td class="font-medium text-white">Auth</td><td class="text-teal-400">Fortify</td><td class="text-zinc-500">2FA, passkeys (email verify optional / off until domain ready)</td></tr>
                        <tr><td class="font-medium text-white">Mail</td><td class="text-teal-400">Resend (free)</td><td class="text-zinc-500">Verification, resets, capped weekly digest</td></tr>
                        <tr><td class="!border-b-0 font-medium text-white">Deploy</td><td class="!border-b-0 text-teal-400">Docker / Render</td><td class="!border-b-0 text-zinc-500">External cron hits /cron/&#123;token&#125; (schedule + queue drain)</td></tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">III</p>
            <h2>Playback pipeline</h2>
            <p>Playback is split across focused services:</p>
            <ul>
                <li><strong>SourceResolver</strong> — builds the provider list (embeds + CineSrc + trailer).</li>
                <li><strong>EmbedUrlBuilder</strong> — adds autoplay, resume, theme, subtitle params per provider.</li>
                <li><strong>ProviderScorer</strong> — scores servers from base reliability, prefs, analytics, and probes.</li>
                <li><strong>ProviderAnalyticsTracker</strong> — success / failure / buffer events and region/hour boosts.</li>
                <li><strong>ProviderHealthProbe</strong> — scheduled reachability checks that demote dead hosts.</li>
                <li><strong>RapidApiClient</strong> — shared RapidAPI HTTP layer with rate limits and a per-host circuit breaker.</li>
            </ul>
            <p>The watch page listens for postMessage from providers that support it (VidCore, VidSrc, VidLink, CineSrc). Others use an 8s auto-fallback timer plus heartbeat progress.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">IV</p>
            <h2>Provider chain</h2>
            <p>Configured in <code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs text-zinc-300">config/sources.php</code>. Defaults rotate among top-tier hosts so one provider is not overused.</p>
            <ul>
                <li><strong>Embeds</strong> — VidCore, VidPhantom, VidSrc, EzVidAPI, VidLink, SuperEmbed, Embed API, AutoEmbed, MoviesAPI, VidBinge, VikingEmbed</li>
                <li><strong>CineSrc</strong> — rich embed + optional HLS Direct</li>
                <li><strong>Trailer</strong> — YouTube fallback from TMDB videos</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">V</p>
            <h2>Database (local only)</h2>
            <p>Movie metadata stays in TMDB. Local tables cover users, favorites, watch history, social graph, provider analytics, and affiliate clicks.</p>
            <div class="rounded-lg bg-zinc-900 p-4 font-mono text-xs text-zinc-300">
                <p class="mb-2 font-bold text-amber-400">watch_histories</p>
                <p class="pl-4">progress, duration, last_server, cinesrc_server_id, device…</p>
                <p class="mb-2 mt-4 font-bold text-amber-400">provider_analytics</p>
                <p class="pl-4">provider × region × hour × date — success / failure / buffer / load</p>
            </div>

            <p class="mt-10 text-xs font-bold uppercase tracking-wider text-amber-500">VI</p>
            <h2>Contributing</h2>
            <p>Add providers via <code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs">config/sources.php</code> (<code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs">embed_options</code> + <code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs">postmessage</code> when available). Keep playback changes behind tests in <code class="rounded bg-zinc-800 px-1.5 py-0.5 text-xs">SourceResolverTest</code> and related feature suites.</p>
        </div>

        <div class="mt-12 border-t border-zinc-800 pt-6">
            <p class="text-xs text-zinc-600">{{ config('app.name') }} — Laravel, Livewire, TMDB. Streams come from third-party providers.</p>
        </div>
    </div>
</x-layouts::guest>
