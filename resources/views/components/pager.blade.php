@props([
    'page',
    'totalPages',
    'previous' => 'previousPage',
    'next' => 'nextPage',
])

@if($totalPages > 1)
    <nav class="mt-12 flex items-center justify-center gap-3" aria-label="Pagination">
        <button
            type="button"
            wire:click="{{ $previous }}"
            @disabled($page <= 1)
            class="inline-flex size-11 items-center justify-center rounded-full bg-white/[0.06] text-white transition hover:bg-white/[0.12] disabled:pointer-events-none disabled:opacity-30"
            aria-label="Previous page"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        </button>
        <span class="min-w-24 text-center text-sm tabular-nums text-zinc-400">Page <span class="font-semibold text-white">{{ $page }}</span> of {{ $totalPages }}</span>
        <button
            type="button"
            wire:click="{{ $next }}"
            @disabled($page >= $totalPages)
            class="inline-flex size-11 items-center justify-center rounded-full bg-white/[0.06] text-white transition hover:bg-white/[0.12] disabled:pointer-events-none disabled:opacity-30"
            aria-label="Next page"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        </button>
    </nav>
@endif
