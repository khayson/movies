<?php

use App\Models\Profile;
use App\Services\Tmdb;
use App\Services\TopTen;
use App\Support\ApiPayload;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Welcome')]
class extends Component
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{id: int, title: string, poster: string, type: string}>
     */
    private function posters(Tmdb $tmdb, array $payload, string $defaultType, int $limit): array
    {
        $items = [];

        foreach (ApiPayload::rows($payload['results'] ?? null) as $item) {
            if (! is_int($item['id'] ?? null) || empty($item['poster_path'])) {
                continue;
            }

            $type = ($item['media_type'] ?? $defaultType) === 'tv' ? 'tv' : 'movie';

            if (($item['media_type'] ?? $type) === 'person') {
                continue;
            }

            $items[] = [
                'id' => $item['id'],
                'title' => (string) ($item['title'] ?? $item['name'] ?? ''),
                'poster' => $tmdb->imageUrl((string) $item['poster_path'], 'w342'),
                'type' => $type,
            ];

            if (count($items) === $limit) {
                break;
            }
        }

        return $items;
    }

    public function with(Tmdb $tmdb, TopTen $topTen): array
    {
        View::share('ogDescription', 'New movies, series, anime and African cinema in one free place, with profiles for everyone at home.');

        return [
            'wall' => $this->posters($tmdb, $tmdb->trending('all', 'week'), 'movie', 24),
            'topTen' => $topTen->forType('movie'),
            'fresh' => $this->posters($tmdb, $tmdb->nowPlaying(), 'movie', 5),
            'african' => $this->posters($tmdb, $tmdb->discoverByOriginCountry('movie', 'NG'), 'movie', 4),
            'anime' => $this->posters($tmdb, $tmdb->anime('tv'), 'tv', 4),
            'avatarColors' => array_values(Profile::AVATAR_COLORS),
            'faqs' => [
                ['q' => 'What is StreamVault?', 'a' => 'StreamVault brings new movies, TV series, anime and African cinema together in one place. Browse, watch trailers, track what you have seen and pick up where you left off on any device.'],
                ['q' => 'How much does it cost?', 'a' => 'Nothing. Browsing is open to everyone, and a free account adds profiles, My List, watch history and personalised rows.'],
                ['q' => 'Does StreamVault host the videos?', 'a' => 'No. StreamVault does not host, store or distribute any video. Playback comes from third-party providers, and title information comes from TMDB.'],
                ['q' => 'Can my household share one account?', 'a' => 'Yes. Each account can have up to five profiles, and every profile keeps its own history, My List, favourites and recommendations.'],
                ['q' => 'Is it safe for kids?', 'a' => 'Adult content is off by default. It only becomes available to signed-in, age-verified adults who turn it on in their settings, and can be locked behind a password.'],
                ['q' => 'Can I install it on my phone?', 'a' => 'Yes. StreamVault works as an app: use Install app in your browser, or on iPhone tap Share and then Add to Home Screen.'],
            ],
        ];
    }
}; ?>

@php
    $ctaUrl = auth()->check() ? route('home') : route('register');
    $ctaLabel = auth()->check() ? 'Start watching' : 'Get Started — it\'s free';
@endphp

<div>
    {{-- Hero: a tilted wall of this week's posters behind the headline --}}
    <section class="hero-bleed relative isolate flex min-h-[clamp(560px,92vh,880px)] items-center overflow-hidden">
        <div class="absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -inset-x-1/4 -top-1/4 grid -rotate-12 grid-cols-4 gap-3 opacity-45 sm:grid-cols-6 lg:grid-cols-8">
                @foreach(array_merge($wall, $wall) as $poster)
                    <img src="{{ $poster['poster'] }}" alt="" loading="lazy" class="aspect-[2/3] w-full rounded-md object-cover">
                @endforeach
            </div>
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/80 via-zinc-950/70 to-zinc-950"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(9,9,11,0.85)_70%)]"></div>
        </div>

        <div class="mx-auto w-full max-w-3xl px-4 pt-24 text-center sm:px-6">
            <p class="eyebrow">Free to browse · No subscription</p>
            <h1 class="mt-4 text-4xl font-black leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl">
                Every new movie, series and anime. <span class="bg-gradient-to-r from-amber-300 to-amber-500 bg-clip-text text-transparent">One place.</span>
            </h1>
            <p class="mx-auto mt-6 max-w-xl text-base text-zinc-300 sm:text-lg">
                The latest releases, Nollywood and Ghallywood, anime as it airs, and a profile for everyone at home.
            </p>
            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ $ctaUrl }}" class="btn-primary px-8 py-3.5 text-base" wire:navigate>
                    {{ $ctaLabel }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </a>
                @guest
                    <a href="{{ route('home') }}" class="btn-secondary px-8 py-3.5 text-base" wire:navigate>Browse without an account</a>
                @endguest
            </div>
        </div>
    </section>

    {{-- Trending now --}}
    @include('partials.top-ten-row', ['title' => 'Trending this week', 'items' => $topTen, 'type' => 'movie'])

    {{-- Feature sections --}}
    <div class="mx-auto max-w-7xl space-y-24 px-4 py-24 sm:px-6 lg:px-8">
        <section class="reveal grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="eyebrow">Always current</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Fresh releases, the week they land</h2>
                <p class="mt-4 max-w-lg text-zinc-400">Popular, top rated and genre lists favour titles from the last two years, so you spend less time scrolling past old catalogue and more time watching.</p>
                <a href="{{ route('new-releases') }}" class="btn-secondary mt-6" wire:navigate>See new releases</a>
            </div>
            <div class="flex justify-center lg:justify-end" aria-hidden="true">
                <div class="relative h-72 w-full max-w-md overflow-hidden sm:h-80 sm:overflow-visible">
                    @foreach($fresh as $index => $poster)
                        <img src="{{ $poster['poster'] }}" alt="" loading="lazy"
                             class="absolute top-1/2 w-32 rounded-lg object-cover shadow-2xl shadow-black/70 ring-1 ring-white/10 sm:w-40"
                             style="left: {{ $index * 15 }}%; transform: translateY(-50%) rotate({{ ($index - 2) * 6 }}deg); z-index: {{ 10 - abs($index - 2) }};">
                    @endforeach
                </div>
            </div>
        </section>

        <section class="reveal grid items-center gap-10 lg:grid-cols-2">
            <div class="order-2 flex flex-wrap justify-center gap-5 lg:order-1 lg:justify-start" aria-hidden="true">
                @foreach(['You', 'Ama', 'Kofi', 'Kids', 'Nana'] as $index => $name)
                    <div class="flex flex-col items-center gap-2">
                        <span class="flex size-20 items-center justify-center rounded-md text-3xl font-bold text-white sm:size-24 {{ $avatarColors[$index % count($avatarColors)] }}">{{ mb_substr($name, 0, 1) }}</span>
                        <span class="text-sm text-zinc-400">{{ $name }}</span>
                    </div>
                @endforeach
            </div>
            <div class="order-1 lg:order-2">
                <p class="eyebrow">For the whole household</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Up to five profiles, one free account</h2>
                <p class="mt-4 max-w-lg text-zinc-400">Everyone gets their own watch history, My List, favourites and "Because you watched" recommendations, so nobody's picks get mixed up.</p>
            </div>
        </section>

        <section class="reveal grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="eyebrow">African cinema</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Nollywood and Ghallywood, front and centre</h2>
                <p class="mt-4 max-w-lg text-zinc-400">Dedicated homes for films and series from Nigeria and Ghana, sorted by what's new and popular right now.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('nollywood') }}" class="btn-secondary" wire:navigate>Explore Nollywood</a>
                    <a href="{{ route('ghallywood') }}" class="btn-secondary" wire:navigate>Explore Ghallywood</a>
                </div>
            </div>
            <div class="grid grid-cols-4 gap-3">
                @foreach($african as $poster)
                    <button type="button" @click="$dispatch('open-title', { type: '{{ $poster['type'] }}', id: {{ $poster['id'] }} })" class="overflow-hidden rounded-lg ring-1 ring-white/10 transition hover:-translate-y-1 hover:ring-amber-400/60">
                        <img src="{{ $poster['poster'] }}" alt="{{ $poster['title'] }}" loading="lazy" class="aspect-[2/3] w-full object-cover">
                    </button>
                @endforeach
            </div>
        </section>

        <section class="reveal grid items-center gap-10 lg:grid-cols-2">
            <div class="order-2 grid grid-cols-4 gap-3 lg:order-1">
                @foreach($anime as $poster)
                    <button type="button" @click="$dispatch('open-title', { type: '{{ $poster['type'] }}', id: {{ $poster['id'] }} })" class="overflow-hidden rounded-lg ring-1 ring-white/10 transition hover:-translate-y-1 hover:ring-fuchsia-400/60">
                        <img src="{{ $poster['poster'] }}" alt="{{ $poster['title'] }}" loading="lazy" class="aspect-[2/3] w-full object-cover">
                    </button>
                @endforeach
            </div>
            <div class="order-1 lg:order-2">
                <p class="eyebrow !text-fuchsia-400/90">Anime</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Anime as it airs</h2>
                <p class="mt-4 max-w-lg text-zinc-400">This season's series and the newest films, plus the full MyAnimeList catalogue when you search.</p>
                <a href="{{ route('anime.index') }}" class="btn-secondary mt-6" wire:navigate>Browse anime</a>
            </div>
        </section>

        <section class="reveal grid gap-4 sm:grid-cols-3">
            @foreach([
                ['title' => 'Pick up where you left off', 'text' => 'Continue Watching remembers your spot, even across devices.', 'icon' => 'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z'],
                ['title' => 'Trailers on hover', 'text' => 'Preview anything before you commit — no clicking through pages.', 'icon' => 'm15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z'],
                ['title' => 'Install it like an app', 'text' => 'Add StreamVault to your phone or desktop home screen in one tap.', 'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
            ] as $feature)
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-amber-400/10 text-amber-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}" /></svg>
                    </span>
                    <h3 class="mt-4 font-bold text-white">{{ $feature['title'] }}</h3>
                    <p class="mt-1 text-sm text-zinc-400">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- FAQ --}}
        <section class="reveal mx-auto max-w-3xl" x-data="{ open: 0 }">
            <h2 class="text-center text-3xl font-black tracking-tight text-white sm:text-4xl">Frequently asked questions</h2>
            <div class="mt-10 space-y-2">
                @foreach($faqs as $index => $faq)
                    <div class="overflow-hidden rounded-lg bg-zinc-900">
                        <h3>
                            <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}" :aria-expanded="open === {{ $index }}" aria-controls="faq-{{ $index }}"
                                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold text-white transition hover:bg-zinc-800 sm:text-lg">
                                {{ $faq['q'] }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6 shrink-0 transition" :class="open === {{ $index }} && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </button>
                        </h3>
                        <div id="faq-{{ $index }}" x-show="open === {{ $index }}" x-collapse x-cloak class="border-t border-zinc-950 px-5 py-4 text-zinc-300">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Closing call to action --}}
        <section class="reveal relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/15 via-zinc-900 to-zinc-950 px-6 py-14 text-center sm:px-12">
            <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">Ready when you are</h2>
            <p class="mx-auto mt-3 max-w-md text-zinc-300">Create your free account in seconds and set up a profile for everyone at home.</p>
            <a href="{{ $ctaUrl }}" class="btn-primary mt-8 px-8 py-3.5 text-base" wire:navigate>{{ $ctaLabel }}</a>
        </section>
    </div>
</div>
