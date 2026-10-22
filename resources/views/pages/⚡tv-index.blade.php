<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('TV Shows — StreamVault')]
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
            'top_rated' => $tmdb->topRated('tv', $this->page),
            'trending' => $tmdb->trending('tv', 'week', $this->page),
            'airing_today' => $tmdb->airingToday($this->page),
            'on_the_air' => $tmdb->onTheAir($this->page),
            default => $tmdb->popular('tv', $this->page),
        };

        $heroShow = null;
        $results = $data['results'] ?? [];
        if ($this->page === 1 && count($results) > 0) {
            $heroShow = $results[0];
        }

        return [
            'shows' => $results,
            'totalPages' => min($data['total_pages'] ?? 1, 500),
            'heroShow' => $heroShow,
        ];
    }
};
?>

<div>
    <x-page-header
        eyebrow="Browse"
        title="TV Shows"
        subtitle="New series, the best-rated shows of the last two years, and what's airing this week."
        :backdrop="$heroShow && ! empty($heroShow['backdrop_path']) ? app(Tmdb::class)->backdropUrl($heroShow['backdrop_path'], 'w1280') : null"
    >
        <div class="scrollbar-hide -mx-4 flex gap-2 overflow-x-auto px-4">
            @foreach(['popular' => 'Popular', 'top_rated' => 'Top Rated', 'trending' => 'Trending', 'airing_today' => 'Airing Today', 'on_the_air' => 'On The Air'] as $key => $label)
                <x-tab-button :active="$category === $key" wire:click="setCategory('{{ $key }}')">{{ $label }}</x-tab-button>
            @endforeach
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
            @foreach($shows as $show)
                @include('partials.media-card', ['item' => $show, 'type' => 'tv'])
            @endforeach
        </div>

        <x-pager :page="$page" :total-pages="$totalPages" />
    </div>
</div>
