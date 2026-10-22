<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('New Releases — StreamVault')]
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
            'tv' => $tmdb->airingToday($this->page),
            default => $tmdb->nowPlaying($this->page),
        };

        return [
            'items' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    <x-page-header
        eyebrow="New & Popular"
        title="New Releases"
        subtitle="Films in cinemas right now and TV episodes airing today."
    >
        <div class="flex gap-2">
            @foreach(['movies' => 'In Cinemas', 'tv' => 'Airing Today'] as $key => $label)
                <x-tab-button :active="$tab === $key" wire:click="setTab('{{ $key }}')">{{ $label }}</x-tab-button>
            @endforeach
            <a href="{{ route('trending') }}" wire:navigate class="shrink-0 rounded-full bg-white/[0.06] px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-white/[0.12] hover:text-white">Trending</a>
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
            @foreach($items as $item)
                @include('partials.media-card', ['item' => $item, 'type' => $tab === 'tv' ? 'tv' : 'movie'])
            @endforeach
        </div>

        <x-pager :page="$page" :total-pages="$totalPages" />
    </div>
</div>
