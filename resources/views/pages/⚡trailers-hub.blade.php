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
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-bold">Trailers</h1>

        {{-- Tabs --}}
        <div class="mb-8 flex gap-2">
            <button wire:click="$set('tab', 'upcoming')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'upcoming' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Upcoming
            </button>
            <button wire:click="$set('tab', 'now_playing')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'now_playing' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Now Playing
            </button>
            <button wire:click="$set('tab', 'popular')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'popular' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Popular
            </button>
        </div>

        {{-- Trailer Player Modal --}}
        @if($activeTrailer)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" wire:click.self="closeTrailer">
                <div class="w-full max-w-4xl">
                    <button wire:click="closeTrailer" class="mb-4 ml-auto flex items-center gap-1 text-sm text-zinc-400 transition hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        Close
                    </button>
                    <div class="aspect-video overflow-hidden rounded-xl bg-zinc-900">
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
                    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/50">
                        <button wire:click="playTrailer('{{ $item['trailer']['key'] }}')" class="group relative block w-full">
                            <div class="aspect-video bg-zinc-800">
                                <img src="https://img.youtube.com/vi/{{ $item['trailer']['key'] }}/hqdefault.jpg" alt=""
                                     class="h-full w-full object-cover" loading="lazy">
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover:bg-black/50">
                                <div class="flex size-14 items-center justify-center rounded-full bg-amber-600/90 text-white shadow-lg transition group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </button>
                        <div class="p-4">
                            <a href="{{ route('movies.detail', $item['movie']['id']) }}" class="font-medium text-zinc-200 transition hover:text-amber-400" wire:navigate>
                                {{ $item['movie']['title'] ?? 'Untitled' }}
                            </a>
                            <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500">
                                @if($item['release_date'])
                                    @php $releaseDate = \Carbon\Carbon::parse($item['release_date']); @endphp
                                    @if($releaseDate->isFuture())
                                        <span class="rounded bg-amber-600/10 px-2 py-0.5 text-amber-400">{{ $releaseDate->diffForHumans() }}</span>
                                    @else
                                        <span>{{ $releaseDate->format('M d, Y') }}</span>
                                    @endif
                                @endif
                                <span>{{ $item['trailer']['type'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-zinc-500">No trailers found for this category.</p>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
