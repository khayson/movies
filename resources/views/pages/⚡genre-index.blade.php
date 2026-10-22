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

<div x-data="{ kind: 'movie' }">
    <x-page-header eyebrow="Browse" title="Genres" subtitle="Find something new in the genre you're in the mood for.">
        <div class="flex gap-2">
            <button type="button" @click="kind = 'movie'" :aria-pressed="kind === 'movie'" :class="kind === 'movie' ? 'bg-white text-zinc-950' : 'bg-white/[0.06] text-zinc-300 hover:bg-white/[0.12] hover:text-white'" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition">Movies</button>
            <button type="button" @click="kind = 'tv'" :aria-pressed="kind === 'tv'" :class="kind === 'tv' ? 'bg-white text-zinc-950' : 'bg-white/[0.06] text-zinc-300 hover:bg-white/[0.12] hover:text-white'" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition">TV Shows</button>
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        <section x-show="kind === 'movie'" aria-label="Movie genres">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($movieGenres as $genre)
                        <a href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}" wire:navigate
                           class="group relative overflow-hidden rounded-xl border border-white/[0.06] bg-gradient-to-br from-zinc-800/80 to-zinc-900 px-4 py-6 transition hover:-translate-y-0.5 hover:border-amber-400/40">
                            <span class="relative font-bold text-white">{{ $genre['name'] }}</span>
                            <span class="absolute -bottom-6 -right-4 text-7xl font-black text-white/[0.04] transition group-hover:text-amber-400/10" aria-hidden="true">{{ mb_substr($genre['name'], 0, 1) }}</span>
                        </a>
                @endforeach
            </div>
        </section>

        <section x-show="kind === 'tv'" x-cloak aria-label="TV show genres">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($tvGenres as $genre)
                        <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}" wire:navigate
                           class="group relative overflow-hidden rounded-xl border border-white/[0.06] bg-gradient-to-br from-zinc-800/80 to-zinc-900 px-4 py-6 transition hover:-translate-y-0.5 hover:border-amber-400/40">
                            <span class="relative font-bold text-white">{{ $genre['name'] }}</span>
                            <span class="absolute -bottom-6 -right-4 text-7xl font-black text-white/[0.04] transition group-hover:text-amber-400/10" aria-hidden="true">{{ mb_substr($genre['name'], 0, 1) }}</span>
                        </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
