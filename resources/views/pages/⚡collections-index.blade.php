<?php

use App\Models\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Collections — StreamVault')]
class extends Component
{
    #[Url]
    public string $tab = 'discover';

    public bool $showCreateForm = false;

    #[Validate('required|string|min:2|max:100')]
    public string $newName = '';

    #[Validate('nullable|string|max:500')]
    public string $newDescription = '';

    public bool $newIsPublic = true;

    public function createCollection(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $this->validate();

        $slug = Str::slug($this->newName).'-'.Str::random(6);

        $user->collections()->create([
            'name' => $this->newName,
            'description' => $this->newDescription ?: null,
            'is_public' => $this->newIsPublic,
            'slug' => $slug,
        ]);

        $this->showCreateForm = false;
        $this->newName = '';
        $this->newDescription = '';
        $this->newIsPublic = true;
    }

    public function deleteCollection(int $collectionId): void
    {
        auth()->user()?->collections()->where('id', $collectionId)->delete();
    }

    public function with(): array
    {
        $publicCollections = Collection::where('is_public', true)
            ->withCount('items')
            ->with('user')
            ->latest()
            ->limit(30)
            ->get();

        $myCollections = auth()->check()
            ? auth()->user()->collections()->withCount('items')->latest()->get()
            : collect();

        return [
            'publicCollections' => $publicCollections,
            'myCollections' => $myCollections,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-purple-950/15 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-3">
                        <span class="h-6 w-1 rounded-full bg-purple-500"></span>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-400/80">Community</p>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Collections</h1>
                    <p class="mt-2 text-sm text-zinc-400">Curated lists by the community</p>
                </div>
                @auth
                    <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-600/20 transition hover:bg-purple-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        New Collection
                    </button>
                @endauth
            </div>

            {{-- Tabs --}}
            <div class="mt-8 flex gap-2">
                <button wire:click="$set('tab', 'discover')"
                        class="rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === 'discover' ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}">
                    Discover
                </button>
                @auth
                    <button wire:click="$set('tab', 'mine')"
                            class="rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === 'mine' ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:bg-white/[0.06] hover:text-white' }}">
                        My Collections ({{ $myCollections->count() }})
                    </button>
                @endauth
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Create form --}}
        @if($showCreateForm)
            <div class="mb-8 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-6">
                <h2 class="mb-4 text-lg font-semibold">Create Collection</h2>
                <form wire:submit="createCollection">
                    <div class="mb-4">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-400">Name</label>
                        <input type="text" wire:model="newName" placeholder="My Favorite Sci-Fi..."
                               class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-purple-500 focus:ring-1 focus:ring-purple-500/30" />
                        @error('newName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-400">Description (optional)</label>
                        <textarea wire:model="newDescription" rows="2" placeholder="What's this collection about?"
                                  class="w-full rounded-xl border border-white/[0.08] bg-white/[0.04] px-4 py-2.5 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-purple-500 focus:ring-1 focus:ring-purple-500/30"></textarea>
                    </div>
                    <div class="mb-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="newIsPublic" id="is_public" class="rounded border-zinc-600 bg-zinc-800 text-purple-600 focus:ring-purple-600">
                        <label for="is_public" class="text-sm text-zinc-400">Make this collection public</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-xl bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-500">Create</button>
                        <button type="button" wire:click="$set('showCreateForm', false)" class="rounded-xl border border-white/[0.06] bg-white/[0.03] px-5 py-2.5 text-sm font-medium text-zinc-400 transition hover:text-white">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        @if($tab === 'discover')
            @if($publicCollections->count() > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($publicCollections as $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 transition hover:border-white/[0.12] hover:bg-white/[0.04]" wire:navigate>
                            <h3 class="mb-1 text-lg font-semibold text-zinc-100">{{ $collection->name }}</h3>
                            @if($collection->description)
                                <p class="mb-3 text-sm text-zinc-400">{{ Str::limit($collection->description, 100) }}</p>
                            @endif
                            <div class="flex items-center justify-between text-xs text-zinc-500">
                                <span>by {{ $collection->user->name }}</span>
                                <span class="rounded-lg bg-white/[0.04] px-2.5 py-1 font-medium">{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                    <p class="text-zinc-500">No public collections yet. Be the first to create one!</p>
                </div>
            @endif
        @else
            @if($myCollections->count() > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($myCollections as $collection)
                        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5">
                            <div class="mb-2 flex items-start justify-between">
                                <a href="{{ route('collections.show', $collection->slug) }}" class="text-lg font-semibold text-zinc-100 transition hover:text-purple-400" wire:navigate>
                                    {{ $collection->name }}
                                </a>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-lg bg-white/[0.04] px-2.5 py-1 text-xs font-medium {{ $collection->is_public ? 'text-green-400' : 'text-zinc-500' }}">
                                        {{ $collection->is_public ? 'Public' : 'Private' }}
                                    </span>
                                    <button wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Delete this collection?"
                                            class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-red-500/10 hover:text-red-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </div>
                            @if($collection->description)
                                <p class="mb-2 text-sm text-zinc-400">{{ Str::limit($collection->description, 100) }}</p>
                            @endif
                            <p class="text-xs text-zinc-500">{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                    <p class="text-zinc-500">You haven't created any collections yet.</p>
                </div>
            @endif
        @endif
    </div>
</div>
