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
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold">Collections</h1>
            @auth
                <button wire:click="$toggle('showCreateForm')" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">
                    New Collection
                </button>
            @endauth
        </div>

        {{-- Create form --}}
        @if($showCreateForm)
            <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <h2 class="mb-4 text-lg font-semibold">Create Collection</h2>
                <form wire:submit="createCollection">
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-zinc-400">Name</label>
                        <input type="text" wire:model="newName" placeholder="My Favorite Sci-Fi..."
                               class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-600" />
                        @error('newName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-zinc-400">Description (optional)</label>
                        <textarea wire:model="newDescription" rows="2" placeholder="What's this collection about?"
                                  class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-600"></textarea>
                    </div>
                    <div class="mb-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="newIsPublic" id="is_public" class="rounded border-zinc-600 bg-zinc-800 text-amber-600 focus:ring-amber-600">
                        <label for="is_public" class="text-sm text-zinc-400">Make this collection public</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">Create</button>
                        <button type="button" wire:click="$set('showCreateForm', false)" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-700">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="mb-6 flex gap-2">
            <button wire:click="$set('tab', 'discover')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'discover' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                Discover
            </button>
            @auth
                <button wire:click="$set('tab', 'mine')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $tab === 'mine' ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}">
                    My Collections ({{ $myCollections->count() }})
                </button>
            @endauth
        </div>

        @if($tab === 'discover')
            @if($publicCollections->count() > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($publicCollections as $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5 transition hover:border-zinc-700 hover:bg-zinc-900" wire:navigate>
                            <h3 class="mb-1 text-lg font-semibold text-zinc-100">{{ $collection->name }}</h3>
                            @if($collection->description)
                                <p class="mb-3 text-sm text-zinc-400">{{ Str::limit($collection->description, 100) }}</p>
                            @endif
                            <div class="flex items-center justify-between text-xs text-zinc-500">
                                <span>by {{ $collection->user->name }}</span>
                                <span>{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-zinc-500">No public collections yet. Be the first to create one!</p>
            @endif
        @else
            @if($myCollections->count() > 0)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($myCollections as $collection)
                        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
                            <div class="mb-2 flex items-start justify-between">
                                <a href="{{ route('collections.show', $collection->slug) }}" class="text-lg font-semibold text-zinc-100 transition hover:text-amber-400" wire:navigate>
                                    {{ $collection->name }}
                                </a>
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs {{ $collection->is_public ? 'text-green-400' : 'text-zinc-500' }}">
                                        {{ $collection->is_public ? 'Public' : 'Private' }}
                                    </span>
                                    <button wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Delete this collection?"
                                            class="rounded p-1 text-zinc-500 transition hover:text-red-400">
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
                <p class="text-sm text-zinc-500">You haven't created any collections yet.</p>
            @endif
        @endif
    </div>

    <div class="pb-16"></div>
</div>
