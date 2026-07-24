@props(['title', 'items', 'type' => 'movie'])

<section class="mt-12">
    <div class="mb-6 flex items-center gap-3">
        <h2 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h2>
        <span class="rounded-full bg-red-600/20 px-2.5 py-0.5 text-xs font-bold text-red-400">Coming Soon</span>
        <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
        <a href="{{ route('upcoming.index') }}" class="text-sm font-medium text-zinc-500 transition hover:text-white" wire:navigate>
            See All
        </a>
    </div>

    <div class="scrollbar-hide -mx-4 flex gap-5 overflow-x-auto px-4 pb-4">
        @foreach(array_slice($items, 0, 10) as $item)
            @php
                $itemTitle = $item['title'] ?? $item['name'] ?? 'Untitled';
                $releaseDate = $item['release_date'] ?? $item['first_air_date'] ?? '';
                $mediaType = $item['media_type'] ?? $type;
                $detailRoute = $mediaType === 'tv' ? 'tv.detail' : 'movies.detail';
            @endphp
            <a href="{{ route($detailRoute, $item['id']) }}" class="group w-56 shrink-0 sm:w-64" wire:navigate>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-white/[0.06] bg-zinc-800">
                    @if(!empty($item['backdrop_path']))
                        <img
                            src="{{ app(\App\Services\Tmdb::class)->imageUrl($item['backdrop_path'], 'w780') }}"
                            alt="{{ $itemTitle }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
                            loading="lazy"
                        >
                    @elseif(!empty($item['poster_path']))
                        <img
                            src="{{ app(\App\Services\Tmdb::class)->imageUrl($item['poster_path'], 'w500') }}"
                            alt="{{ $itemTitle }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
                            loading="lazy"
                        >
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-3">
                        @if($releaseDate)
                            @if($releaseDate > now()->toDateString())
                                <span class="mb-1 inline-block rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                                    {{ \Carbon\Carbon::parse($releaseDate)->format('M d, Y') }}
                                </span>
                            @else
                                <span class="mb-1 inline-block rounded bg-green-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                                    Now Available
                                </span>
                            @endif
                        @endif
                    </div>

                    <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <div class="flex size-10 items-center justify-center rounded-full bg-red-600/90 text-white shadow-lg shadow-red-600/30 backdrop-blur-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                </div>
                <h3 class="mt-2 text-sm font-semibold text-zinc-200 transition group-hover:text-white">{{ Str::limit($itemTitle, 35) }}</h3>
                @if(!empty($item['overview']))
                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-zinc-500">{{ $item['overview'] }}</p>
                @endif
            </a>
        @endforeach
    </div>
</section>
