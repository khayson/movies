<?php

use App\Services\SourceResolver;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public string $type;

    public int $tmdbId;

    public int $season = 1;

    public int $episode = 1;

    public int $activeServer = 0;

    public function mount(string $type, int $tmdbId, int $season = 1, int $episode = 1): void
    {
        $this->type = $type;
        $this->tmdbId = $tmdbId;
        $this->season = $season;
        $this->episode = $episode;

        if (auth()->check()) {
            $this->recordWatchHistory();
            $this->applyDefaultSource();
        }
    }

    private function applyDefaultSource(): void
    {
        $prefs = auth()->user()->preferences ?? [];
        $defaultSource = $prefs['default_source'] ?? '';

        if ($defaultSource === '') {
            return;
        }

        $resolver = app(SourceResolver::class);
        $sources = $resolver->resolve(
            $this->tmdbId,
            $this->type,
            $this->type === 'tv' ? $this->season : null,
            $this->type === 'tv' ? $this->episode : null,
        );

        foreach ($sources as $i => $source) {
            if (($source['provider'] ?? '') === $defaultSource) {
                $this->activeServer = $i;
                break;
            }
        }
    }

    public function selectServer(int $index): void
    {
        $this->activeServer = $index;
    }

    public function selectEpisode(int $season, int $episode): void
    {
        $this->season = $season;
        $this->episode = $episode;
        $this->activeServer = 0;

        if (auth()->check()) {
            $this->recordWatchHistory();
        }
    }

    private function recordWatchHistory(): void
    {
        $tmdb = app(Tmdb::class);

        try {
            $details = $tmdb->details($this->type, $this->tmdbId);
            $title = $details['title'] ?? $details['name'] ?? 'Untitled';
            $posterPath = $details['poster_path'] ?? null;
        } catch (\Throwable) {
            $title = 'Untitled';
            $posterPath = null;
        }

        auth()->user()->watchHistory()->updateOrCreate(
            [
                'tmdb_id' => $this->tmdbId,
                'media_type' => $this->type,
            ],
            [
                'title' => $title,
                'poster_path' => $posterPath,
                'season' => $this->type === 'tv' ? $this->season : null,
                'episode' => $this->type === 'tv' ? $this->episode : null,
            ]
        );
    }

    public function with(SourceResolver $resolver, Tmdb $tmdb): array
    {
        $details = [];

        try {
            $details = $tmdb->details($this->type, $this->tmdbId);
        } catch (\Throwable) {
        }

        $releaseDate = $details['release_date'] ?? $details['first_air_date'] ?? '';
        $isUpcoming = $releaseDate && $releaseDate > now()->toDateString();

        if ($isUpcoming) {
            $trailer = collect($details['videos']['results'] ?? [])->first(function ($v) {
                return $v['site'] === 'YouTube' && in_array($v['type'], ['Trailer', 'Teaser']);
            });

            return [
                'sources' => $trailer ? [[
                    'type' => 'youtube',
                    'url' => "https://www.youtube.com/embed/{$trailer['key']}",
                    'quality' => 'auto',
                    'provider' => 'YouTube Trailer',
                ]] : [],
                'details' => $details,
                'seasonData' => null,
                'isUpcoming' => true,
            ];
        }

        $sources = $resolver->resolve(
            $this->tmdbId,
            $this->type,
            $this->type === 'tv' ? $this->season : null,
            $this->type === 'tv' ? $this->episode : null,
        );

        $seasonData = null;
        if ($this->type === 'tv') {
            try {
                $seasonData = $tmdb->season($this->tmdbId, $this->season);
            } catch (\Throwable) {
            }
        }

        return [
            'sources' => $sources,
            'details' => $details,
            'seasonData' => $seasonData,
            'isUpcoming' => false,
        ];
    }
};
?>

<div>
    @php
        $title = $details['title'] ?? $details['name'] ?? 'Untitled';
        $source = $sources[$activeServer] ?? $sources[0] ?? null;
        $embedSources = collect($sources)->where('type', 'embed')->values();
        $trailerSource = collect($sources)->firstWhere('type', 'youtube');
        $backdropPath = $details['backdrop_path'] ?? null;
    @endphp

    {{-- Cinematic backdrop --}}
    @if($backdropPath)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[500px] overflow-hidden opacity-20">
            <img src="{{ app(\App\Services\Tmdb::class)->backdropUrl($backdropPath) }}" alt="" class="h-full w-full object-cover blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/50 to-zinc-950"></div>
        </div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Upcoming notice --}}
        @if($isUpcoming)
            <div class="mb-4 flex items-center gap-3 rounded-xl border border-amber-500/20 bg-amber-500/[0.07] px-5 py-3.5">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <p class="text-sm text-amber-300/90">
                    <strong class="font-semibold text-amber-300">Coming Soon</strong> — This title hasn't been released yet. Enjoy the trailer while you wait!
                </p>
            </div>
        @else
            <div class="mb-4 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                <p class="text-xs text-zinc-500">
                    <strong class="font-medium text-zinc-400">Disclaimer:</strong> {{ config('app.name') }} does not host or provide any video content. All streams are sourced from third-party external providers.
                </p>
            </div>
        @endif

        {{-- Player container --}}
        <div class="mb-6 overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-900/80 shadow-2xl shadow-black/40">
            @if(!$isUpcoming && $embedSources->count() > 1)
                <div class="flex flex-wrap items-center gap-2 border-b border-white/[0.06] bg-white/[0.02] px-4 py-2.5">
                    <span class="mr-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-500">Server</span>
                    @foreach($sources as $i => $s)
                        @if($s['type'] === 'embed')
                            <button
                                wire:click="selectServer({{ $i }})"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $activeServer === $i ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'bg-white/[0.05] text-zinc-400 hover:bg-white/[0.1] hover:text-white' }}"
                            >
                                {{ $s['provider'] }}
                            </button>
                        @endif
                    @endforeach
                    @if($trailerSource)
                        @php $trailerIndex = collect($sources)->search(fn($s) => $s['type'] === 'youtube'); @endphp
                        <button
                            wire:click="selectServer({{ $trailerIndex }})"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $activeServer === $trailerIndex ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'bg-white/[0.05] text-zinc-400 hover:bg-white/[0.1] hover:text-white' }}"
                        >
                            Trailer
                        </button>
                    @endif
                </div>
            @endif

            <div class="aspect-video w-full">
                @if($source)
                    @if($source['type'] === 'embed')
                        <iframe
                            src="{{ $source['url'] }}"
                            class="h-full w-full"
                            frameborder="0"
                            allowfullscreen
                            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                            referrerpolicy="origin"
                        ></iframe>
                    @elseif($source['type'] === 'youtube')
                        <iframe
                            src="{{ $source['url'] }}?autoplay=1"
                            class="h-full w-full"
                            frameborder="0"
                            allowfullscreen
                            allow="autoplay; encrypted-media"
                        ></iframe>
                    @endif
                @else
                    <div class="flex h-full flex-col items-center justify-center gap-3 bg-zinc-900">
                        <div class="flex size-16 items-center justify-center rounded-2xl bg-white/[0.04]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-zinc-400">No sources available</p>
                        <p class="text-xs text-zinc-600">Try a different server or check back later.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Title info --}}
        <div class="mb-8">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-5">
                    @if(!empty($details['poster_path']))
                        <img src="{{ app(\App\Services\Tmdb::class)->imageUrl($details['poster_path'], 'w185') }}" alt="{{ $title }}" class="hidden w-20 rounded-lg shadow-lg sm:block" loading="lazy">
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold sm:text-3xl">
                            {{ $title }}
                            @if($type === 'tv')
                                <span class="text-lg font-normal text-zinc-400">S{{ $season }} E{{ $episode }}</span>
                            @endif
                        </h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
                            @if(!empty($details['release_date'] ?? $details['first_air_date'] ?? ''))
                                <span>{{ Str::substr($details['release_date'] ?? $details['first_air_date'] ?? '', 0, 4) }}</span>
                            @endif
                            @if(!empty($details['vote_average']))
                                <span class="flex items-center gap-1 text-amber-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ number_format($details['vote_average'], 1) }}
                                </span>
                            @endif
                            @if(!empty($details['genres']))
                                <span class="text-zinc-600">{{ collect($details['genres'])->pluck('name')->take(3)->implode(' / ') }}</span>
                            @endif
                        </div>
                        @if(!empty($details['overview']))
                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-400">{{ Str::limit($details['overview'], 250) }}</p>
                        @endif
                    </div>
                </div>
                @php $detailRoute = $type === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                <a href="{{ route($detailRoute, $tmdbId) }}" class="hidden shrink-0 items-center gap-2 rounded-lg border border-white/[0.08] bg-white/[0.03] px-4 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white sm:inline-flex" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    Details
                </a>
            </div>
        </div>

        {{-- Episode selector for TV --}}
        @if($type === 'tv' && !$isUpcoming && $seasonData && !empty($seasonData['episodes']))
            <section class="mt-2 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5">
                <h2 class="mb-4 text-base font-bold">Season {{ $season }} Episodes</h2>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($seasonData['episodes'] as $ep)
                        <button
                            wire:click="selectEpisode({{ $season }}, {{ $ep['episode_number'] }})"
                            class="rounded-xl p-3 text-left transition {{ $episode === $ep['episode_number'] ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}"
                        >
                            <div class="text-xs font-bold">E{{ $ep['episode_number'] }}</div>
                            <div class="mt-0.5 truncate text-[11px] {{ $episode === $ep['episode_number'] ? 'text-white/80' : 'text-zinc-500' }}">{{ $ep['name'] ?? '' }}</div>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
