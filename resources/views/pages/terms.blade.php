<x-layouts::guest>
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="mb-10 border-b border-zinc-800 pb-8">
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-amber-500">Legal</p>
            <h1 class="mb-3 font-serif text-3xl font-bold md:text-4xl">Terms &amp; Conditions</h1>
            <p class="max-w-xl text-sm leading-relaxed text-zinc-400">Please read these terms carefully before using {{ config('app.name') }}. By accessing or using the platform, you agree to be bound by these terms.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded bg-amber-900/40 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-amber-400">Effective: July 22, 2026</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400">Open Source Project</span>
            </div>
        </header>

        <div class="prose-invert prose-sm max-w-none [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-sm [&_h3]:font-bold [&_li]:text-zinc-300 [&_p]:mb-3 [&_p]:leading-relaxed [&_p]:text-zinc-300 [&_strong]:text-white [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5">
            {{-- Disclaimer --}}
            <div class="mb-8 rounded-lg border border-red-900/50 bg-red-950/20 px-5 py-4">
                <p class="mb-1 text-xs font-bold uppercase tracking-wider text-red-400">External Content Disclaimer</p>
                <p class="text-sm text-red-300/80">{{ config('app.name') }} does not host, store, upload, or distribute any video content. All video streams accessible through this platform are provided by third-party external sources. {{ config('app.name') }} acts solely as an interface that indexes publicly available metadata and links to external content. We have no control over the content, availability, or legality of media provided by third-party sources.</p>
            </div>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">1</p>
            <h2>Acceptance of Terms</h2>
            <p>By accessing {{ config('app.name') }} ("the Platform," "we," "our"), you agree to comply with and be bound by these Terms & Conditions. If you do not agree, you must not use the Platform.</p>
            <p>{{ config('app.name') }} is an open-source project provided "as is." We reserve the right to modify these terms at any time. Continued use after changes constitutes acceptance.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">2</p>
            <h2>Nature of the Service</h2>
            <p>{{ config('app.name') }} is a metadata aggregation and browsing platform. The service provides:</p>
            <ul>
                <li>Movie and TV show metadata sourced from The Movie Database (TMDB) API.</li>
                <li>Search, browsing, and organizational features for discovering content.</li>
                <li>Links or references to video content hosted on third-party external servers.</li>
            </ul>
            <p>{{ config('app.name') }} does <strong>not</strong>:</p>
            <ul>
                <li>Host, upload, store, or transcode any video files on its own servers.</li>
                <li>Own, license, or claim rights to any video content accessible through the platform.</li>
                <li>Guarantee the availability, quality, or legality of third-party streams.</li>
                <li>Endorse, verify, or take responsibility for external content.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">3</p>
            <h2>User Responsibilities</h2>
            <p>As a user, you acknowledge and agree that:</p>
            <ul>
                <li>You are solely responsible for your use of the Platform and any content you access through it.</li>
                <li>You will comply with all applicable local, national, and international laws regarding online content consumption.</li>
                <li>You understand that streaming or downloading copyrighted material without authorization may be illegal in your jurisdiction.</li>
                <li>You will not use the Platform for any unlawful purpose or in violation of any third party's rights.</li>
                <li>You will not attempt to reverse-engineer, modify, or exploit the Platform's infrastructure for malicious purposes.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">4</p>
            <h2>Accounts &amp; Registration</h2>
            <p>Account creation is optional. If you create an account:</p>
            <ul>
                <li>You are responsible for maintaining the security of your credentials.</li>
                <li>You must provide accurate information during registration.</li>
                <li>You may delete your account and all associated data at any time.</li>
                <li>We reserve the right to suspend or terminate accounts that violate these terms.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">5</p>
            <h2>Intellectual Property</h2>
            <p>The {{ config('app.name') }} platform source code is released under an open-source license. This applies to the code, not to the content accessed through it:</p>
            <ul>
                <li>All movie and TV metadata (titles, descriptions, posters, cast information) is provided by TMDB and subject to their terms of use.</li>
                <li>Video content is owned by its respective copyright holders.</li>
                <li>The {{ config('app.name') }} name, logo, and original UI design are part of the open-source project.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">6</p>
            <h2>DMCA &amp; Copyright Claims</h2>
            <p>Since {{ config('app.name') }} does not host any content, we cannot remove content from third-party servers. However:</p>
            <ul>
                <li>If you are a copyright holder and believe a link on our platform points to infringing content, contact us and we will remove the link or reference promptly.</li>
                <li>Please include: identification of the copyrighted work, the specific URL on our platform, your contact information, and a statement of good faith.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">7</p>
            <h2>Limitation of Liability</h2>
            <div class="rounded-lg border border-amber-800/50 bg-amber-950/20 px-4 py-3">
                <p class="mb-1 text-xs font-bold uppercase tracking-wider text-amber-400">No Warranties</p>
                <p class="text-sm text-amber-300/70">{{ config('app.name') }} is provided "as is" and "as available" without warranties of any kind, express or implied. We do not warrant that the service will be uninterrupted, secure, or error-free.</p>
            </div>
            <ul class="mt-4">
                <li>We are not liable for any direct, indirect, incidental, or consequential damages arising from your use of the Platform.</li>
                <li>We are not responsible for the content, practices, or availability of third-party sources.</li>
                <li>We are not liable for any legal consequences resulting from your access to third-party content.</li>
                <li>Total liability, if any, shall not exceed the amount you paid to use the service (which is zero, as the service is free).</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">8</p>
            <h2>Third-Party Services</h2>
            <ul>
                <li><strong>TMDB</strong> — provides metadata. Subject to TMDB's own terms and privacy policy.</li>
                <li><strong>External streaming sources</strong> — provide video content. We have no affiliation with, control over, or responsibility for these services.</li>
            </ul>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">9</p>
            <h2>Open Source</h2>
            <p>{{ config('app.name') }} is an open-source project. The source code is available for review, modification, and distribution under the project's license terms. Self-hosted instances are independently operated and subject to their own terms.</p>

            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">10</p>
            <h2>Changes to Terms</h2>
            <p>We reserve the right to update these terms at any time. Your continued use after changes are posted constitutes acceptance.</p>
        </div>

        <div class="mt-12 border-t border-zinc-800 pt-6">
            <p class="text-xs text-zinc-600">Last updated July 22, 2026. {{ config('app.name') }} is an open-source project.</p>
        </div>
    </div>
</x-layouts::guest>
