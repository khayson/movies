<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public int $personId;

    #[Url]
    public string $filmographyTab = 'all';

    public function mount(int $personId): void
    {
        $this->personId = $personId;
    }

    public function with(Tmdb $tmdb): array
    {
        $person = $tmdb->person($this->personId);

        $credits = collect($person['combined_credits']['cast'] ?? [])
            ->merge($person['combined_credits']['crew'] ?? [])
            ->unique(fn (array $item) => ($item['id'] ?? 0).'-'.($item['media_type'] ?? ''))
            ->sortByDesc('popularity')
            ->values();

        $knownFor = $credits->take(8);

        $filmography = match ($this->filmographyTab) {
            'movies' => $credits->where('media_type', 'movie'),
            'tv' => $credits->where('media_type', 'tv'),
            default => $credits,
        };

        $filmography = $filmography->sortByDesc(function (array $item) {
            return $item['release_date'] ?? $item['first_air_date'] ?? '0000';
        })->values()->take(50);

        $backdropCredit = $credits->first(fn (array $c) => ! empty($c['backdrop_path']));

        return [
            'person' => $person,
            'knownFor' => $knownFor,
            'filmography' => $filmography,
            'totalCredits' => $credits->count(),
            'movieCount' => $credits->where('media_type', 'movie')->count(),
            'tvCount' => $credits->where('media_type', 'tv')->count(),
            'backdrop' => $backdropCredit['backdrop_path'] ?? null,
        ];
    }
};
?>

<div>
    @php $name = $person['name'] ?? 'Unknown'; @endphp

    {{-- Cinematic Backdrop --}}
    <div class="relative h-[40vh] min-h-[300px] w-full overflow-hidden">
        @if($backdrop)
            <img src="{{ app(Tmdb::class)->backdropUrl($backdrop) }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/80 via-transparent to-transparent"></div>
    </div>

    <div class="mx-auto -mt-44 max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="relative flex flex-col gap-8 md:flex-row">
            {{-- Profile photo --}}
            <div class="w-48 shrink-0 md:w-64">
                <div class="aspect-[2/3] overflow-hidden rounded-2xl bg-zinc-800 shadow-2xl ring-1 ring-white/[0.08]">
                    @if(!empty($person['profile_path']))
                        <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path']) }}" alt="{{ $name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center text-zinc-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                        </div>
                    @endif
                </div>

                {{-- Personal info --}}
                <div class="mt-6 space-y-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4">
                    @if(!empty($person['known_for_department']))
                        <div>
                            <p class="text-xs font-medium text-zinc-500">Known For</p>
                            <p class="text-sm text-zinc-300">{{ $person['known_for_department'] }}</p>
                        </div>
                    @endif
                    @if(!empty($person['birthday']))
                        <div>
                            <p class="text-xs font-medium text-zinc-500">Born</p>
                            <p class="text-sm text-zinc-300">{{ \Carbon\Carbon::parse($person['birthday'])->format('M d, Y') }}</p>
                        </div>
                    @endif
                    @if(!empty($person['deathday']))
                        <div>
                            <p class="text-xs font-medium text-zinc-500">Died</p>
                            <p class="text-sm text-zinc-300">{{ \Carbon\Carbon::parse($person['deathday'])->format('M d, Y') }}</p>
                        </div>
                    @endif
                    @if(!empty($person['place_of_birth']))
                        <div>
                            <p class="text-xs font-medium text-zinc-500">Place of Birth</p>
                            <p class="text-sm text-zinc-300">{{ $person['place_of_birth'] }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-medium text-zinc-500">Credits</p>
                        <p class="text-sm font-semibold text-amber-400">{{ $totalCredits }} titles</p>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="flex-1 pt-4">
                <h1 class="mb-4 text-3xl font-bold tracking-tight md:text-4xl lg:text-5xl">{{ $name }}</h1>

                @if(!empty($person['biography']))
                    <div class="mb-8" x-data="{ expanded: false }">
                        <h2 class="mb-3 flex items-center gap-2 text-lg font-bold">
                            <span class="h-5 w-1 rounded-full bg-pink-500"></span>
                            Biography
                        </h2>
                        <div class="relative">
                            <p class="leading-relaxed text-zinc-400" :class="expanded || '{{ Str::length($person['biography']) }}' < 500 ? '' : 'line-clamp-4'">
                                {{ $person['biography'] }}
                            </p>
                            @if(Str::length($person['biography']) > 500)
                                <button x-on:click="expanded = !expanded" class="mt-2 text-sm font-medium text-pink-400 transition hover:text-pink-300"
                                        x-text="expanded ? 'Show less' : 'Read more'"></button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Known For --}}
                @if($knownFor->count() > 0)
                    <section class="mb-8">
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                            <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                            Known For
                        </h2>
                        <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                            @foreach($knownFor as $credit)
                                @php $creditType = $credit['media_type'] ?? 'movie'; @endphp
                                <div class="w-28 shrink-0 sm:w-32">
                                    @include('partials.media-card', ['item' => $credit, 'type' => $creditType])
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Filmography --}}
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                        <span class="h-5 w-1 rounded-full bg-blue-500"></span>
                        Filmography
                    </h2>
                    <div class="mb-4 flex gap-2">
                        <button wire:click="$set('filmographyTab', 'all')"
                                class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filmographyTab === 'all' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white' }}">
                            All ({{ $totalCredits }})
                        </button>
                        <button wire:click="$set('filmographyTab', 'movies')"
                                class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filmographyTab === 'movies' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white' }}">
                            Movies ({{ $movieCount }})
                        </button>
                        <button wire:click="$set('filmographyTab', 'tv')"
                                class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filmographyTab === 'tv' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white' }}">
                            TV ({{ $tvCount }})
                        </button>
                    </div>

                    <div class="space-y-2">
                        @foreach($filmography as $credit)
                            @php
                                $creditType = $credit['media_type'] ?? 'movie';
                                $creditTitle = $credit['title'] ?? $credit['name'] ?? 'Untitled';
                                $creditDate = $credit['release_date'] ?? $credit['first_air_date'] ?? '';
                                $creditYear = $creditDate ? Str::substr($creditDate, 0, 4) : '—';
                                $creditRole = $credit['character'] ?? $credit['job'] ?? '';
                            @endphp
                            <a href="{{ route($creditType === 'movie' ? 'movies.detail' : 'tv.detail', ['tmdbId' => $credit['id']]) }}"
                               class="flex items-center gap-4 rounded-xl border border-white/[0.04] bg-white/[0.02] p-3 transition hover:border-white/[0.1] hover:bg-white/[0.04]" wire:navigate>
                                <span class="w-12 shrink-0 text-center text-sm font-medium tabular-nums text-zinc-500">{{ $creditYear }}</span>
                                <div class="h-12 w-8 shrink-0 overflow-hidden rounded-lg bg-zinc-800 ring-1 ring-white/[0.06]">
                                    @if(!empty($credit['poster_path']))
                                        <img src="{{ app(Tmdb::class)->imageUrl($credit['poster_path'], 'w92') }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-zinc-200">{{ $creditTitle }}</p>
                                    @if($creditRole)
                                        <p class="text-xs text-zinc-500">{{ $creditRole }}</p>
                                    @endif
                                </div>
                                <span class="rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs font-medium text-zinc-500">{{ ucfirst($creditType) }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
