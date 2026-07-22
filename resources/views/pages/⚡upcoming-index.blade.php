<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Upcoming — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'movies';

    #[Url]
    public int $page = 1;

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
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
        $data = match ($this->tab) {
            'tv' => $tmdb->onTheAir($this->page),
            default => $tmdb->upcoming($this->page),
        };

        return [
            'items' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-2 flex items-center gap-3">
            <h1 class="text-3xl font-bold">Upcoming</h1>
            <span class="rounded-full bg-amber-600/20 px-3 py-1 text-xs font-semibold text-amber-400">Coming Soon</span>
        </div>
        <p class="mb-8 text-sm text-zinc-400">Movies and TV shows that haven't been released yet. Watch trailers while you wait.</p>

        <div class="mb-8 flex gap-2">
            @foreach(['movies' => 'Upcoming Movies', 'tv' => 'Upcoming TV'] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === $key ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($items as $item)
                @include('partials.media-card', [
                    'item' => $item,
                    'type' => $tab === 'tv' ? 'tv' : 'movie',
                    'showOverview' => true,
                ])
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
