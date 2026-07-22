<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public string $type;

    public int $genreId;

    public string $genreName;

    #[Url]
    public int $page = 1;

    public function mount(string $type, int $genreId, string $genreName): void
    {
        $this->type = $type;
        $this->genreId = $genreId;
        $this->genreName = $genreName;
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
        $data = $tmdb->discoverByGenre($this->type, $this->genreId, $this->page);

        return [
            'items' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('genres.index') }}" class="text-zinc-500 transition hover:text-white" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $type === 'tv' ? 'TV Shows' : 'Movies' }}</p>
                <h1 class="text-3xl font-bold">{{ str_replace('-', ' ', Str::title($genreName)) }}</h1>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($items as $item)
                @include('partials.media-card', ['item' => $item, 'type' => $type, 'showOverview' => true])
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
