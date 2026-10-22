<?php

use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    /**
     * @var array<string, array{name: string, country: string, countryName: string, tagline: string}>
     */
    public const INDUSTRIES = [
        'nollywood' => [
            'name' => 'Nollywood',
            'country' => 'NG',
            'countryName' => 'Nigeria',
            'tagline' => 'Popular movies and series from Nigeria’s film industry',
        ],
        'ghallywood' => [
            'name' => 'Ghallywood',
            'country' => 'GH',
            'countryName' => 'Ghana',
            'tagline' => 'Popular movies and series from Ghana’s film industry',
        ],
    ];

    public string $industry;

    #[Url]
    public string $tab = 'movie';

    #[Url]
    public int $page = 1;

    public function mount(string $industry): void
    {
        abort_unless(array_key_exists($industry, self::INDUSTRIES), 404);

        $this->industry = $industry;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'tv' ? 'tv' : 'movie';
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
        $industry = self::INDUSTRIES[$this->industry];
        $type = $this->tab === 'tv' ? 'tv' : 'movie';
        $data = $tmdb->discoverByOriginCountry($type, $industry['country'], $this->page);

        View::share('title', $industry['name']);
        View::share('ogTitle', $industry['name'].' — '.config('app.name'));
        View::share('ogDescription', $industry['tagline'].' on '.config('app.name').'.');

        return [
            'details' => $industry,
            'type' => $type,
            'items' => $data['results'] ?? [],
            'totalPages' => max(1, min($data['total_pages'] ?? 1, 500)),
        ];
    }
};
?>

<div>
    <x-page-header :eyebrow="'African Cinema · '.$details['countryName']" :title="$details['name']" :subtitle="$details['tagline']">
        <div class="flex gap-2">
            @foreach(['movie' => 'Movies', 'tv' => 'TV Shows'] as $key => $label)
                <x-tab-button :active="$type === $key" wire:click="setTab('{{ $key }}')">{{ $label }}</x-tab-button>
            @endforeach
        </div>
    </x-page-header>

    <div class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        @if($items === [])
            <p class="py-16 text-center text-sm text-zinc-500">No {{ $type === 'tv' ? 'TV shows' : 'movies' }} found for {{ $details['name'] }} right now.</p>
        @else
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" wire:loading.class="opacity-50">
                @foreach($items as $item)
                    @include('partials.media-card', ['item' => $item, 'type' => $type])
                @endforeach
            </div>

            <x-pager :page="$page" :total-pages="$totalPages" />
        @endif
    </div>
</div>
