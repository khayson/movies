@props(['title', 'items', 'type' => 'movie'])

<section class="mt-12">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="h-6 w-1 rounded-full bg-amber-500"></div>
            <h2 class="text-xl font-bold text-white">{{ $title }}</h2>
            <span class="rounded-full bg-amber-600/20 px-2.5 py-0.5 text-xs font-semibold text-amber-400">Coming Soon</span>
        </div>
        <a href="{{ route('upcoming.index') }}" class="text-sm font-medium text-amber-500 transition hover:text-amber-400" wire:navigate>
            See All &rarr;
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
                <div class="relative aspect-video overflow-hidden rounded-xl bg-zinc-800">
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
                            <span class="mb-1 inline-block rounded bg-amber-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                                {{ \Carbon\Carbon::parse($releaseDate)->format('M d, Y') }}
                            </span>
                        @endif
                    </div>

                    {{-- Play trailer button --}}
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <div class="rounded-full bg-white/20 px-4 py-2 text-xs font-semibold text-white backdrop-blur-sm">
                            Watch Trailer
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
