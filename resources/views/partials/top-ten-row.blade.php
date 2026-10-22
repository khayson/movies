@php
    $tmdb = app(\App\Services\Tmdb::class);
    $detailRoute = $type === 'tv' ? 'tv.detail' : 'movies.detail';
@endphp

@if(count($items) > 0)
    <section class="reveal mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="{{ $title }}">
        <div class="mb-3 flex items-center gap-2.5">
            <span class="flex size-8 flex-col items-center justify-center rounded bg-amber-400 text-[8px] font-black leading-none tracking-tight text-zinc-950" aria-hidden="true"><span>TOP</span><span class="text-sm">10</span></span>
            <h2 class="section-title">{{ $title }}</h2>
        </div>

        <div class="group/row relative" x-data>
            <button
                type="button"
                aria-label="Scroll left"
                @click="$refs.scroll.scrollBy({ left: -$refs.scroll.clientWidth * 0.8, behavior: 'smooth' })"
                class="absolute -left-2 top-1/2 z-20 hidden -translate-y-1/2 rounded-full bg-black/60 p-2 text-white/70 shadow-lg backdrop-blur-sm transition hover:bg-black/80 hover:text-white group-hover/row:sm:flex"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>

            <ol x-ref="scroll" class="scrollbar-hide -mx-4 flex gap-2 overflow-x-auto px-4 pb-2">
                @foreach($items as $rank => $item)
                    <li class="shrink-0">
                        <a
                            href="{{ route($detailRoute, $item['id']) }}"
                            @click="if (! $event.ctrlKey && ! $event.metaKey && ! $event.shiftKey && ! $event.altKey && $event.button === 0) { $event.preventDefault(); $event.stopImmediatePropagation(); $dispatch('open-title', { type: '{{ $type }}', id: {{ (int) $item['id'] }} }) }"
                            class="group/top flex items-end"
                            aria-label="#{{ $rank + 1 }} {{ $item['title'] ?? $item['name'] ?? 'Untitled' }}"
                        >
                            <span
                                aria-hidden="true"
                                class="-mr-4 select-none text-[7rem] font-black leading-[0.8] tracking-tighter text-zinc-950 sm:-mr-5 sm:text-[9rem] {{ $rank === 9 ? 'tracking-[-0.15em]' : '' }}"
                                style="-webkit-text-stroke: 3px rgb(113 113 122);"
                            >{{ $rank + 1 }}</span>
                            <span class="relative block w-28 overflow-hidden rounded-md bg-zinc-800 ring-1 ring-white/[0.06] transition duration-300 group-hover/top:scale-105 group-hover/top:ring-white/30 sm:w-32">
                                @if(! empty($item['poster_path']))
                                    <img src="{{ $tmdb->imageUrl($item['poster_path'], 'w342') }}" alt="" loading="lazy" class="aspect-[2/3] w-full object-cover">
                                @else
                                    <span class="flex aspect-[2/3] items-center justify-center p-2 text-center text-xs text-zinc-500">{{ $item['title'] ?? $item['name'] ?? 'Untitled' }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>

            <button
                type="button"
                aria-label="Scroll right"
                @click="$refs.scroll.scrollBy({ left: $refs.scroll.clientWidth * 0.8, behavior: 'smooth' })"
                class="absolute -right-2 top-1/2 z-20 hidden -translate-y-1/2 rounded-full bg-black/60 p-2 text-white/70 shadow-lg backdrop-blur-sm transition hover:bg-black/80 hover:text-white group-hover/row:sm:flex"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>
    </section>
@endif
