@props([
    'title',
    'items',
    'type' => null,
    'seeAllRoute' => null,
    'style' => 'grid',
    'showOverview' => false,
    'variant' => 'default',
    'removable' => false,
    'removeAction' => null,
])

<section class="mt-10">
    <div class="mb-4 flex items-center gap-3">
        <h2 class="text-xl font-bold tracking-tight text-white">{{ $title }}</h2>
        <span class="size-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
        <div class="h-px flex-1 bg-gradient-to-r from-white/[0.06] to-transparent"></div>
        @if($seeAllRoute)
            <a href="{{ $seeAllRoute }}" class="text-sm font-medium text-zinc-500 transition hover:text-white" wire:navigate>
                See All
            </a>
        @endif
    </div>

    @if($style === 'scroll')
        <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
            @foreach(array_slice($items, 0, 20) as $item)
                <div class="w-36 shrink-0 sm:w-40 lg:w-44">
                    @include('partials.media-card', [
                        'item' => $item,
                        'type' => $type,
                        'showOverview' => $showOverview,
                        'variant' => $variant,
                        'removable' => $removable,
                        'removeAction' => $removeAction,
                        'removeId' => $removable && $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : null,
                    ])
                </div>
            @endforeach
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach(array_slice($items, 0, 12) as $item)
                @include('partials.media-card', [
                    'item' => $item,
                    'type' => $type,
                    'showOverview' => $showOverview,
                    'variant' => $variant,
                    'removable' => $removable,
                    'removeAction' => $removeAction,
                    'removeId' => $removable && $item instanceof \Illuminate\Database\Eloquent\Model ? $item->id : null,
                ])
            @endforeach
        </div>
    @endif
</section>
