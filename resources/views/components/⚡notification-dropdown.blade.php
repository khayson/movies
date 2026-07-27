<?php

use App\Models\UserNotification;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function notifications(): \Illuminate\Database\Eloquent\Collection
    {
        return UserNotification::where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $id): void
    {
        UserNotification::where('user_id', auth()->id())->findOrFail($id)->markAsRead();
        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: 'Notification marked as read.');
    }

    public function markAllAsRead(): void
    {
        UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: 'All notifications marked as read.');
    }

    public function deleteNotification(int $id): void
    {
        UserNotification::where('user_id', auth()->id())->findOrFail($id)->delete();
        unset($this->notifications, $this->unreadCount);
        Flux::toast(text: 'Notification deleted.');
    }
};
?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" wire:poll.15s>
    {{-- Bell trigger --}}
    <button
        @click="open = !open"
        class="relative flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white"
        title="Notifications"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if($this->unreadCount > 0)
            <span class="absolute right-1.5 top-1.5 flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-amber-500"></span>
            </span>
        @endif
    </button>

    {{-- Mobile backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-40 bg-black/50 sm:hidden"
    ></div>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        x-cloak
        class="fixed inset-x-3 top-[4.5rem] z-50 overflow-hidden rounded-xl border border-white/[0.08] bg-zinc-900 shadow-xl shadow-black/40 sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[380px]"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-white/[0.06] px-4 py-3">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-white">Notifications</h3>
                @if($this->unreadCount > 0)
                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-400">{{ $this->unreadCount }}</span>
                @endif
            </div>
            <div class="flex items-center gap-1">
                @if($this->unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        class="rounded-md px-2 py-1 text-xs font-medium text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white"
                    >
                        Mark all read
                    </button>
                @endif
                <a
                    href="{{ route('notifications') }}"
                    wire:navigate
                    @click="open = false"
                    class="rounded-md px-2 py-1 text-xs font-medium text-amber-500 transition-colors hover:bg-amber-500/10 hover:text-amber-400"
                >
                    View all
                </a>
            </div>
        </div>

        {{-- Notification list --}}
        <div class="max-h-[400px] overflow-y-auto scrollbar-hide">
            @if($this->notifications->isEmpty())
                <div class="px-4 py-10 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-2 size-8 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <p class="text-sm text-zinc-500">No notifications yet</p>
                </div>
            @else
                @foreach($this->notifications as $notification)
                    <div
                        class="group flex items-start gap-3 border-b border-white/[0.04] px-4 py-3 transition-colors last:border-0 {{ $notification->read_at ? 'bg-transparent' : 'bg-amber-500/[0.03]' }}"
                        wire:key="notif-{{ $notification->id }}"
                    >
                        {{-- Poster or icon --}}
                        @if($notification->poster_path)
                            <img
                                src="https://image.tmdb.org/t/p/w92{{ $notification->poster_path }}"
                                alt=""
                                class="mt-0.5 h-12 w-8 flex-shrink-0 rounded-md object-cover"
                            >
                        @else
                            <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full {{ $notification->read_at ? 'bg-zinc-800' : 'bg-amber-500/10' }}">
                                @if($notification->type === 'follow')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 {{ $notification->read_at ? 'text-zinc-500' : 'text-amber-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                                @elseif($notification->type === 'new_release')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 {{ $notification->read_at ? 'text-zinc-500' : 'text-amber-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 0 1 6 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25c0 .621.504 1.125 1.125 1.125M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 {{ $notification->read_at ? 'text-zinc-500' : 'text-amber-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                @endif
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] font-medium {{ $notification->read_at ? 'text-zinc-400' : 'text-zinc-200' }}">
                                {{ $notification->title }}
                            </p>
                            <p class="mt-0.5 line-clamp-2 text-xs {{ $notification->read_at ? 'text-zinc-600' : 'text-zinc-500' }}">
                                {{ $notification->message }}
                            </p>
                            <p class="mt-1 text-[11px] text-zinc-600">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                            @if(!$notification->read_at)
                                <button
                                    wire:click="markAsRead({{ $notification->id }})"
                                    class="rounded-md p-1 text-zinc-500 transition-colors hover:bg-white/[0.06] hover:text-amber-400"
                                    title="Mark as read"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </button>
                            @endif
                            <button
                                wire:click="deleteNotification({{ $notification->id }})"
                                class="rounded-md p-1 text-zinc-600 transition-colors hover:bg-white/[0.06] hover:text-red-400"
                                title="Delete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {{-- Unread indicator --}}
                        @if(!$notification->read_at)
                            <div class="mt-2 flex-shrink-0">
                                <span class="block size-1.5 rounded-full bg-amber-500"></span>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        @if($this->notifications->isNotEmpty())
            <div class="border-t border-white/[0.06] px-4 py-2.5">
                <a
                    href="{{ route('notifications') }}"
                    wire:navigate
                    @click="open = false"
                    class="flex items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-medium text-zinc-400 transition-colors hover:text-white"
                >
                    View all notifications
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
        @endif
    </div>
</div>
