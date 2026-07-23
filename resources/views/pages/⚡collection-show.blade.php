<?php

use App\Models\Collection;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public string $slug;

    public bool $showEditForm = false;

    #[Validate('required|string|min:2|max:100')]
    public string $editName = '';

    #[Validate('nullable|string|max:500')]
    public string $editDescription = '';

    public bool $editIsPublic = true;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function startEdit(): void
    {
        $collection = $this->getCollection();
        if (! $collection || $collection->user_id !== auth()->id()) {
            return;
        }

        $this->editName = $collection->name;
        $this->editDescription = $collection->description ?? '';
        $this->editIsPublic = $collection->is_public;
        $this->showEditForm = true;
    }

    public function updateCollection(): void
    {
        $collection = $this->getCollection();
        if (! $collection || $collection->user_id !== auth()->id()) {
            return;
        }

        $this->validate();

        $collection->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
            'is_public' => $this->editIsPublic,
        ]);

        $this->showEditForm = false;
    }

    public function removeItem(int $itemId): void
    {
        $collection = $this->getCollection();
        if (! $collection || $collection->user_id !== auth()->id()) {
            return;
        }

        $collection->items()->where('id', $itemId)->delete();
    }

    public function with(): array
    {
        $collection = $this->getCollection();

        if (! $collection) {
            abort(404);
        }

        if (! $collection->is_public && $collection->user_id !== auth()->id()) {
            abort(403);
        }

        $collection->loadCount('items');
        $collection->load(['items', 'user']);

        $isOwner = auth()->check() && $collection->user_id === auth()->id();

        return [
            'collection' => $collection,
            'isOwner' => $isOwner,
        ];
    }

    private function getCollection(): ?Collection
    {
        return Collection::where('slug', $this->slug)->first();
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="mb-2 flex items-start justify-between">
                <div>
                    <a href="{{ route('collections.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-zinc-500 transition hover:text-zinc-300" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        Collections
                    </a>
                    <h1 class="text-3xl font-bold">{{ $collection->name }}</h1>
                </div>
                @if($isOwner)
                    <button wire:click="startEdit" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700">
                        Edit
                    </button>
                @endif
            </div>
            <div class="flex items-center gap-3 text-sm text-zinc-500">
                <span>by {{ $collection->user->name }}</span>
                <span>{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</span>
                <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs {{ $collection->is_public ? 'text-green-400' : 'text-zinc-500' }}">
                    {{ $collection->is_public ? 'Public' : 'Private' }}
                </span>
            </div>
            @if($collection->description)
                <p class="mt-2 max-w-2xl text-sm text-zinc-400">{{ $collection->description }}</p>
            @endif

            {{-- Share link --}}
            @if($collection->is_public)
                <div class="mt-3 flex items-center gap-2">
                    <span class="text-xs text-zinc-500">Share:</span>
                    <input type="text" readonly value="{{ route('collections.show', $collection->slug) }}"
                           class="rounded border border-zinc-700 bg-zinc-800 px-3 py-1 text-xs text-zinc-400 outline-none" style="width: 300px" />
                </div>
            @endif
        </div>

        {{-- Edit form --}}
        @if($showEditForm)
            <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/50 p-6">
                <h2 class="mb-4 text-lg font-semibold">Edit Collection</h2>
                <form wire:submit="updateCollection">
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-zinc-400">Name</label>
                        <input type="text" wire:model="editName"
                               class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white outline-none focus:border-amber-600" />
                        @error('editName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="mb-1 block text-sm font-medium text-zinc-400">Description</label>
                        <textarea wire:model="editDescription" rows="2"
                                  class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white outline-none focus:border-amber-600"></textarea>
                    </div>
                    <div class="mb-4 flex items-center gap-2">
                        <input type="checkbox" wire:model="editIsPublic" id="edit_public" class="rounded border-zinc-600 bg-zinc-800 text-amber-600 focus:ring-amber-600">
                        <label for="edit_public" class="text-sm text-zinc-400">Public</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">Save</button>
                        <button type="button" wire:click="$set('showEditForm', false)" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-700">Cancel</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Items grid --}}
        @if($collection->items->count() > 0)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($collection->items as $item)
                    <div class="group relative">
                        <a href="{{ route($item->media_type === 'movie' ? 'movies.detail' : 'tv.detail', ['tmdbId' => $item->tmdb_id]) }}"
                           class="block" wire:navigate>
                            <div class="aspect-[2/3] overflow-hidden rounded-lg bg-zinc-800">
                                @if($item->poster_path)
                                    <img src="{{ app(Tmdb::class)->imageUrl($item->poster_path) }}" alt="{{ $item->title }}"
                                         class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                                @else
                                    <div class="flex h-full items-center justify-center text-zinc-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-medium text-zinc-300">{{ Str::limit($item->title, 30) }}</p>
                            <p class="text-xs text-zinc-500">{{ ucfirst($item->media_type) }}</p>
                        </a>
                        @if($isOwner)
                            <button wire:click="removeItem({{ $item->id }})" wire:confirm="Remove from collection?"
                                    class="absolute right-2 top-2 rounded-full bg-black/70 p-1.5 text-zinc-400 opacity-0 transition hover:text-red-400 group-hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        @endif
                        @if($item->note)
                            <p class="mt-1 text-xs italic text-zinc-500">{{ Str::limit($item->note, 50) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-12 text-center">
                <p class="text-zinc-500">This collection is empty.</p>
                @if($isOwner)
                    <p class="mt-2 text-sm text-zinc-600">Add titles from any movie or TV show page.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
