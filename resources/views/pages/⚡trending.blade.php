<?php

use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Trending & Popular — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'trending';

    #[Url]
    public string $type = 'all';

    #[Url]
    public string $window = 'day';

    #[Url]
    public int $page = 1;

    public function updatedTab(): void
    {
        $this->page = 1;
    }

    public function updatedType(): void
    {
        $this->page = 1;
    }

    public function updatedWindow(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function with(Tmdb $tmdb): array
    {
        View::share('ogTitle', 'Trending & Popular — ' . config('app.name'));
        View::share('ogDescription', 'Discover what\'s trending and popular right now on ' . config('app.name') . '.');

        $results = match ($this->tab) {
            'popular' => $this->type === 'tv'
                ? $tmdb->popular('tv', $this->page)
                : $tmdb->popular('movie', $this->page),
            'top_rated' => $this->type === 'tv'
                ? $tmdb->topRated('tv', $this->page)
                : $tmdb->topRated('movie', $this->page),
            'now_playing' => $this->type === 'tv'
                ? $tmdb->airingToday($this->page)
                : $tmdb->nowPlaying($this->page),
            default => $tmdb->trending(
                $this->type === 'all' ? 'all' : $this->type,
                $this->window,
                $this->page,
            ),
        };

        $items = $results['results'] ?? [];
        $totalPages = min($results['total_pages'] ?? 1, 500);

        $movieGenres = $tmdb->genres('movie')['genres'] ?? [];
        $tvGenres = $tmdb->genres('tv')['genres'] ?? [];
        $genreMap = [];
        foreach (array_merge($movieGenres, $tvGenres) as $g) {
            $genreMap[$g['id']] = $g['name'];
        }

        return [
            'items' => $items,
            'totalPages' => $totalPages,
            'genreMap' => $genreMap,
        ];
    }
};
?>

<div>
    <x-page-header eyebrow="New & Popular" title="Trending" subtitle="What everyone is watching right now, plus the most popular and best-rated titles.">
        <div class="scrollbar-hide -mx-4 flex gap-2 overflow-x-auto px-4">
            @foreach(['trending' => 'Trending', 'popular' => 'Popular', 'top_rated' => 'Top Rated', 'now_playing' => 'In Cinemas'] as $key => $label)
                <x-tab-button :active="$tab === $key" wire:click="$set('tab', '{{ $key }}')">{{ $label }}</x-tab-button>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 rounded-full bg-white/[0.06] p-1" role="group" aria-label="Type">
                @if($tab === 'trending')
                    <button type="button" wire:click="$set('type', 'all')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $type === 'all' ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:text-white' }}">All</button>
                @endif
                <button type="button" wire:click="$set('type', 'movie')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $type === 'movie' ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:text-white' }}">Movies</button>
                <button type="button" wire:click="$set('type', 'tv')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $type === 'tv' ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:text-white' }}">TV Shows</button>
            </div>

            @if($tab === 'trending')
                <div class="flex items-center gap-1 rounded-full bg-white/[0.06] p-1" role="group" aria-label="Time window">
                    <button type="button" wire:click="$set('window', 'day')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $window === 'day' ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:text-white' }}">Today</button>
                    <button type="button" wire:click="$set('window', 'week')" class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition {{ $window === 'week' ? 'bg-white text-zinc-950' : 'text-zinc-300 hover:text-white' }}">This Week</button>
                </div>
            @endif
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        @if(empty($items))
            <p class="py-20 text-center text-zinc-500">No results found.</p>
        @else
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
                @foreach($items as $item)
                    @include('partials.media-card', ['item' => $item, 'type' => $item['media_type'] ?? ($tab === 'now_playing' && $type === 'tv' ? 'tv' : ($type !== 'all' ? $type : 'movie'))])
                @endforeach
            </div>
        @endif

        <x-pager :page="$page" :total-pages="$totalPages" previous="prevPage" />
    </div>
</div>
