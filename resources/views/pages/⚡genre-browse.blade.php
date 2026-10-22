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
    <x-page-header
        :eyebrow="$type === 'tv' ? 'TV Shows' : 'Movies'"
        :title="str_replace('-', ' ', Str::title($genreName))"
        subtitle="The most popular titles in this genre from the last two years."
    >
        <a href="{{ route('genres.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.06] px-4 py-2 text-sm font-semibold text-zinc-300 transition hover:bg-white/[0.12] hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            All genres
        </a>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
            @foreach($items as $item)
                @include('partials.media-card', ['item' => $item, 'type' => $type])
            @endforeach
        </div>

        <x-pager :page="$page" :total-pages="$totalPages" />
    </div>
</div>
