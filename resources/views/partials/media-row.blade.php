@props([
    'title',
    'items',
    'type' => null,
    'seeAllRoute' => null,
    'style' => 'grid',
    'showOverview' => false,
    'variant' => 'default',
    'removable' => false,
    'removeAction' => null,
])

@php
    $cardProps = fn ($item): array => [
        'item' => $item,
        'type' => $type ?? (is_array($item) ? ($item['media_type'] ?? 'movie') : null),
        'showOverview' => $showOverview,
        'variant' => $variant,
        'removable' => $removable,
        'removeAction' => $removeAction,
        'removeId' => $removable && $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : null,
    ];
@endphp

@if(count($items) > 0)
    <section class="mt-10" aria-label="{{ $title }}">
        <div class="mb-3 flex items-end justify-between gap-4">
            <h2 class="section-title">{{ $title }}</h2>
            @if($seeAllRoute)
                <a href="{{ $seeAllRoute }}" class="group inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-zinc-400 transition hover:text-amber-300" wire:navigate>
                    Explore all
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </a>
            @endif
        </div>

        @if($style === 'scroll')
            <div class="group/row relative" x-data>
                <button
                    type="button"
                    aria-label="Scroll {{ $title }} left"
                    @click="$refs.scroll.scrollBy({ left: -$refs.scroll.clientWidth * 0.85, behavior: 'smooth' })"
                    class="absolute -left-4 top-0 z-20 hidden h-[calc(100%-2.75rem)] w-10 items-center justify-center bg-gradient-to-r from-zinc-950 to-transparent text-white/80 opacity-0 transition hover:text-white group-hover/row:opacity-100 sm:flex"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                </button>

                <div x-ref="scroll" class="scrollbar-hide -mx-4 flex snap-x gap-3 overflow-x-auto scroll-px-4 px-4 pb-2 sm:gap-4">
                    @foreach(array_slice(is_array($items) ? $items : $items->all(), 0, 20) as $item)
                        <div class="w-32 shrink-0 snap-start sm:w-40 lg:w-44">
                            @include('partials.media-card', $cardProps($item))
                        </div>
                    @endforeach
                </div>

                <button
                    type="button"
                    aria-label="Scroll {{ $title }} right"
                    @click="$refs.scroll.scrollBy({ left: $refs.scroll.clientWidth * 0.85, behavior: 'smooth' })"
                    class="absolute -right-4 top-0 z-20 hidden h-[calc(100%-2.75rem)] w-10 items-center justify-center bg-gradient-to-l from-zinc-950 to-transparent text-white/80 opacity-0 transition hover:text-white group-hover/row:opacity-100 sm:flex"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                </button>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach(array_slice(is_array($items) ? $items : $items->all(), 0, 12) as $item)
                    @include('partials.media-card', $cardProps($item))
                @endforeach
            </div>
        @endif
    </section>
@endif
