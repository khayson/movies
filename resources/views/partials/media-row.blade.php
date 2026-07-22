@props(['title', 'items', 'type' => null, 'seeAllRoute' => null, 'style' => 'grid'])

<section class="mt-10">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-white">{{ $title }}</h2>
        @if($seeAllRoute)
            <a href="{{ $seeAllRoute }}" class="text-sm font-medium text-amber-500 transition hover:text-amber-400" wire:navigate>
                See All &rarr;
            </a>
        @endif
    </div>

    @if($style === 'scroll')
        <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
            @foreach(array_slice($items, 0, 20) as $item)
                <div class="w-36 shrink-0 sm:w-40 lg:w-44">
                    @include('partials.media-card', ['item' => $item, 'type' => $type])
                </div>
            @endforeach
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach(array_slice($items, 0, 12) as $item)
                @include('partials.media-card', ['item' => $item, 'type' => $type])
            @endforeach
        </div>
    @endif
</section>
