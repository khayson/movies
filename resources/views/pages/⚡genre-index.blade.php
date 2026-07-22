<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Genres — StreamVault')]
class extends Component
{
    public function with(Tmdb $tmdb): array
    {
        return [
            'movieGenres' => $tmdb->genres('movie')['genres'] ?? [],
            'tvGenres' => $tmdb->genres('tv')['genres'] ?? [],
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-2 text-3xl font-bold">Genres</h1>
        <p class="mb-8 text-sm text-zinc-400">Browse movies and TV shows by genre</p>

        <section class="mb-12">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                Movie Genres
            </h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($movieGenres as $genre)
                    <a href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                       class="group relative overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-6 text-center transition hover:border-amber-600/50 hover:bg-zinc-800"
                       wire:navigate>
                        <span class="text-sm font-semibold text-zinc-300 transition group-hover:text-amber-400">{{ $genre['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                <span class="h-5 w-1 rounded-full bg-teal-500"></span>
                TV Show Genres
            </h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($tvGenres as $genre)
                    <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                       class="group relative overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-6 text-center transition hover:border-teal-600/50 hover:bg-zinc-800"
                       wire:navigate>
                        <span class="text-sm font-semibold text-zinc-300 transition group-hover:text-teal-400">{{ $genre['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
