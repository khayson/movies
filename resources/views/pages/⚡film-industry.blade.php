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
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-emerald-950/20 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-emerald-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400/80">{{ $details['countryName'] }}</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">{{ $details['name'] }}</h1>
            <p class="mt-2 text-sm text-zinc-400">{{ $details['tagline'] }}</p>

            <div class="mt-8 flex gap-2">
                @foreach(['movie' => 'Movies', 'tv' => 'TV Shows'] as $key => $label)
                    <button
                        wire:click="setTab('{{ $key }}')"
                        class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $type === $key ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        @if($items === [])
            <p class="py-16 text-center text-sm text-zinc-500">No {{ $type === 'tv' ? 'TV shows' : 'movies' }} found for {{ $details['name'] }} right now.</p>
        @else
            <div class="mb-6">
                <p class="text-sm text-zinc-500">
                    Page <span class="font-medium text-zinc-300">{{ $page }}</span> of <span class="font-medium text-zinc-300">{{ $totalPages }}</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($items as $item)
                    @include('partials.media-card', ['item' => $item, 'type' => $type])
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
        @endif
    </div>
</div>
