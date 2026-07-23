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

    public int $progressSeconds = 0;

    public int $durationSeconds = 0;

    public function mount(string $type, int $tmdbId, int $season = 1, int $episode = 1): void
    {
        $this->type = $type;
        $this->tmdbId = $tmdbId;
        $this->season = $season;
        $this->episode = $episode;

        if (auth()->check()) {
            $this->restoreWatchState();
            $this->recordWatchHistory();
        } else {
            $resolver = app(SourceResolver::class);
            $this->activeServer = $resolver->recommendServer(
                $this->tmdbId,
                $this->type,
                $this->type === 'tv' ? $this->season : null,
                $this->type === 'tv' ? $this->episode : null,
            );
        }
    }

    private function restoreWatchState(): void
    {
        $resolver = app(SourceResolver::class);

        $history = auth()->user()->watchHistory()
            ->where('tmdb_id', $this->tmdbId)
            ->where('media_type', $this->type)
            ->first();

        if ($history) {
            $this->progressSeconds = $history->progress_seconds;
            $this->durationSeconds = $history->duration_seconds;
        }

        $recommended = $resolver->recommendServer(
            $this->tmdbId,
            $this->type,
            $this->type === 'tv' ? $this->season : null,
            $this->type === 'tv' ? $this->episode : null,
        );

        $this->activeServer = $recommended;
    }

    public function selectServer(int $index): void
    {
        $this->activeServer = $index;

        if (auth()->check()) {
            $resolver = app(SourceResolver::class);
            $sources = $resolver->resolve(
                $this->tmdbId,
                $this->type,
                $this->type === 'tv' ? $this->season : null,
                $this->type === 'tv' ? $this->episode : null,
            );

            $provider = $sources[$index]['provider'] ?? null;
            if ($provider) {
                auth()->user()->watchHistory()->updateOrCreate(
                    ['tmdb_id' => $this->tmdbId, 'media_type' => $this->type],
                    ['last_server' => $provider],
                );
            }
        }
    }

    public function reportServerError(int $index): void
    {
        $resolver = app(SourceResolver::class);
        $sources = $resolver->resolve(
            $this->tmdbId,
            $this->type,
            $this->type === 'tv' ? $this->season : null,
            $this->type === 'tv' ? $this->episode : null,
        );

        $provider = $sources[$index]['provider'] ?? null;
        if ($provider) {
            $resolver->reportFailure($provider);
        }

        $nextServer = $resolver->recommendServer(
            $this->tmdbId,
            $this->type,
            $this->type === 'tv' ? $this->season : null,
            $this->type === 'tv' ? $this->episode : null,
        );

        if ($nextServer !== $index) {
            $this->selectServer($nextServer);
        }
    }

    public function selectEpisode(int $season, int $episode): void
    {
        $this->season = $season;
        $this->episode = $episode;
        $this->progressSeconds = 0;
        $this->durationSeconds = 0;

        if (auth()->check()) {
            $this->restoreWatchState();
            $this->recordWatchHistory();
        } else {
            $resolver = app(SourceResolver::class);
            $this->activeServer = $resolver->recommendServer(
                $this->tmdbId,
                $this->type,
                $this->season,
                $this->episode,
            );
        }
    }

    public function saveProgress(int $progress, int $duration): void
    {
        if (! auth()->check() || $duration < 1) {
            return;
        }

        $this->progressSeconds = $progress;
        $this->durationSeconds = $duration;

        auth()->user()->watchHistory()->updateOrCreate(
            ['tmdb_id' => $this->tmdbId, 'media_type' => $this->type],
            [
                'progress_seconds' => $progress,
                'duration_seconds' => $duration,
            ],
        );
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
                'totalSeasons' => 1,
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

        $totalSeasons = $details['number_of_seasons'] ?? 1;

        return [
            'sources' => $sources,
            'details' => $details,
            'seasonData' => $seasonData,
            'isUpcoming' => false,
            'totalSeasons' => $totalSeasons,
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
        $posterPath = $details['poster_path'] ?? null;
        $year = Str::substr($details['release_date'] ?? $details['first_air_date'] ?? '', 0, 4);
        $rating = !empty($details['vote_average']) ? number_format($details['vote_average'], 1) : null;
        $runtime = $details['runtime'] ?? ($details['episode_run_time'][0] ?? null);
        $genres = collect($details['genres'] ?? [])->pluck('name')->take(3);
        $detailRoute = $type === 'tv' ? 'tv.detail' : 'movies.detail';
        $activeProviderName = $source['provider'] ?? 'Unknown';
    @endphp

    {{-- Cinematic backdrop --}}
    @if($backdropPath)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[600px] overflow-hidden">
            <img src="{{ app(\App\Services\Tmdb::class)->backdropUrl($backdropPath) }}" alt="" class="h-full w-full object-cover opacity-15 blur-xl">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/30 via-zinc-950/80 to-zinc-950"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/60 to-transparent"></div>
        </div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Upcoming notice --}}
        @if($isUpcoming)
            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-amber-500/20 bg-amber-500/[0.06] px-5 py-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-300">Coming Soon</p>
                    <p class="text-xs text-amber-300/70">This title hasn't been released yet. Enjoy the trailer while you wait!</p>
                </div>
            </div>
        @else
            <div class="mb-5 rounded-2xl border border-white/[0.04] bg-white/[0.015] px-4 py-2.5">
                <p class="text-[11px] text-zinc-500">
                    <strong class="font-medium text-zinc-400">Disclaimer:</strong> {{ config('app.name') }} does not host or provide any video content. All streams are sourced from third-party external providers.
                </p>
            </div>
        @endif

        <div class="flex flex-col gap-6 lg:flex-row">
            {{-- Main column --}}
            <div class="flex-1 min-w-0">
                {{-- Player container --}}
                <div x-data="watchPlayer()" x-init="init()">
                    <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-900/80 shadow-2xl shadow-black/50">
                        {{-- Player --}}
                        <div class="relative aspect-video w-full bg-black">
                            @if($source)
                                @if($source['type'] === 'embed')
                                    <iframe
                                        id="player-iframe"
                                        src="{{ $source['url'] }}"
                                        class="h-full w-full"
                                        frameborder="0"
                                        allowfullscreen
                                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                        referrerpolicy="origin"
                                    ></iframe>
                                @elseif($source['type'] === 'youtube')
                                    <iframe
                                        id="player-iframe"
                                        src="{{ $source['url'] }}?autoplay=1"
                                        class="h-full w-full"
                                        frameborder="0"
                                        allowfullscreen
                                        allow="autoplay; encrypted-media"
                                    ></iframe>
                                @endif
                            @else
                                <div class="flex h-full flex-col items-center justify-center gap-4">
                                    <div class="flex size-20 items-center justify-center rounded-2xl bg-white/[0.04]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-zinc-400">No sources available</p>
                                        <p class="mt-1 text-xs text-zinc-600">Try a different server or check back later.</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Progress bar --}}
                        @if($progressSeconds > 0 && $durationSeconds > 0)
                            <div class="h-1 w-full bg-white/[0.06]">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-300" style="width: {{ round(($progressSeconds / $durationSeconds) * 100) }}%"></div>
                            </div>
                        @endif
                    </div>

                    {{-- Control bar: Server dropdown + info (outside overflow-hidden) --}}
                    @if(!$isUpcoming && $embedSources->count() > 0)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3">
                            <div class="flex items-center gap-3">
                                {{-- Server dropdown --}}
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-3.5 py-2 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" /></svg>
                                        <span>{{ $activeProviderName }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-zinc-500 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute bottom-full left-0 z-50 mb-2 w-60 origin-bottom-left rounded-xl border border-white/[0.08] bg-zinc-900 p-1.5 shadow-2xl shadow-black/50" style="display: none;">
                                        <p class="mb-1 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Streaming Servers</p>
                                        @foreach($sources as $i => $s)
                                            @if($s['type'] === 'embed')
                                                <button
                                                    wire:click="selectServer({{ $i }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition {{ $activeServer === $i ? 'bg-amber-600/15 text-amber-300' : 'text-zinc-400 hover:bg-white/[0.05] hover:text-white' }}"
                                                >
                                                    @if($activeServer === $i)
                                                        <span class="flex size-5 items-center justify-center rounded-full bg-amber-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                                        </span>
                                                    @else
                                                        <span class="flex size-5 items-center justify-center rounded-full border border-white/[0.1]">
                                                            <span class="size-1.5 rounded-full bg-zinc-600"></span>
                                                        </span>
                                                    @endif
                                                    {{ $s['provider'] }}
                                                    @if($i === 0)
                                                        <span class="ml-auto rounded bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-amber-400">Best</span>
                                                    @endif
                                                </button>
                                            @endif
                                        @endforeach
                                        @if($trailerSource)
                                            <div class="my-1 border-t border-white/[0.06]"></div>
                                            @php $trailerIndex = collect($sources)->search(fn($s) => $s['type'] === 'youtube'); @endphp
                                            <button
                                                wire:click="selectServer({{ $trailerIndex }})"
                                                @click="open = false"
                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition {{ $activeServer === $trailerIndex ? 'bg-amber-600/15 text-amber-300' : 'text-zinc-400 hover:bg-white/[0.05] hover:text-white' }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814ZM9.545 15.568V8.432L15.818 12l-6.273 3.568Z"/></svg>
                                                Trailer
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Server status indicator --}}
                                <div class="hidden items-center gap-1.5 sm:flex">
                                    <span class="relative flex size-2">
                                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <span class="text-[11px] text-zinc-500">Auto-selected</span>
                                </div>
                            </div>

                            {{-- Progress info --}}
                            @if($progressSeconds > 0 && $durationSeconds > 0)
                                <div class="hidden items-center gap-1.5 text-xs text-zinc-500 sm:flex">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    <span>{{ gmdate('H:i:s', $progressSeconds) }} / {{ gmdate('H:i:s', $durationSeconds) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Title + metadata --}}
                <div class="mt-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            @if($posterPath)
                                <img src="{{ app(\App\Services\Tmdb::class)->imageUrl($posterPath, 'w185') }}" alt="{{ $title }}" class="hidden w-16 rounded-xl ring-1 ring-white/[0.08] sm:block" loading="lazy">
                            @endif
                            <div>
                                <h1 class="text-xl font-bold tracking-tight sm:text-2xl lg:text-3xl">
                                    {{ $title }}
                                    @if($type === 'tv')
                                        <span class="text-base font-normal text-zinc-500">S{{ $season }}:E{{ $episode }}</span>
                                    @endif
                                </h1>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                    @if($year)
                                        <span class="text-zinc-400">{{ $year }}</span>
                                    @endif
                                    @if($rating)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            {{ $rating }}
                                        </span>
                                    @endif
                                    @if($runtime)
                                        <span class="text-zinc-500">&bull; {{ $runtime }}m</span>
                                    @endif
                                    @if($genres->isNotEmpty())
                                        <span class="text-zinc-600">&bull;</span>
                                        @foreach($genres as $genre)
                                            <span class="rounded-md border border-white/[0.06] bg-white/[0.02] px-2 py-0.5 text-[11px] text-zinc-400">{{ $genre }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                @if(!empty($details['overview']))
                                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-500">{{ Str::limit($details['overview'], 220) }}</p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route($detailRoute, $tmdbId) }}" class="hidden shrink-0 items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white sm:inline-flex" wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                            Details
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Episodes panel for TV --}}
            @if($type === 'tv' && !$isUpcoming)
                <div class="w-full lg:w-80 xl:w-96">
                    <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.02]">
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-white/[0.04] bg-white/[0.02] px-4 py-3">
                            <h3 class="text-sm font-bold text-white">Episodes</h3>
                            @if($seasonData && !empty($seasonData['episodes']))
                                <span class="rounded-md bg-white/[0.06] px-2 py-0.5 text-[11px] tabular-nums text-zinc-400">{{ count($seasonData['episodes']) }} ep</span>
                            @endif
                        </div>

                        {{-- Season tabs --}}
                        @if(isset($totalSeasons) && $totalSeasons > 1)
                            <div class="scrollbar-hide flex gap-0 overflow-x-auto border-b border-white/[0.04]">
                                @for($s = 1; $s <= $totalSeasons; $s++)
                                    <button
                                        wire:click="selectEpisode({{ $s }}, 1)"
                                        class="relative shrink-0 px-4 py-2.5 text-xs font-semibold transition {{ $season === $s ? 'text-amber-400' : 'text-zinc-500 hover:text-zinc-300' }}"
                                    >
                                        S{{ $s }}
                                        @if($season === $s)
                                            <span class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-amber-500"></span>
                                        @endif
                                    </button>
                                @endfor
                            </div>
                        @endif

                        {{-- Episode list --}}
                        <div class="scrollbar-hide max-h-[65vh] overflow-y-auto">
                            @if($seasonData && !empty($seasonData['episodes']))
                                @foreach($seasonData['episodes'] as $ep)
                                    @php
                                        $isActive = $episode === $ep['episode_number'];
                                        $epStill = $ep['still_path'] ?? null;
                                    @endphp
                                    <button
                                        wire:click="selectEpisode({{ $season }}, {{ $ep['episode_number'] }})"
                                        class="group flex w-full items-start gap-3 border-b border-white/[0.03] p-3 text-left transition last:border-b-0 {{ $isActive ? 'bg-amber-600/[0.08]' : 'hover:bg-white/[0.03]' }}"
                                    >
                                        {{-- Episode thumbnail --}}
                                        <div class="relative w-24 shrink-0 overflow-hidden rounded-lg bg-zinc-800 {{ $isActive ? 'ring-1 ring-amber-500/40' : '' }}">
                                            <div class="aspect-video">
                                                @if($epStill)
                                                    <img src="{{ app(\App\Services\Tmdb::class)->imageUrl($epStill, 'w300') }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <div class="flex h-full items-center justify-center">
                                                        <span class="text-lg font-bold text-zinc-700">{{ $ep['episode_number'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            @if($isActive)
                                                <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                                                    <div class="flex size-7 items-center justify-center rounded-full bg-amber-600 shadow-lg shadow-amber-600/30">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3 translate-x-px text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Episode info --}}
                                        <div class="min-w-0 flex-1 py-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-bold {{ $isActive ? 'text-amber-400' : 'text-zinc-500' }}">E{{ $ep['episode_number'] }}</span>
                                                @if(!empty($ep['runtime']))
                                                    <span class="text-[11px] text-zinc-600">{{ $ep['runtime'] }}m</span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 truncate text-sm font-medium {{ $isActive ? 'text-amber-300' : 'text-zinc-300 group-hover:text-white' }}">
                                                {{ $ep['name'] ?? 'Episode '.$ep['episode_number'] }}
                                            </p>
                                            @if(!empty($ep['overview']))
                                                <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-zinc-600">{{ $ep['overview'] }}</p>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach
                            @else
                                <div class="px-4 py-10 text-center">
                                    <p class="text-sm text-zinc-600">No episode data available.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Player tracking: postMessage + localStorage fallback --}}
    <script>
        function watchPlayer() {
            return {
                saveTimer: null,
                heartbeatTimer: null,
                startTime: 0,
                watchKey: 'sv_watch_{{ $type }}_{{ $tmdbId }}',
                lastProgress: 0,
                lastDuration: 0,
                postMessageActive: false,
                init() {
                    this.startTime = Date.now();
                    this.restoreFromLocal();

                    window.addEventListener('message', (event) => {
                        if (event.origin !== 'https://cinesrc.st') return;
                        const data = event.data;
                        if (!data || !data.type) return;

                        switch (data.type) {
                            case 'cinesrc:timeupdate':
                                this.postMessageActive = true;
                                this.lastProgress = Math.floor(data.time || 0);
                                this.lastDuration = Math.floor(data.duration || 0);
                                this.saveToLocal();
                                this.debounceSave();
                                break;
                            case 'cinesrc:error':
                                @this.call('reportServerError', {{ $activeServer }});
                                break;
                        }
                    });

                    this.heartbeatTimer = setInterval(() => {
                        if (this.postMessageActive) return;
                        const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                        if (elapsed > 5) {
                            this.lastProgress = elapsed;
                            this.lastDuration = Math.max(this.lastDuration, elapsed + 300);
                            this.saveToLocal();
                            this.debounceSave();
                        }
                    }, 10000);

                    window.addEventListener('beforeunload', () => {
                        this.saveToLocal();
                        this.saveNow();
                    });

                    document.addEventListener('livewire:navigating', () => {
                        this.saveToLocal();
                        this.saveNow();
                    });
                },
                restoreFromLocal() {
                    try {
                        const saved = JSON.parse(localStorage.getItem(this.watchKey) || 'null');
                        if (saved) {
                            this.lastProgress = saved.progress || 0;
                            this.lastDuration = saved.duration || 0;
                        }
                    } catch {}
                },
                saveToLocal() {
                    try {
                        localStorage.setItem(this.watchKey, JSON.stringify({
                            progress: this.lastProgress,
                            duration: this.lastDuration,
                            server: {{ $activeServer }},
                            ts: Date.now()
                        }));
                    } catch {}
                },
                saveNow() {
                    if (this.saveTimer) clearTimeout(this.saveTimer);
                    if (this.lastProgress > 0 && this.lastDuration > 0) {
                        @this.call('saveProgress', this.lastProgress, this.lastDuration);
                    }
                },
                debounceSave() {
                    if (this.saveTimer) clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(() => {
                        this.saveNow();
                    }, 5000);
                },
                destroy() {
                    if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
                }
            };
        }
    </script>
</div>
