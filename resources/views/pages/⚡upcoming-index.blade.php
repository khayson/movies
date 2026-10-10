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
    #[Url]
    public string $tab = 'movies';

    #[Url]
    public int $page = 1;

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['movies', 'tv'], true) ? $tab : 'movies';
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
        $mediaType = $this->tab === 'tv' ? 'tv' : 'movie';
        $data = $tmdb->comingSoon($mediaType, $this->page);

        $today = now()->toDateString();
        $dateField = $mediaType === 'tv' ? 'first_air_date' : 'release_date';
        $items = collect($data['results'] ?? [])
            ->filter(fn (array $item) => ($item[$dateField] ?? '') > $today)
            ->values()
            ->all();

        $featured = collect($items)->first(fn (array $item) => ! empty($item['backdrop_path']))
            ?? ($items[0] ?? null);

        $ogImage = null;
        if (is_array($featured) && ! empty($featured['backdrop_path'])) {
            $ogImage = $tmdb->backdropUrl((string) $featured['backdrop_path'], 'w1280');
        }

        View::share([
            'title' => 'Coming Soon',
            'ogTitle' => 'Coming Soon — '.config('app.name'),
            'ogDescription' => 'Browse upcoming movies and TV shows before they premiere. Trailers, release dates, and details on '.config('app.name').'.',
            'ogImage' => $ogImage,
            'ogImageAlt' => 'Coming Soon on '.config('app.name'),
            'ogUrl' => route('coming-soon'),
            'ogType' => 'website',
        ]);

        return [
            'items' => $items,
            'featured' => $featured,
            'mediaType' => $mediaType,
            'dateField' => $dateField,
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    @php
        $featuredTitle = is_array($featured)
            ? ($mediaType === 'tv'
                ? ($featured['name'] ?? $featured['title'] ?? 'Coming Soon')
                : ($featured['title'] ?? $featured['name'] ?? 'Coming Soon'))
            : null;
        $featuredDate = is_array($featured) ? ($featured[$dateField] ?? '') : '';
        $featuredOverview = is_array($featured) ? ($featured['overview'] ?? '') : '';
        $featuredId = is_array($featured) ? ($featured['id'] ?? null) : null;
        $featuredHref = $featuredId
            ? route($mediaType === 'tv' ? 'tv.detail' : 'movies.detail', $featuredId)
            : null;
    @endphp

    {{-- Featured hero --}}
    <div class="hero-bleed relative min-h-[420px] overflow-hidden lg:min-h-[520px]">
        @if(is_array($featured) && ! empty($featured['backdrop_path']))
            <img
                src="{{ app(\App\Services\Tmdb::class)->backdropUrl($featured['backdrop_path'], 'w1280') }}"
                alt="{{ $featuredTitle }}"
                class="absolute inset-0 size-full object-cover"
            >
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-amber-950 via-zinc-950 to-zinc-950"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/90 via-zinc-950/40 to-transparent"></div>

        <div class="relative mx-auto flex min-h-[420px] max-w-7xl flex-col justify-end px-4 pb-10 pt-24 sm:px-6 lg:min-h-[520px] lg:px-8 lg:pb-14">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="rounded-md bg-amber-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-zinc-950">Coming soon</span>
                @if($featuredDate)
                    <span class="rounded-md border border-white/10 bg-black/30 px-2.5 py-1 text-[11px] font-medium text-amber-200 backdrop-blur-sm">
                        Premieres {{ \Carbon\Carbon::parse($featuredDate)->format('M j, Y') }}
                    </span>
                @endif
            </div>

            <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-white md:text-5xl lg:text-6xl">
                {{ $featuredTitle ?? 'Coming Soon' }}
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-300/90 sm:text-base">
                @if($featuredOverview)
                    {{ Str::limit($featuredOverview, 180) }}
                @else
                    Browse upcoming movies and TV shows before they premiere — trailers, dates, and details in one place.
                @endif
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-2.5">
                @if($featuredHref)
                    <a href="{{ $featuredHref }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600"
                       wire:navigate>
                        View details
                    </a>
                @endif
                @include('partials.share-buttons', [
                    'shareTitle' => 'Coming Soon on '.config('app.name'),
                    'shareText' => 'Coming soon on '.config('app.name').' — upcoming movies & TV with release dates and trailers',
                    'shareUrl' => route('coming-soon'),
                    'shareImage' => is_array($featured) ? \App\Support\SocialMeta::shareImage($featured) : null,
                    'isUpcoming' => true,
                    'shareReleaseDate' => $featuredDate ? \Carbon\Carbon::parse($featuredDate)->format('M j, Y') : null,
                ])
            </div>
        </div>
    </div>

    {{-- Tabs + grid --}}
    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 border-b border-white/[0.06] py-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-white">All coming soon</h2>
                <p class="mt-1 text-sm text-zinc-500">Sorted by popularity · future releases only</p>
            </div>
            <div class="flex gap-2">
                @foreach(['movies' => 'Movies', 'tv' => 'TV Shows'] as $key => $label)
                    <button
                        type="button"
                        wire:click="setTab('{{ $key }}')"
                        class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === $key ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="mb-6 mt-6">
            <p class="text-sm text-zinc-500">Page <span class="font-medium text-zinc-300">{{ $page }}</span> of <span class="font-medium text-zinc-300">{{ $totalPages }}</span></p>
        </div>

        @if(count($items) > 0)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($items as $item)
                    @include('partials.media-card', [
                        'item' => $item,
                        'type' => $mediaType,
                    ])
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] px-6 py-16 text-center">
                <p class="text-sm font-medium text-zinc-300">No upcoming {{ $tab === 'tv' ? 'TV shows' : 'movies' }} found right now.</p>
                <p class="mt-1 text-xs text-zinc-500">Try the other tab or check back soon.</p>
            </div>
        @endif

        <div class="mt-10 flex items-center justify-center gap-3">
            @if($page > 1)
                <button type="button" wire:click="previousPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    Previous
                </button>
            @endif
            <span class="rounded-xl bg-white/[0.04] px-5 py-2.5 text-sm tabular-nums text-zinc-500">{{ $page }} / {{ $totalPages }}</span>
            @if($page < $totalPages)
                <button type="button" wire:click="nextPage" class="inline-flex items-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            @endif
        </div>
    </div>
</div>
