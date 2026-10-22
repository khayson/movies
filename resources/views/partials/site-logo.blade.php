<a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-2.5" wire:navigate aria-label="{{ config('app.name') }} home">
    <span class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 shadow-lg shadow-amber-500/20 transition group-hover:shadow-amber-400/30">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-950" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
    </span>
    <span class="{{ ($compactOnPhones ?? false) ? 'hidden min-[420px]:inline' : '' }} text-lg font-black tracking-tight text-white">{{ config('app.name') }}</span>
</a>
