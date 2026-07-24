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
    {{-- Cinematic Header --}}
    <div class="relative overflow-hidden">
        @if($heroShow && !empty($heroShow['backdrop_path']))
            <div class="absolute inset-0">
                <img src="{{ app(Tmdb::class)->backdropUrl($heroShow['backdrop_path'], 'w1280') }}" alt="" class="h-full w-full object-cover opacity-20">
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/50 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-3">
                        <span class="h-6 w-1 rounded-full bg-teal-500"></span>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-400/80">Browse</p>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight md:text-5xl">TV Shows</h1>
                    <p class="mt-2 text-sm text-zinc-400">Discover series, dramas, and binge-worthy shows</p>
                </div>
            </div>

            {{-- Category Tabs --}}
            <div class="scrollbar-hide mt-8 flex gap-2 overflow-x-auto pb-1">
                @foreach(['popular' => 'Popular', 'top_rated' => 'Top Rated', 'trending' => 'Trending', 'airing_today' => 'Airing Today', 'on_the_air' => 'On The Air'] as $key => $label)
                    <button
                        wire:click="setCategory('{{ $key }}')"
                        class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $category === $key ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-zinc-500">
                Page <span class="font-medium text-zinc-300">{{ $page }}</span> of <span class="font-medium text-zinc-300">{{ $totalPages }}</span>
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($shows as $show)
                @include('partials.media-card', ['item' => $show, 'type' => 'tv'])
            @endforeach
        </div>

        <div class="mt-10 flex items-center justify-center gap-3">
            @if($page > 1)
                <button wire:click="previousPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    Previous
                </button>
            @endif
            <span class="rounded-xl bg-white/[0.04] px-5 py-2.5 text-sm tabular-nums text-zinc-500">{{ $page }} / {{ $totalPages }}</span>
            @if($page < $totalPages)
                <button wire:click="nextPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            @endif
        </div>
    </div>
</div>
