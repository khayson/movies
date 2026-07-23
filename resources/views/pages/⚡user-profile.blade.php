<?php

use App\Models\User;
use App\Services\BadgeService;
use App\Services\StreakService;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function with(StreakService $streakService, BadgeService $badgeService): array
    {
        $user = User::findOrFail($this->userId);

        $badgeService->checkAndAward($user);

        $streak = $streakService->calculate($user);

        $badges = $user->badges()->latest('earned_at')->get();

        $recentReviews = $user->reviews()->with('user')->latest()->limit(5)->get();

        $publicCollections = $user->collections()->where('is_public', true)->withCount('items')->latest()->limit(6)->get();

        $stats = [
            'watched' => $user->watchHistory()->count(),
            'reviews' => $user->reviews()->count(),
            'favorites' => $user->favorites()->count(),
            'collections' => $user->collections()->count(),
        ];

        return [
            'profileUser' => $user,
            'streak' => $streak,
            'badges' => $badges,
            'recentReviews' => $recentReviews,
            'publicCollections' => $publicCollections,
            'stats' => $stats,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Profile Header --}}
        <div class="mb-8 flex items-center gap-6">
            <div class="flex size-20 items-center justify-center rounded-full bg-amber-600/20 text-2xl font-bold text-amber-400">
                {{ Str::upper(Str::substr($profileUser->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-3xl font-bold">{{ $profileUser->name }}</h1>
                <p class="text-sm text-zinc-500">Joined {{ $profileUser->created_at->format('M Y') }}</p>
            </div>
        </div>

        {{-- Stats --}}
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 text-center">
                <p class="text-2xl font-bold text-amber-400">{{ $stats['watched'] }}</p>
                <p class="text-xs text-zinc-500">Watched</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 text-center">
                <p class="text-2xl font-bold text-amber-400">{{ $stats['reviews'] }}</p>
                <p class="text-xs text-zinc-500">Reviews</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 text-center">
                <p class="text-2xl font-bold text-amber-400">{{ $stats['favorites'] }}</p>
                <p class="text-xs text-zinc-500">Favorites</p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 text-center">
                <p class="text-2xl font-bold text-amber-400">{{ $streak['current'] }}</p>
                <p class="text-xs text-zinc-500">Day Streak</p>
            </div>
        </div>

        {{-- Watch Streak Calendar --}}
        @if(count($streak['active_days']) > 0)
            <section class="mb-8">
                <h2 class="mb-4 text-lg font-semibold">Watch Activity (Last 90 Days)</h2>
                <div class="flex flex-wrap gap-1">
                    @for($i = 89; $i >= 0; $i--)
                        @php $day = now()->subDays($i)->format('Y-m-d'); @endphp
                        <div class="size-3 rounded-sm {{ in_array($day, $streak['active_days']) ? 'bg-amber-500' : 'bg-zinc-800' }}"
                             title="{{ $day }}"></div>
                    @endfor
                </div>
                <div class="mt-2 flex items-center gap-4 text-xs text-zinc-500">
                    <span>Current streak: {{ $streak['current'] }} {{ Str::plural('day', $streak['current']) }}</span>
                    <span>Longest streak: {{ $streak['longest'] }} {{ Str::plural('day', $streak['longest']) }}</span>
                </div>
            </section>
        @endif

        {{-- Badges --}}
        @if($badges->count() > 0)
            <section class="mb-8">
                <h2 class="mb-4 text-lg font-semibold">Badges</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach($badges as $badge)
                        @php $def = $badge->definition(); @endphp
                        @if($def)
                            <div class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-4 py-2" title="{{ $def['description'] }}">
                                <span class="text-xl">{{ $def['icon'] }}</span>
                                <div>
                                    <p class="text-sm font-medium text-zinc-200">{{ $def['name'] }}</p>
                                    <p class="text-xs text-zinc-500">{{ $badge->earned_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Recent Reviews --}}
        @if($recentReviews->count() > 0)
            <section class="mb-8">
                <h2 class="mb-4 text-lg font-semibold">Recent Reviews</h2>
                <div class="space-y-3">
                    @foreach($recentReviews as $review)
                        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
                            <div class="mb-1 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-zinc-200">{{ $review->title }}</h3>
                                <div class="flex items-center gap-1 rounded-md bg-amber-600/10 px-2 py-0.5 text-xs font-bold text-amber-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ $review->rating }}/10
                                </div>
                            </div>
                            @if($review->body)
                                <p class="text-sm text-zinc-400">{{ Str::limit($review->body, 200) }}</p>
                            @endif
                            <p class="mt-1 text-xs text-zinc-600">{{ $review->created_at->diffForHumans() }} &middot; {{ ucfirst($review->media_type) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Public Collections --}}
        @if($publicCollections->count() > 0)
            <section class="mb-8">
                <h2 class="mb-4 text-lg font-semibold">Collections</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($publicCollections as $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4 transition hover:border-zinc-700" wire:navigate>
                            <h3 class="font-medium text-zinc-200">{{ $collection->name }}</h3>
                            <p class="text-xs text-zinc-500">{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
