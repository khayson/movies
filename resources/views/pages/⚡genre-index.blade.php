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

@php
    $genreColors = [
        'Action' => 'from-red-600/30 to-red-900/10 border-red-500/20 hover:border-red-500/40',
        'Adventure' => 'from-orange-600/30 to-orange-900/10 border-orange-500/20 hover:border-orange-500/40',
        'Animation' => 'from-pink-600/30 to-pink-900/10 border-pink-500/20 hover:border-pink-500/40',
        'Comedy' => 'from-yellow-600/30 to-yellow-900/10 border-yellow-500/20 hover:border-yellow-500/40',
        'Crime' => 'from-slate-600/30 to-slate-900/10 border-slate-500/20 hover:border-slate-500/40',
        'Documentary' => 'from-emerald-600/30 to-emerald-900/10 border-emerald-500/20 hover:border-emerald-500/40',
        'Drama' => 'from-blue-600/30 to-blue-900/10 border-blue-500/20 hover:border-blue-500/40',
        'Family' => 'from-lime-600/30 to-lime-900/10 border-lime-500/20 hover:border-lime-500/40',
        'Fantasy' => 'from-purple-600/30 to-purple-900/10 border-purple-500/20 hover:border-purple-500/40',
        'History' => 'from-amber-600/30 to-amber-900/10 border-amber-500/20 hover:border-amber-500/40',
        'Horror' => 'from-red-800/30 to-red-950/10 border-red-700/20 hover:border-red-700/40',
        'Music' => 'from-violet-600/30 to-violet-900/10 border-violet-500/20 hover:border-violet-500/40',
        'Mystery' => 'from-indigo-600/30 to-indigo-900/10 border-indigo-500/20 hover:border-indigo-500/40',
        'Romance' => 'from-rose-600/30 to-rose-900/10 border-rose-500/20 hover:border-rose-500/40',
        'Science Fiction' => 'from-cyan-600/30 to-cyan-900/10 border-cyan-500/20 hover:border-cyan-500/40',
        'Sci-Fi & Fantasy' => 'from-cyan-600/30 to-cyan-900/10 border-cyan-500/20 hover:border-cyan-500/40',
        'Thriller' => 'from-zinc-500/30 to-zinc-800/10 border-zinc-500/20 hover:border-zinc-500/40',
        'War' => 'from-stone-600/30 to-stone-900/10 border-stone-500/20 hover:border-stone-500/40',
        'War & Politics' => 'from-stone-600/30 to-stone-900/10 border-stone-500/20 hover:border-stone-500/40',
        'Western' => 'from-amber-700/30 to-amber-950/10 border-amber-600/20 hover:border-amber-600/40',
    ];
    $defaultColor = 'from-zinc-600/30 to-zinc-900/10 border-zinc-500/20 hover:border-zinc-500/40';
@endphp

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-amber-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-amber-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-400/80">Explore</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Genres</h1>
            <p class="mt-2 text-sm text-zinc-400">Browse movies and TV shows by genre</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Movie Genres --}}
        <section class="mb-12">
            <h2 class="mb-5 flex items-center gap-2 text-xl font-bold">
                <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                Movie Genres
            </h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($movieGenres as $genre)
                    <a href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                       class="group relative overflow-hidden rounded-2xl border bg-gradient-to-br p-6 text-center transition-all duration-300 hover:scale-[1.02] hover:shadow-lg {{ $genreColors[$genre['name']] ?? $defaultColor }}"
                       wire:navigate>
                        <span class="text-sm font-semibold text-zinc-200 transition group-hover:text-white">{{ $genre['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- TV Genres --}}
        <section>
            <h2 class="mb-5 flex items-center gap-2 text-xl font-bold">
                <span class="h-5 w-1 rounded-full bg-teal-500"></span>
                TV Show Genres
            </h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach($tvGenres as $genre)
                    <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                       class="group relative overflow-hidden rounded-2xl border bg-gradient-to-br p-6 text-center transition-all duration-300 hover:scale-[1.02] hover:shadow-lg {{ $genreColors[$genre['name']] ?? $defaultColor }}"
                       wire:navigate>
                        <span class="text-sm font-semibold text-zinc-200 transition group-hover:text-white">{{ $genre['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
