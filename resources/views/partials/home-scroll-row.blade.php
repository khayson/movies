@php
    $type = $type ?? null;
    $rowId = 'row-' . uniqid();
@endphp

<div class="group/row relative" x-data="{ scrollEl: null }" x-init="scrollEl = $refs.scroll_{{ str_replace('-', '_', $rowId) }}">
    {{-- Left arrow --}}
    <button
        @click="scrollEl.scrollBy({ left: -scrollEl.clientWidth * 0.8, behavior: 'smooth' })"
        class="absolute -left-2 top-1/2 z-20 hidden -translate-y-1/2 rounded-full bg-black/60 p-2 text-white/70 shadow-lg backdrop-blur-sm transition hover:bg-black/80 hover:text-white group-hover/row:sm:flex"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
    </button>

    {{-- Scroll container --}}
    <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-4" x-ref="scroll_{{ str_replace('-', '_', $rowId) }}">
        @foreach(array_slice($items, 0, 20) as $item)
            <div class="w-36 shrink-0 sm:w-40 lg:w-44">
                @include('partials.media-card', [
                    'item' => $item,
                    'type' => $type ?? ($item['media_type'] ?? 'movie'),
                ])
            </div>
        @endforeach
    </div>

    {{-- Right arrow --}}
    <button
        @click="scrollEl.scrollBy({ left: scrollEl.clientWidth * 0.8, behavior: 'smooth' })"
        class="absolute -right-2 top-1/2 z-20 hidden -translate-y-1/2 rounded-full bg-black/60 p-2 text-white/70 shadow-lg backdrop-blur-sm transition hover:bg-black/80 hover:text-white group-hover/row:sm:flex"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
    </button>
</div>
