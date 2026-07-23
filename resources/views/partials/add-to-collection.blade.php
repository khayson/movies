{{-- Add to Collection dropdown --}}
{{-- Required: $userCollections, $showCollectionPicker, $mediaTitle, $mediaPoster --}}
@auth
    <div class="relative" x-data="{ open: $wire.showCollectionPicker }" x-on:click.outside="$wire.showCollectionPicker = false; open = false">
        <button wire:click="$toggle('showCollectionPicker')" x-on:click="open = !open"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 px-4 py-3 text-sm font-medium text-zinc-400 transition hover:border-zinc-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
            Collection
        </button>
        <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-64 rounded-xl border border-zinc-700 bg-zinc-900 p-2 shadow-xl">
            @if($userCollections->count() > 0)
                @foreach($userCollections as $col)
                    <button wire:click="addToCollection({{ $col->id }}, '{{ addslashes($mediaTitle) }}', '{{ $mediaPoster ?? '' }}')"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-zinc-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                        {{ Str::limit($col->name, 30) }}
                    </button>
                @endforeach
            @else
                <p class="px-3 py-2 text-xs text-zinc-500">No collections yet.</p>
            @endif
            <div class="mt-1 border-t border-zinc-800 pt-1">
                <a href="{{ route('collections.index', ['tab' => 'mine']) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-amber-400 transition hover:bg-zinc-800" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    New Collection
                </a>
            </div>
        </div>
    </div>
@endauth
