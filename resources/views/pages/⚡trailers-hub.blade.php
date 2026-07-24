<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Trailers — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'upcoming';

    public string $activeTrailer = '';

    public function playTrailer(string $youtubeKey): void
    {
        $this->activeTrailer = $youtubeKey;
    }

    public function closeTrailer(): void
    {
        $this->activeTrailer = '';
    }

    public function with(Tmdb $tmdb): array
    {
        $movies = match ($this->tab) {
            'now_playing' => $tmdb->nowPlaying()['results'] ?? [],
            'popular' => $tmdb->popular('movie')['results'] ?? [],
            default => $tmdb->upcoming()['results'] ?? [],
        };

        $trailersWithMovies = [];
        foreach (array_slice($movies, 0, 12) as $movie) {
            try {
                $details = $tmdb->details('movie', $movie['id']);
                $trailer = collect($details['videos']['results'] ?? [])->first(function ($v) {
                    return $v['site'] === 'YouTube' && in_array($v['type'], ['Trailer', 'Teaser']);
                });
                if ($trailer) {
                    $trailersWithMovies[] = [
                        'movie' => $movie,
                        'trailer' => $trailer,
                        'release_date' => $movie['release_date'] ?? '',
                    ];
                }
            } catch (\Throwable) {
            }
        }

        return [
            'trailersWithMovies' => $trailersWithMovies,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-red-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-red-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-400/80">Watch</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Trailers</h1>
            <p class="mt-2 text-sm text-zinc-400">Watch the latest movie trailers and teasers</p>

            <div class="mt-8 flex gap-2">
                @foreach(['upcoming' => 'Upcoming', 'now_playing' => 'Now Playing', 'popular' => 'Popular'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                            class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === $key ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Trailer Player Modal --}}
        @if($activeTrailer)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm" wire:click.self="closeTrailer">
                <div class="w-full max-w-4xl">
                    <button wire:click="closeTrailer" class="mb-4 ml-auto flex items-center gap-1.5 rounded-xl border border-white/[0.08] bg-white/[0.05] px-4 py-2 text-sm text-zinc-300 transition hover:border-white/[0.15] hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        Close
                    </button>
                    <div class="aspect-video overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-white/[0.06]">
                        <iframe
                            src="https://www.youtube.com/embed/{{ $activeTrailer }}?autoplay=1"
                            class="h-full w-full"
                            frameborder="0"
                            allowfullscreen
                            allow="autoplay; encrypted-media"
                        ></iframe>
                    </div>
                </div>
            </div>
        @endif

        {{-- Trailers Grid --}}
        @if(count($trailersWithMovies) > 0)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($trailersWithMovies as $item)
                    <div class="group overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.02] transition hover:border-white/[0.12]">
                        <button wire:click="playTrailer('{{ $item['trailer']['key'] }}')" class="relative block w-full">
                            <div class="aspect-video bg-zinc-800">
                                <img src="https://img.youtube.com/vi/{{ $item['trailer']['key'] }}/hqdefault.jpg" alt=""
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover:bg-black/40">
                                <div class="flex size-14 items-center justify-center rounded-full bg-red-600/90 text-white shadow-lg shadow-red-600/30 transition group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </button>
                        <div class="p-4">
                            <a href="{{ route('movies.detail', $item['movie']['id']) }}" class="font-medium text-zinc-200 transition hover:text-red-400" wire:navigate>
                                {{ $item['movie']['title'] ?? 'Untitled' }}
                            </a>
                            <div class="mt-1.5 flex items-center gap-2 text-xs text-zinc-500">
                                @if($item['release_date'])
                                    @php $releaseDate = \Carbon\Carbon::parse($item['release_date']); @endphp
                                    @if($releaseDate->isFuture())
                                        <span class="rounded-lg bg-amber-500/10 px-2 py-0.5 text-amber-400">{{ $releaseDate->diffForHumans() }}</span>
                                    @else
                                        <span>{{ $releaseDate->format('M d, Y') }}</span>
                                    @endif
                                @endif
                                <span class="rounded-lg bg-white/[0.04] px-2 py-0.5">{{ $item['trailer']['type'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                <p class="text-zinc-500">No trailers found for this category.</p>
            </div>
        @endif
    </div>
</div>
