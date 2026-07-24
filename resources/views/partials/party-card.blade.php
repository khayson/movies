@props([
    'party',
    'variant' => 'compact',
])

@php
    $tmdb = app(\App\Services\Tmdb::class);
    $watchUrl = route('watch', [$party->media_type, $party->tmdb_id]);
    $hostName = $party->host->name ?? 'Host';
    $isOwner = auth()->check() && $party->host_id === auth()->id();
@endphp

@if($variant === 'full')
    {{-- Full party card (watch-parties page, active view) --}}
    <div class="overflow-hidden rounded-xl border border-white/[0.06] bg-white/[0.02]">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
            @if($party->poster_path)
                <div class="h-36 w-24 shrink-0 overflow-hidden rounded-lg bg-zinc-800 ring-1 ring-white/[0.06]">
                    <img src="{{ $tmdb->imageUrl($party->poster_path, 'w154') }}" alt="{{ $party->title }}" class="h-full w-full object-cover">
                </div>
            @endif
            <div class="flex-1">
                <h3 class="text-lg font-bold text-white">{{ $party->title }}</h3>
                <p class="mt-1 text-sm text-zinc-400">Hosted by <span class="font-medium text-zinc-300">{{ $hostName }}</span></p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ ucfirst($party->media_type) }} &bull; Started {{ $party->starts_at->diffForHumans() }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <div class="rounded-lg border border-white/[0.06] bg-white/[0.04] px-4 py-2">
                        <p class="text-[10px] font-medium uppercase tracking-widest text-zinc-500">Party Code</p>
                        <p class="font-mono text-lg font-bold tracking-wider text-red-400">{{ $party->code }}</p>
                    </div>
                    <a href="{{ $watchUrl }}"
                       class="inline-flex items-center gap-2 rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-600/30 transition hover:bg-red-500"
                       wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        Start Watching
                    </a>
                </div>

                @if($isOwner)
                    <button wire:click="endParty({{ $party->id }})" class="mt-3 text-sm font-medium text-red-400 transition hover:text-red-300">
                        End Party
                    </button>
                @endif
            </div>
        </div>
    </div>

@elseif($variant === 'list')
    {{-- List row (my active parties) --}}
    <div class="group flex items-center gap-4 rounded-xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.1] hover:bg-white/[0.04]">
        @if($party->poster_path)
            <div class="h-14 w-10 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                <img src="{{ $tmdb->imageUrl($party->poster_path, 'w92') }}" alt="" class="h-full w-full object-cover" loading="lazy">
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-zinc-200">{{ $party->title }}</p>
            <p class="mt-0.5 text-xs text-zinc-500">
                Code: <span class="font-mono font-bold text-red-400">{{ $party->code }}</span>
                &bull; {{ ucfirst($party->media_type) }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ $watchUrl }}"
               class="rounded-full bg-red-600/10 px-3 py-1.5 text-xs font-bold text-red-400 transition hover:bg-red-600 hover:text-white"
               wire:navigate>Watch</a>
            @if($isOwner)
                <button wire:click="endParty({{ $party->id }})" class="rounded-full bg-white/[0.04] px-3 py-1.5 text-xs font-medium text-zinc-500 transition hover:bg-red-600/10 hover:text-red-400">End</button>
            @endif
        </div>
    </div>

@else
    {{-- Compact card (dashboard, sidebar) --}}
    <a href="{{ route('watch-parties') }}" class="group block w-48 shrink-0 rounded-xl border border-white/[0.06] bg-white/[0.02] p-3 transition hover:border-white/[0.12] hover:bg-white/[0.04]" wire:navigate>
        <div class="flex items-start gap-3">
            <div class="h-16 w-11 shrink-0 overflow-hidden rounded-lg bg-zinc-800 ring-1 ring-white/[0.06]">
                @if($party->poster_path)
                    <img src="{{ $tmdb->imageUrl($party->poster_path, 'w92') }}" alt="{{ $party->title }}" class="h-full w-full object-cover" loading="lazy">
                @else
                    <div class="flex h-full items-center justify-center text-zinc-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                @endif
            </div>
            <div class="flex -space-x-1.5 pt-1">
                <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-zinc-600 to-zinc-700 text-[9px] font-bold text-white ring-2 ring-zinc-900">
                    {{ Str::initials($hostName, true) }}
                </div>
                <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-zinc-500 to-zinc-600 ring-2 ring-zinc-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197" /></svg>
                </div>
            </div>
        </div>
        <h3 class="mt-3 text-sm font-semibold text-white transition group-hover:text-red-400">{{ Str::limit($party->title, 22) }}</h3>
        <p class="mt-0.5 text-xs text-zinc-500">{{ ucfirst($party->media_type) }}</p>
    </a>
@endif
