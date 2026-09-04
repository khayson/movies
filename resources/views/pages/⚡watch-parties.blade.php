<?php

use App\Models\WatchParty;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Watch Parties — StreamVault')]
class extends Component
{
    public string $joinCode = '';

    #[Validate('required|string|max:100')]
    public string $newTitle = '';

    #[Validate('required|integer|min:1')]
    public int $newTmdbId = 0;

    #[Validate('required|in:movie,tv')]
    public string $newMediaType = 'movie';

    public string $newPosterPath = '';

    public ?WatchParty $joinedParty = null;

    public string $tmdbSearch = '';

    /** @var array<int, array<string, mixed>> */
    public array $tmdbResults = [];

    public string $selectedTitle = '';

    public function searchTmdb(): void
    {
        if (strlen($this->tmdbSearch) < 2) {
            $this->tmdbResults = [];

            return;
        }

        $tmdb = app(Tmdb::class);
        $results = $tmdb->search($this->tmdbSearch)['results'] ?? [];

        $this->tmdbResults = collect($results)
            ->filter(fn (array $item) => in_array($item['media_type'] ?? '', ['movie', 'tv']))
            ->take(6)
            ->map(fn (array $item) => [
                'id' => $item['id'],
                'title' => $item['title'] ?? $item['name'] ?? 'Unknown',
                'media_type' => $item['media_type'],
                'poster_path' => $item['poster_path'] ?? '',
                'year' => Str::substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4),
            ])
            ->values()
            ->all();
    }

    public function selectTitle(int $id, string $mediaType, string $title, string $posterPath): void
    {
        $this->newTmdbId = $id;
        $this->newMediaType = $mediaType;
        $this->newPosterPath = $posterPath;
        $this->selectedTitle = $title;
        $this->tmdbResults = [];
        $this->tmdbSearch = '';
    }

    public function clearSelection(): void
    {
        $this->newTmdbId = 0;
        $this->newMediaType = 'movie';
        $this->newPosterPath = '';
        $this->selectedTitle = '';
    }

    public function joinParty(): void
    {
        $party = WatchParty::where('code', strtoupper(trim($this->joinCode)))
            ->where('is_active', true)
            ->first();

        if ($party) {
            $this->joinedParty = $party;
        } else {
            $this->addError('joinCode', 'Party not found or no longer active.');
        }
    }

    public function createParty(): void
    {
        $this->validate();

        $party = WatchParty::create([
            'host_id' => auth()->id(),
            'title' => $this->newTitle,
            'tmdb_id' => $this->newTmdbId,
            'media_type' => $this->newMediaType,
            'poster_path' => $this->newPosterPath ?: null,
            'starts_at' => now(),
        ]);

        $this->joinedParty = $party;
    }

    public function endParty(int $id): void
    {
        WatchParty::where('host_id', auth()->id())
            ->where('id', $id)
            ->update(['is_active' => false]);

        $this->joinedParty = null;
    }

    public function with(): array
    {
        $myParties = auth()->check()
            ? WatchParty::with('host')->where('host_id', auth()->id())->where('is_active', true)->latest()->get()
            : collect();

        return [
            'myParties' => $myParties,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <h1 class="text-2xl font-bold text-white">Watch Parties</h1>
            <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
            <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
        </div>
        <p class="mb-6 text-sm text-zinc-400">Share a code so friends open the same title. Playback is not synced — everyone watches on their own.</p>

        @if($joinedParty)
            <div class="mb-8">
                @include('partials.party-card', ['party' => $joinedParty, 'variant' => 'full'])
            </div>
        @endif

        <div class="grid gap-6 sm:grid-cols-2">
            {{-- Join a Party --}}
            <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Join a Party</h2>
                <p class="mb-4 text-sm text-zinc-400">Enter the 8-character code shared by the host.</p>
                <div class="flex gap-2">
                    <input wire:model="joinCode" type="text" maxlength="8" placeholder="Enter code"
                           class="flex-1 rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 font-mono uppercase tracking-wider text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">
                    <button wire:click="joinParty"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500">
                        Join
                    </button>
                </div>
                @error('joinCode')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Create a Party --}}
            @auth
                <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-6">
                    <h2 class="mb-4 text-lg font-semibold text-white">Create a Party</h2>
                    <p class="mb-4 text-sm text-zinc-400">Pick a title, get a code, and share it. Friends join and open the same watch page.</p>
                    <div class="space-y-3">
                        <input wire:model="newTitle" type="text" placeholder="Party name (e.g. Friday Movie Night)"
                               class="w-full rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">

                        {{-- TMDB Search Picker --}}
                        @if($selectedTitle)
                            <div class="flex items-center gap-3 rounded-lg border border-red-500/20 bg-red-500/[0.04] px-3 py-2">
                                @if($newPosterPath)
                                    <img src="https://image.tmdb.org/t/p/w92{{ $newPosterPath }}" alt="" class="h-10 w-7 rounded object-cover">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-200">{{ $selectedTitle }}</p>
                                    <p class="text-xs text-zinc-500">{{ ucfirst($newMediaType) }}</p>
                                </div>
                                <button wire:click="clearSelection" class="rounded p-1 text-zinc-500 transition hover:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <input wire:model.live.debounce.300ms="tmdbSearch"
                                       wire:keyup="searchTmdb"
                                       type="text"
                                       placeholder="Search for a movie or TV show..."
                                       class="w-full rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">
                                @if(count($tmdbResults) > 0)
                                    <div class="absolute left-0 right-0 top-full z-20 mt-1 rounded-lg border border-white/[0.08] bg-zinc-900 shadow-xl">
                                        @foreach($tmdbResults as $result)
                                            <button wire:click="selectTitle({{ $result['id'] }}, '{{ $result['media_type'] }}', '{{ addslashes($result['title']) }}', '{{ $result['poster_path'] }}')"
                                                    class="flex w-full items-center gap-3 px-3 py-2 text-left transition hover:bg-white/[0.04] first:rounded-t-lg last:rounded-b-lg">
                                                @if($result['poster_path'])
                                                    <img src="https://image.tmdb.org/t/p/w92{{ $result['poster_path'] }}" alt="" class="h-10 w-7 rounded object-cover">
                                                @else
                                                    <div class="flex h-10 w-7 items-center justify-center rounded bg-zinc-800 text-[10px] text-zinc-600">N/A</div>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm text-zinc-200">{{ $result['title'] }}</p>
                                                    <p class="text-xs text-zinc-500">{{ ucfirst($result['media_type']) }}{{ $result['year'] ? " ({$result['year']})" : '' }}</p>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <button wire:click="createParty"
                                class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500 disabled:opacity-50"
                                {{ $newTmdbId === 0 ? 'disabled' : '' }}>
                            Create Party
                        </button>
                    </div>
                    @error('newTitle')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endauth
        </div>

        {{-- My Active Parties --}}
        @if($myParties->isNotEmpty())
            <section class="mt-8">
                <div class="mb-4 flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-white">Your Active Parties</h2>
                    <span class="size-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                    <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
                </div>
                <div class="space-y-3">
                    @foreach($myParties as $party)
                        @include('partials.party-card', ['party' => $party, 'variant' => 'list'])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
