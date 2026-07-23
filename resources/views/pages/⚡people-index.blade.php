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
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-8 text-3xl font-bold">Popular People</h1>

        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach($people as $person)
                <a href="{{ route('people.detail', $person['id']) }}" class="group text-center" wire:navigate>
                    <div class="mx-auto aspect-square w-full overflow-hidden rounded-full bg-zinc-800">
                        @if(!empty($person['profile_path']))
                            <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}"
                                 class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                        @else
                            <div class="flex h-full items-center justify-center text-zinc-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                            </div>
                        @endif
                    </div>
                    <p class="mt-2 text-sm font-medium text-zinc-200 group-hover:text-amber-400">{{ $person['name'] }}</p>
                    @if(!empty($person['known_for_department']))
                        <p class="text-xs text-zinc-500">{{ $person['known_for_department'] }}</p>
                    @endif
                    @if(!empty($person['known_for']))
                        <p class="mt-1 text-xs text-zinc-600">{{ Str::limit(collect($person['known_for'])->pluck('title')->filter()->implode(', '), 40) }}</p>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($totalPages > 1)
            <div class="mt-8 flex items-center justify-center gap-4">
                <button wire:click="previousPage" @disabled($page <= 1)
                        class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700 disabled:opacity-50">
                    Previous
                </button>
                <span class="text-sm text-zinc-500">Page {{ $page }} of {{ $totalPages }}</span>
                <button wire:click="nextPage" @disabled($page >= $totalPages)
                        class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700 disabled:opacity-50">
                    Next
                </button>
            </div>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
