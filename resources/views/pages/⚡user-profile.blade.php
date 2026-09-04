<?php

use App\Models\Conversation;
use App\Models\Follow;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BadgeService;
use App\Services\StreakService;
use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
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

    public function toggleFollow(): void
    {
        $user = auth()->user();
        if (!$user || $user->id === $this->userId) {
            return;
        }

        $existing = Follow::where('follower_id', $user->id)
            ->where('following_id', $this->userId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            Follow::create([
                'follower_id' => $user->id,
                'following_id' => $this->userId,
            ]);

            $targetUser = User::find($this->userId);
            if ($targetUser) {
                $logger = app(ActivityLogger::class);
                $activity = $logger->log($user, 'follow', "followed {$targetUser->name}");
            }
        }
    }

    public function sendMessage(): void
    {
        $user = auth()->user();
        if (!$user || $user->id === $this->userId) {
            return;
        }

        $conversation = Conversation::findOrStartBetween($user->id, $this->userId);
        $this->redirect(route('messages.thread', $conversation->id), navigate: true);
    }

    public function with(StreakService $streakService, BadgeService $badgeService): array
    {
        $user = User::findOrFail($this->userId);

        View::share('ogTitle', $user->name . ' — ' . config('app.name'));
        View::share('ogDescription', $user->name . ' has watched ' . $user->watchHistory()->visible()->count() . ' titles and written ' . $user->reviews()->count() . ' reviews on ' . config('app.name') . '.');
        View::share('ogType', 'profile');

        $badgeService->checkAndAward($user);

        $streak = $streakService->calculate($user);

        $badges = $user->badges()->latest('earned_at')->get();

        $recentReviews = $user->reviews()->with('user')->latest()->limit(5)->get();

        $publicCollections = $user->collections()->where('is_public', true)->withCount('items')->latest()->limit(6)->get();

        $stats = [
            'watched' => $user->watchHistory()->visible()->count(),
            'reviews' => $user->reviews()->count(),
            'favorites' => $user->favorites()->count(),
            'collections' => $user->collections()->count(),
        ];

        $isFollowing = auth()->check() && auth()->user()->isFollowing($user);
        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();

        return [
            'profileUser' => $user,
            'streak' => $streak,
            'badges' => $badges,
            'recentReviews' => $recentReviews,
            'publicCollections' => $publicCollections,
            'stats' => $stats,
            'isFollowing' => $isFollowing,
            'followersCount' => $followersCount,
            'followingCount' => $followingCount,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-amber-950/15 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-5xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <div class="flex size-24 items-center justify-center rounded-2xl bg-amber-600/20 text-3xl font-bold text-amber-400 ring-2 ring-amber-500/20">
                    {{ Str::upper(Str::substr($profileUser->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tight md:text-4xl">{{ $profileUser->name }}</h1>
                    </div>
                    <p class="mt-1 text-sm text-zinc-500">Joined {{ $profileUser->created_at->format('M Y') }}</p>
                    <div class="mt-2 flex items-center gap-4 text-sm text-zinc-400">
                        <span><strong class="text-zinc-200">{{ $followersCount }}</strong> {{ Str::plural('follower', $followersCount) }}</span>
                        <span><strong class="text-zinc-200">{{ $followingCount }}</strong> following</span>
                    </div>
                </div>
                @auth
                    @if(auth()->id() !== $profileUser->id)
                        <div class="flex items-center gap-2">
                            <button wire:click="sendMessage"
                                    class="rounded-xl border border-white/[0.08] bg-white/[0.03] px-4 py-2.5 text-sm font-semibold text-zinc-300 transition hover:border-blue-500/30 hover:text-blue-400">
                                Message
                            </button>
                            <button wire:click="toggleFollow"
                                    class="rounded-xl px-6 py-2.5 text-sm font-semibold transition {{ $isFollowing ? 'border border-white/[0.08] bg-white/[0.03] text-zinc-300 hover:border-red-500/30 hover:text-red-400' : 'bg-amber-600 text-white shadow-lg shadow-amber-600/20 hover:bg-amber-500' }}">
                                {{ $isFollowing ? 'Unfollow' : 'Follow' }}
                            </button>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Stats --}}
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach([['watched', $stats['watched'], 'Watched'], ['reviews', $stats['reviews'], 'Reviews'], ['favorites', $stats['favorites'], 'Favorites'], ['streak', $streak['current'], 'Day Streak']] as [$key, $value, $label])
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 text-center">
                    <p class="text-3xl font-bold tabular-nums text-amber-400">{{ $value }}</p>
                    <p class="mt-1 text-xs font-medium text-zinc-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- Watch Streak Calendar --}}
        @if(count($streak['active_days']) > 0)
            <section class="mb-8">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <span class="h-5 w-1 rounded-full bg-green-500"></span>
                    Watch Activity (Last 90 Days)
                </h2>
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4">
                    <div class="flex flex-wrap gap-1">
                        @for($i = 89; $i >= 0; $i--)
                            @php $day = now()->subDays($i)->format('Y-m-d'); @endphp
                            <div class="size-3 rounded-sm {{ in_array($day, $streak['active_days']) ? 'bg-amber-500' : 'bg-white/[0.04]' }}"
                                 title="{{ $day }}"></div>
                        @endfor
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs text-zinc-500">
                        <span>Current streak: <strong class="text-zinc-300">{{ $streak['current'] }}</strong> {{ Str::plural('day', $streak['current']) }}</span>
                        <span>Longest streak: <strong class="text-zinc-300">{{ $streak['longest'] }}</strong> {{ Str::plural('day', $streak['longest']) }}</span>
                    </div>
                </div>
            </section>
        @endif

        {{-- Badges --}}
        @if($badges->count() > 0)
            <section class="mb-8">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                    Badges
                </h2>
                <div class="flex flex-wrap gap-3">
                    @foreach($badges as $badge)
                        @php $def = $badge->definition(); @endphp
                        @if($def)
                            <div class="flex items-center gap-2 rounded-xl border border-white/[0.06] bg-white/[0.02] px-4 py-2.5" title="{{ $def['description'] }}">
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
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <span class="h-5 w-1 rounded-full bg-blue-500"></span>
                    Recent Reviews
                </h2>
                <div class="space-y-3">
                    @foreach($recentReviews as $review)
                        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4">
                            <div class="mb-1 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-zinc-200">{{ $review->title }}</h3>
                                <div class="flex items-center gap-1 rounded-lg bg-amber-500/10 px-2.5 py-1 text-xs font-bold text-amber-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ $review->rating }}/10
                                </div>
                            </div>
                            @if($review->body)
                                <p class="text-sm text-zinc-400">{{ Str::limit($review->body, 200) }}</p>
                            @endif
                            <p class="mt-1.5 text-xs text-zinc-600">{{ $review->created_at->diffForHumans() }} &middot; {{ ucfirst($review->media_type) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Public Collections --}}
        @if($publicCollections->count() > 0)
            <section>
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <span class="h-5 w-1 rounded-full bg-purple-500"></span>
                    Collections
                </h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($publicCollections as $collection)
                        <a href="{{ route('collections.show', $collection->slug) }}"
                           class="rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4 transition hover:border-white/[0.12] hover:bg-white/[0.04]" wire:navigate>
                            <h3 class="font-medium text-zinc-200">{{ $collection->name }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">{{ $collection->items_count }} {{ Str::plural('title', $collection->items_count) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
