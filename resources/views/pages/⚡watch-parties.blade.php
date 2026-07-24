<?php

use App\Models\WatchParty;
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
                    <p class="mb-4 text-sm text-zinc-400">Start a watch party and invite friends with a code.</p>
                    <div class="space-y-3">
                        <input wire:model="newTitle" type="text" placeholder="Party name (e.g. Friday Movie Night)"
                               class="w-full rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">
                        <input wire:model="newTmdbId" type="number" placeholder="TMDB ID"
                               class="w-full rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">
                        <select wire:model="newMediaType"
                                class="w-full rounded-lg border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm text-zinc-200 outline-none transition focus:border-red-500/30 focus:bg-white/[0.06]">
                            <option value="movie">Movie</option>
                            <option value="tv">TV Show</option>
                        </select>
                        <button wire:click="createParty"
                                class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500">
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
