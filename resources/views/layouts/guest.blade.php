<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            /* At the top of the page the nav floats over the hero as a soft scrim, so the artwork
               is never cut off; once scrolled it becomes the frosted bar. */
            .site-nav { background: linear-gradient(to bottom, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.25) 60%, rgba(0, 0, 0, 0) 100%); border-bottom: 1px solid transparent; transition: background 0.3s ease, border-color 0.3s ease; }
            .site-nav.is-scrolled { background: transparent; border-bottom-color: rgba(255, 255, 255, 0.06); }
            /* Blur lives on a pseudo-element: backdrop-filter on the nav itself would make it
               the containing block for its position:fixed descendants (dropdown panels/overlays). */
            .site-nav.is-scrolled::before { content: ''; position: absolute; inset: 0; z-index: -1; background: rgba(9, 9, 11, 0.7); backdrop-filter: blur(20px) saturate(1.8); -webkit-backdrop-filter: blur(20px) saturate(1.8); }
            /* Cinematic heroes run up underneath the overlay nav, which is a single 4rem row at every width. */
            .hero-bleed { margin-top: -4rem; }
            .nav-item { position: relative; }
            .nav-item::after { content: ''; position: absolute; bottom: -1px; left: 50%; width: 0; height: 2px; background: linear-gradient(90deg, #f59e0b, #d97706); transition: all 0.3s ease; transform: translateX(-50%); border-radius: 1px; }
            .nav-item.active::after, .nav-item:hover::after { width: 100%; }
            .footer-glow { background: radial-gradient(ellipse 80% 50% at 50% 100%, rgba(245,158,11,0.04) 0%, transparent 70%); }
            [x-cloak] { display: none !important; }
            .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
            .reveal.revealed { opacity: 1; transform: translateY(0); }
            .reveal-delay-1 { transition-delay: 0.1s; }
            .reveal-delay-2 { transition-delay: 0.2s; }
            .reveal-delay-3 { transition-delay: 0.3s; }
        </style>
    </head>
    <body class="min-h-screen overflow-x-clip bg-zinc-950 text-white antialiased">
        @include('partials.site-header')

        {{-- Main content --}}
        <main>
            {{ $slot }}
        </main>

        @include('partials.site-footer')

        @auth
            @include('partials.logout-confirm-modal')
        @endauth

        @persist('toast')
            <flux:toast position="top center" />
        @endpersist

        @fluxScripts

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const obs = new IntersectionObserver((entries) => {
                    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); } });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
                document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
            });
            document.addEventListener('livewire:navigated', () => {
                const obs = new IntersectionObserver((entries) => {
                    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); } });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
                document.querySelectorAll('.reveal:not(.revealed)').forEach(el => obs.observe(el));
            });
        </script>

        @include('partials.title-modal')
    </body>
</html>
