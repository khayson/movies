<x-layouts::guest>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="mb-10 border-b border-zinc-800 pb-8">
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-amber-500">Legal</p>
            <h1 class="mb-3 font-serif text-3xl font-bold md:text-4xl">Privacy Policy</h1>
            <p class="max-w-xl text-sm leading-relaxed text-zinc-400">{{ config('app.name') }} is built with privacy in mind. This policy explains what data we collect, why, and how we protect it. The short version: we collect as little as possible.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded bg-amber-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-amber-400">Effective: July 22, 2026</span>
                <span class="rounded bg-teal-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-teal-400">Minimal Data Collection</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">Open Source</span>
            </div>
        </header>

        <div class="prose-invert prose-sm max-w-none [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-sm [&_h3]:font-bold [&_li]:text-zinc-300 [&_p]:mb-3 [&_p]:leading-relaxed [&_p]:text-zinc-300 [&_strong]:text-white [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5">
            <div class="mb-8 rounded-lg border border-teal-800/50 bg-teal-950/20 px-5 py-4">
                <p class="mb-1 text-xs font-bold uppercase tracking-wider text-teal-400">Privacy-First Approach</p>
                <p class="text-sm text-teal-300/80">{{ config('app.name') }} is open source. You can audit exactly what data is collected by reading the source code. We do not sell, share, or monetize your data. There are no analytics trackers, no ad networks, and no third-party data brokers.</p>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">1</p>
            <h2>Information We Collect</h2>

            <h3>Account Data (if you register)</h3>
            <div class="mb-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 text-left text-xs uppercase tracking-wider text-zinc-500">
                            <th class="pb-2 pr-4">Data</th>
                            <th class="pb-2 pr-4">Purpose</th>
                            <th class="pb-2">Storage</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-300">
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">Name</td><td class="py-2 pr-4">Display in your profile</td><td class="py-2 text-zinc-500">Local database</td></tr>
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">Email</td><td class="py-2 pr-4">Authentication, password reset</td><td class="py-2 text-zinc-500">Local database</td></tr>
                        <tr><td class="py-2 pr-4 font-medium text-white">Password</td><td class="py-2 pr-4">Authentication</td><td class="py-2 text-zinc-500">Stored as bcrypt hash only</td></tr>
                    </tbody>
                </table>
            </div>

            <h3>Usage Data (if logged in)</h3>
            <div class="mb-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 text-left text-xs uppercase tracking-wider text-zinc-500">
                            <th class="pb-2 pr-4">Data</th>
                            <th class="pb-2 pr-4">Purpose</th>
                            <th class="pb-2">Retention</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-300">
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">Favorites</td><td class="py-2 pr-4">Your saved list</td><td class="py-2 text-zinc-500">Until you remove them</td></tr>
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">Watch history</td><td class="py-2 pr-4">Continue watching</td><td class="py-2 text-zinc-500">Until you clear or delete account</td></tr>
                        <tr><td class="py-2 pr-4 font-medium text-white">Playback position</td><td class="py-2 pr-4">Resume from where you left off</td><td class="py-2 text-zinc-500">Overwritten each session</td></tr>
                    </tbody>
                </table>
            </div>

            <h3>What We Do Not Collect</h3>
            <ul>
                <li>IP addresses — we do not log or store visitor IPs.</li>
                <li>Device fingerprints or browser identifiers.</li>
                <li>Tracking cookies or advertising identifiers.</li>
                <li>Usage analytics or behavioral profiling data.</li>
                <li>Payment information — the service is completely free.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">2</p>
            <h2>How We Use Your Data</h2>
            <ul>
                <li><strong>Authentication</strong> — to let you log in and maintain your session.</li>
                <li><strong>Personalization</strong> — to show your favorites, watch history, and continue-watching state.</li>
                <li><strong>Account management</strong> — to let you update your profile and delete your account.</li>
            </ul>
            <p>We do not use your data for advertising, profiling, or any purpose other than those listed above.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">3</p>
            <h2>Third-Party Services</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-700 text-left text-xs uppercase tracking-wider text-zinc-500">
                            <th class="pb-2 pr-4">Service</th>
                            <th class="pb-2 pr-4">Data Shared</th>
                            <th class="pb-2">Purpose</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-300">
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">TMDB API</td><td class="py-2 pr-4">Search queries, content IDs</td><td class="py-2 text-zinc-500">Fetching metadata</td></tr>
                        <tr class="border-b border-zinc-800"><td class="py-2 pr-4 font-medium text-white">TMDB CDN</td><td class="py-2 pr-4">Your IP (browser request)</td><td class="py-2 text-zinc-500">Loading images</td></tr>
                        <tr><td class="py-2 pr-4 font-medium text-white">Stream sources</td><td class="py-2 pr-4">Content IDs, your IP</td><td class="py-2 text-zinc-500">Loading video content</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="rounded-lg border border-amber-800/50 bg-amber-950/20 px-4 py-3">
                <p class="text-sm text-amber-300/70">When you play content, your browser connects directly to third-party streaming servers. These servers may have their own data collection practices outside our control. We recommend using a VPN if privacy during playback is a concern.</p>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">4</p>
            <h2>Cookies &amp; Local Storage</h2>
            <ul>
                <li><strong>Session cookie</strong> — maintains your login state. Essential only.</li>
                <li><strong>CSRF token</strong> — protects forms from cross-site request forgery.</li>
            </ul>
            <p>We do not use tracking cookies, analytics cookies, or any third-party cookie services.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">5</p>
            <h2>Data Storage &amp; Security</h2>
            <ul>
                <li>Passwords are hashed using bcrypt with a cost factor of 12.</li>
                <li>Sessions are encrypted and stored server-side.</li>
                <li>CSRF protection on all forms and state-changing requests.</li>
                <li>All communication over HTTPS in production.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">6</p>
            <h2>Your Rights</h2>
            <ul>
                <li><strong>Access</strong> — view all data we hold about you through your profile settings.</li>
                <li><strong>Rectify</strong> — update your name, email, or password at any time.</li>
                <li><strong>Delete</strong> — permanently delete your account and all associated data.</li>
                <li><strong>Export</strong> — since the platform is open source, you can self-host and control your data entirely.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">7</p>
            <h2>Children's Privacy</h2>
            <p>{{ config('app.name') }} is not directed at children under 13. We do not knowingly collect personal information from children.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">8</p>
            <h2>Self-Hosted Instances</h2>
            <p>This privacy policy applies only to instances operated by the original project maintainers. Operators of self-hosted instances are responsible for their own privacy practices.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">9</p>
            <h2>Changes to This Policy</h2>
            <p>We may update this privacy policy as the project evolves. Since the project is open source, you can track all changes through the version control history.</p>
        </div>

        <div class="mt-12 border-t border-zinc-800 pt-6">
            <p class="text-xs text-zinc-600">Last updated July 22, 2026. {{ config('app.name') }} is committed to transparency.</p>
        </div>
    </div>
</x-layouts::guest>
