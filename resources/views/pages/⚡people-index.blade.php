<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('People — StreamVault')]
class extends Component
{
    #[Url]
    public int $page = 1;

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
        $data = $tmdb->popularPeople($this->page);

        return [
            'people' => $data['results'] ?? [],
            'totalPages' => min($data['total_pages'] ?? 1, 500),
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-pink-950/15 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="mb-2 flex items-center gap-3">
                <span class="h-6 w-1 rounded-full bg-pink-500"></span>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-pink-400/80">Cast & Crew</p>
            </div>
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Popular People</h1>
            <p class="mt-2 text-sm text-zinc-400">Actors, directors, and creators in the spotlight</p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="mb-6">
            <p class="text-sm text-zinc-500">Page <span class="font-medium text-zinc-300">{{ $page }}</span> of <span class="font-medium text-zinc-300">{{ $totalPages }}</span></p>
        </div>

        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($people as $person)
                <a href="{{ route('people.detail', $person['id']) }}" class="group text-center" wire:navigate>
                    <div class="mx-auto aspect-square w-full overflow-hidden rounded-2xl bg-zinc-800 ring-1 ring-white/[0.06] transition group-hover:ring-pink-500/40">
                        @if(!empty($person['profile_path']))
                            <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex h-full items-center justify-center text-zinc-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                            </div>
                        @endif
                    </div>
                    <p class="mt-3 text-sm font-medium text-zinc-200 transition group-hover:text-pink-400">{{ $person['name'] }}</p>
                    @if(!empty($person['known_for_department']))
                        <p class="text-xs text-zinc-500">{{ $person['known_for_department'] }}</p>
                    @endif
                    @if(!empty($person['known_for']))
                        <p class="mt-1 text-xs text-zinc-600">{{ Str::limit(collect($person['known_for'])->pluck('title')->filter()->implode(', '), 40) }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        @if($totalPages > 1)
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
