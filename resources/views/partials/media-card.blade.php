@props(['item', 'type' => null, 'showOverview' => false])

@php
    $mediaType = $item['media_type'] ?? ($type ?? 'movie');
    $itemTitle = $item['title'] ?? $item['name'] ?? 'Untitled';
    $detailRoute = $mediaType === 'tv' ? 'tv.detail' : 'movies.detail';
    $releaseDate = $item['release_date'] ?? $item['first_air_date'] ?? '';
    $year = $releaseDate ? Str::substr($releaseDate, 0, 4) : '';
    $isUpcoming = $releaseDate && $releaseDate > now()->toDateString();
    $rating = $item['vote_average'] ?? 0;
@endphp

<a href="{{ route($detailRoute, $item['id']) }}" class="group relative block" wire:navigate>
    <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800">
        @if(!empty($item['poster_path']))
            <img
                src="{{ app(\App\Services\Tmdb::class)->imageUrl($item['poster_path'], 'w342') }}"
                alt="{{ $itemTitle }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
                loading="lazy"
            >
        @else
            <div class="flex h-full items-center justify-center text-zinc-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
        @endif

        {{-- Hover overlay --}}
        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 transition-all duration-300 group-hover:opacity-100">
            <div class="p-3">
                @if($showOverview && !empty($item['overview']))
                    <p class="mb-2 line-clamp-3 text-xs leading-relaxed text-zinc-300">{{ $item['overview'] }}</p>
                @endif
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1 text-xs font-medium text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        {{ $rating > 0 ? number_format($rating, 1) : 'N/A' }}
                    </span>
                    <span class="rounded bg-zinc-700/80 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-zinc-300">{{ $mediaType }}</span>
                </div>
            </div>
        </div>

        {{-- Rating badge --}}
        @if($rating > 0)
            <div class="absolute right-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-xs font-bold tabular-nums text-amber-400 backdrop-blur-sm transition-opacity duration-300 group-hover:opacity-0">
                {{ number_format($rating, 1) }}
            </div>
        @endif

        {{-- Upcoming badge --}}
        @if($isUpcoming)
            <div class="absolute left-2 top-2 rounded-md bg-amber-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                Coming Soon
            </div>
        @endif

        {{-- Play button on hover --}}
        <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <div class="flex size-12 items-center justify-center rounded-full bg-amber-600/90 text-white shadow-lg shadow-amber-600/30 backdrop-blur-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </div>
        </div>
    </div>

    <div class="mt-2.5">
        <h3 class="text-sm font-medium text-zinc-300 transition group-hover:text-white">{{ Str::limit($itemTitle, 28) }}</h3>
        <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
            @if($year)
                <span>{{ $year }}</span>
            @endif
            @if($isUpcoming && $releaseDate)
                <span class="text-amber-500">{{ \Carbon\Carbon::parse($releaseDate)->format('M d') }}</span>
            @endif
        </div>
    </div>
</a>
