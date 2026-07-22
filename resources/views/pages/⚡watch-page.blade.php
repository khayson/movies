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
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Upcoming notice --}}
        @if($isUpcoming)
            <div class="mb-4 rounded-lg border border-amber-600/40 bg-amber-600/10 px-4 py-3">
                <p class="flex items-center gap-2 text-sm text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <strong>Coming Soon</strong> — This title hasn't been released yet. Enjoy the trailer while you wait!
                </p>
            </div>
        @else
            {{-- External source disclaimer --}}
            <div class="mb-4 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-3">
                <p class="text-xs text-zinc-500">
                    <strong class="text-zinc-400">Disclaimer:</strong> {{ config('app.name') }} does not host or provide any video content. All streams are sourced from third-party external providers.
                </p>
            </div>
        @endif

        {{-- Server selector --}}
        @if(!$isUpcoming && $embedSources->count() > 1)
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="mr-1 text-xs font-medium uppercase tracking-wider text-zinc-500">Server:</span>
                @foreach($sources as $i => $s)
                    @if($s['type'] === 'embed')
                        <button
                            wire:click="selectServer({{ $i }})"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $activeServer === $i ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                        >
                            {{ $s['provider'] }}
                        </button>
                    @endif
                @endforeach
                @if($trailerSource)
                    @php $trailerIndex = collect($sources)->search(fn($s) => $s['type'] === 'youtube'); @endphp
                    <button
                        wire:click="selectServer({{ $trailerIndex }})"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $activeServer === $trailerIndex ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                    >
                        Trailer
                    </button>
                @endif
            </div>
        @endif

        {{-- Player --}}
        <div class="mb-6 aspect-video w-full overflow-hidden rounded-xl bg-zinc-900">
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
                <div class="flex h-full flex-col items-center justify-center gap-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-16 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                    </svg>
                    <p class="text-zinc-500">No sources available for this title.</p>
                    <p class="text-sm text-zinc-600">Try a different server or check back later.</p>
                </div>
            @endif
        </div>

        {{-- Title info --}}
        <div class="mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ $title }}
                        @if($type === 'tv')
                            <span class="text-lg text-zinc-400">S{{ $season }} E{{ $episode }}</span>
                        @endif
                    </h1>
                    @if(!empty($details['overview']))
                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-400">{{ $details['overview'] }}</p>
                    @endif
                </div>
                @php $detailRoute = $type === 'tv' ? 'tv.detail' : 'movies.detail'; @endphp
                <a href="{{ route($detailRoute, $tmdbId) }}" class="shrink-0 rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-700 hover:text-white" wire:navigate>
                    Details
                </a>
            </div>
        </div>

        {{-- Episode selector for TV --}}
        @if($type === 'tv' && !$isUpcoming && $seasonData && !empty($seasonData['episodes']))
            <section class="mt-6">
                <h2 class="mb-3 text-lg font-bold">Season {{ $season }} Episodes</h2>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($seasonData['episodes'] as $ep)
                        <button
                            wire:click="selectEpisode({{ $season }}, {{ $ep['episode_number'] }})"
                            class="rounded-lg p-2 text-left transition {{ $episode === $ep['episode_number'] ? 'bg-amber-600 text-white' : 'bg-zinc-900 text-zinc-400 hover:bg-zinc-800' }}"
                        >
                            <div class="text-xs font-semibold">E{{ $ep['episode_number'] }}</div>
                            <div class="mt-0.5 truncate text-xs">{{ $ep['name'] ?? '' }}</div>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
