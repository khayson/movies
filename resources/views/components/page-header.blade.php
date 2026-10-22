@props([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'backdrop' => null,
])

{{-- Shared header for browse pages: eyebrow, title, subtitle, optional faded backdrop, and a slot for tabs or filters. --}}
<header class="relative isolate overflow-hidden">
    @if($backdrop)
        <img src="{{ $backdrop }}" alt="" class="absolute inset-0 -z-10 size-full object-cover opacity-25">
    @endif
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-zinc-950/40 via-zinc-950/85 to-zinc-950"></div>

    <div class="mx-auto max-w-7xl px-4 pb-6 pt-10 sm:px-6 sm:pt-14 lg:px-8">
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 text-4xl font-black tracking-tight text-white md:text-5xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-2 max-w-2xl text-sm text-zinc-400 sm:text-base">{{ $subtitle }}</p>
        @endif

        @if(! $slot->isEmpty())
            <div class="mt-7">{{ $slot }}</div>
        @endif
    </div>
</header>
