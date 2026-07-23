<?php

use App\Models\UserNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Notifications — StreamVault')]
class extends Component
{
    public function markAsRead(int $id): void
    {
        $notification = UserNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteNotification(int $id): void
    {
        UserNotification::where('user_id', auth()->id())->findOrFail($id)->delete();
    }

    public function with(): array
    {
        $notifications = UserNotification::where('user_id', auth()->id())
            ->latest()
            ->limit(50)
            ->get();

        $unreadCount = UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Notifications</h1>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700">
                    Mark all as read ({{ $unreadCount }})
                </button>
            @endif
        </div>

        @if($notifications->isEmpty())
            <div class="py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 size-12 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <p class="text-zinc-500">No notifications yet</p>
                <p class="mt-1 text-sm text-zinc-600">When someone follows you or interacts with your content, you'll see it here.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($notifications as $notification)
                    <div class="flex items-start gap-4 rounded-xl border {{ $notification->read_at ? 'border-zinc-800/50 bg-zinc-900/30' : 'border-amber-800/30 bg-amber-900/5' }} p-4 transition">
                        @if($notification->poster_path)
                            <img src="https://image.tmdb.org/t/p/w92{{ $notification->poster_path }}"
                                 alt="" class="h-16 w-11 flex-shrink-0 rounded-lg object-cover">
                        @else
                            <div class="flex h-16 w-11 flex-shrink-0 items-center justify-center rounded-lg bg-zinc-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-200">{{ $notification->title }}</p>
                                    <p class="mt-0.5 text-sm text-zinc-400">{{ $notification->message }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    @if(!$notification->read_at)
                                        <button wire:click="markAsRead({{ $notification->id }})" class="rounded-md p-1.5 text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-300" title="Mark as read">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </button>
                                    @endif
                                    <button wire:click="deleteNotification({{ $notification->id }})" class="rounded-md p-1.5 text-zinc-600 transition hover:bg-zinc-800 hover:text-red-400" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-zinc-600">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
