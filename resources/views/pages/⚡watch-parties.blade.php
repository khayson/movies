<?php

use App\Models\WatchParty;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Watch Parties — StreamVault')]
class extends Component
{
    public string $joinCode = '';

    #[Validate('required|string|max:100')]
    public string $newTitle = '';

    #[Validate('required|integer')]
    public int $newTmdbId = 0;

    #[Validate('required|in:movie,tv')]
    public string $newMediaType = 'movie';

    public string $newPosterPath = '';

    public ?WatchParty $joinedParty = null;

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
            ? WatchParty::where('host_id', auth()->id())->where('is_active', true)->latest()->get()
            : collect();

        return [
            'myParties' => $myParties,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-bold">Watch Parties</h1>

        @if($joinedParty)
            {{-- Active Party View --}}
            <div class="mb-8 rounded-xl border border-amber-800/30 bg-amber-900/10 p-6">
                <div class="flex items-start gap-4">
                    @if($joinedParty->poster_path)
                        <img src="https://image.tmdb.org/t/p/w154{{ $joinedParty->poster_path }}"
                             alt="" class="h-32 w-22 flex-shrink-0 rounded-lg object-cover">
                    @endif
                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-amber-400">{{ $joinedParty->title }}</h2>
                        <p class="mt-1 text-sm text-zinc-400">Hosted by {{ $joinedParty->host->name }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            <div class="rounded-lg bg-zinc-800 px-4 py-2">
                                <p class="text-xs text-zinc-500">Share Code</p>
                                <p class="font-mono text-lg font-bold tracking-wider text-amber-400">{{ $joinedParty->code }}</p>
                            </div>
                            <a href="{{ route('watch', [$joinedParty->media_type, $joinedParty->tmdb_id]) }}"
                               class="rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-500" wire:navigate>
                                Start Watching
                            </a>
                        </div>
                        @if($joinedParty->host_id === auth()->id())
                            <button wire:click="endParty({{ $joinedParty->id }})"
                                    class="mt-3 text-sm text-red-400 hover:text-red-300">
                                End Party
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-6 sm:grid-cols-2">
            {{-- Join a Party --}}
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <h2 class="mb-4 text-lg font-semibold">Join a Party</h2>
                <p class="mb-4 text-sm text-zinc-400">Enter the 8-character code shared by the host.</p>
                <div class="flex gap-2">
                    <input wire:model="joinCode" type="text" maxlength="8" placeholder="Enter code"
                           class="flex-1 rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 font-mono uppercase tracking-wider text-zinc-200 placeholder-zinc-600 focus:border-amber-600 focus:outline-none">
                    <button wire:click="joinParty"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500">
                        Join
                    </button>
                </div>
                @error('joinCode')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Create a Party --}}
            @auth
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                    <h2 class="mb-4 text-lg font-semibold">Create a Party</h2>
                    <p class="mb-4 text-sm text-zinc-400">Start a watch party and invite friends with a code.</p>
                    <div class="space-y-3">
                        <input wire:model="newTitle" type="text" placeholder="Party name (e.g. Friday Movie Night)"
                               class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:border-amber-600 focus:outline-none">
                        <input wire:model="newTmdbId" type="number" placeholder="TMDB ID"
                               class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:border-amber-600 focus:outline-none">
                        <select wire:model="newMediaType"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-zinc-200 focus:border-amber-600 focus:outline-none">
                            <option value="movie">Movie</option>
                            <option value="tv">TV Show</option>
                        </select>
                        <button wire:click="createParty"
                                class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500">
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
                <h2 class="mb-4 text-lg font-semibold">Your Active Parties</h2>
                <div class="space-y-3">
                    @foreach($myParties as $party)
                        <div class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
                            <div>
                                <p class="font-medium text-zinc-200">{{ $party->title }}</p>
                                <p class="text-xs text-zinc-500">Code: <span class="font-mono font-bold text-amber-400">{{ $party->code }}</span></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('watch', [$party->media_type, $party->tmdb_id]) }}"
                                   class="text-sm text-amber-400 hover:underline" wire:navigate>Watch</a>
                                <button wire:click="endParty({{ $party->id }})" class="text-sm text-red-400 hover:text-red-300">End</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
