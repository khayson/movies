<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Movies — StreamVault')]
class extends Component
{
    #[Url]
    public string $category = 'popular';

    #[Url]
    public int $page = 1;

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function with(Tmdb $tmdb): array
    {
        $data = match ($this->category) {
            'top_rated' => $tmdb->topRated('movie', $this->page),
            'trending' => $tmdb->trending('movie', 'week', $this->page),
            'now_playing' => $tmdb->nowPlaying($this->page),
            'upcoming' => $tmdb->upcoming($this->page),
            default => $tmdb->popular('movie', $this->page),
        };

        return [
            'movies' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-3xl font-bold">Movies</h1>

        <div class="scrollbar-hide mb-8 flex gap-2 overflow-x-auto pb-1">
            @foreach(['popular' => 'Popular', 'top_rated' => 'Top Rated', 'trending' => 'Trending', 'now_playing' => 'Now Playing', 'upcoming' => 'Upcoming'] as $key => $label)
                <button
                    wire:click="setCategory('{{ $key }}')"
                    class="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition {{ $category === $key ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($movies as $movie)
                @include('partials.media-card', ['item' => $movie, 'type' => 'movie', 'showOverview' => true])
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-center gap-4">
            @if($page > 1)
                <button wire:click="previousPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Previous</button>
            @endif
            <span class="text-sm text-zinc-500">Page {{ $page }} of {{ $totalPages }}</span>
            @if($page < $totalPages)
                <button wire:click="nextPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Next</button>
            @endif
        </div>
    </div>
</div>
