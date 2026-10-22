@props(['active' => false])

<button
    type="button"
    aria-pressed="{{ $active ? 'true' : 'false' }}"
    {{ $attributes->class([
        'shrink-0 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-300',
        'bg-white text-zinc-950' => $active,
        'bg-white/[0.06] text-zinc-300 hover:bg-white/[0.12] hover:text-white' => ! $active,
    ]) }}
>
    {{ $slot }}
</button>
