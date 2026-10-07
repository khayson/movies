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
        $dismissed = session('dismissed_notifications', []);

        $query = UserNotification::forVisitor(auth()->id())
            ->latest()
            ->limit(10);

        if (! empty($dismissed)) {
            $query->whereNotIn('id', $dismissed);
        }

        return $query->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        $readIds = session('read_notifications', []);
        $dismissed = session('dismissed_notifications', []);

        $query = UserNotification::forVisitor(auth()->id())
            ->whereNull('read_at');

        if (! empty($readIds)) {
            $query->whereNotIn('id', $readIds);
        }

        if (! empty($dismissed)) {
            $query->whereNotIn('id', $dismissed);
        }

        return $query->count();
    }

    public function markAsRead(int $id): void
    {
        $notification = UserNotification::find($id);

        if ($notification) {
            if (auth()->check() && $notification->user_id === auth()->id()) {
                $notification->markAsRead();
            } else {
                $read = session('read_notifications', []);
                if (! in_array($id, $read, true)) {
                    $read[] = $id;
                    session(['read_notifications' => $read]);
                }
            }
        }

        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: 'Notification marked as read.');
    }

    public function markAllAsRead(): void
    {
        if (auth()->check()) {
            UserNotification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $allIds = $this->notifications->pluck('id')->all();
        $read = array_unique(array_merge(session('read_notifications', []), $allIds));
        session(['read_notifications' => $read]);

        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: 'All notifications marked as read.');
    }

    public function deleteNotification(int $id): void
    {
        $notification = UserNotification::find($id);

        if ($notification && auth()->check() && $notification->user_id === auth()->id()) {
            $notification->delete();
        } else {
            $dismissed = session('dismissed_notifications', []);
            if (! in_array($id, $dismissed, true)) {
                $dismissed[] = $id;
                session(['dismissed_notifications' => $dismissed]);
            }
        }

        unset($this->notifications, $this->unreadCount);
        Flux::toast(text: 'Notification removed.');
    }
};
?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" wire:poll.15s>
    {{-- Bell trigger --}}
    <button
        @click="open = !open"
        class="relative flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white"
        title="Notifications"
        aria-label="Open notifications"
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
        class="absolute right-0 top-full z-50 mt-2 w-[min(calc(100vw-1.5rem),400px)] overflow-hidden rounded-xl border border-white/[0.08] bg-zinc-900 shadow-xl shadow-black/40"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-white/[0.06] px-4 py-3">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-white">Notifications</h3>
                @if($this->unreadCount > 0)
                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-400">{{ $this->unreadCount }} new</span>
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
        <div class="max-h-[420px] overflow-y-auto scrollbar-hide">
            @if($this->notifications->isEmpty())
                <div class="px-4 py-10 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-2 size-8 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <p class="text-sm text-zinc-500">No notifications yet</p>
                </div>
            @else
                @foreach($this->notifications as $notification)
                    @php
                        $isRead = (bool) $notification->read_at || in_array($notification->id, session('read_notifications', []), true);
                        $targetUrl = $notification->link ?: ($notification->tmdb_id && $notification->media_type ? route($notification->media_type === 'movie' ? 'movies.detail' : 'tv.detail', $notification->tmdb_id) : null);
                    @endphp
                    <div
                        class="group flex items-start gap-3 border-b border-white/[0.04] px-4 py-3 transition-colors last:border-0 {{ $isRead ? 'bg-transparent' : 'bg-amber-500/[0.04]' }}"
                        wire:key="dropdown-notif-{{ $notification->id }}"
                    >
                        {{-- Poster or Icon --}}
                        @if($notification->poster_path)
                            <img
                                src="https://image.tmdb.org/t/p/w92{{ $notification->poster_path }}"
                                alt=""
                                class="mt-0.5 h-12 w-8 shrink-0 rounded-md object-cover ring-1 ring-white/[0.06]"
                                loading="lazy"
                            >
                        @else
                            <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $isRead ? 'bg-zinc-800 text-zinc-500' : 'bg-amber-500/15 text-amber-400' }}">
                                @if($notification->type === 'follow')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                                @elseif($notification->type === 'new_release')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                @elseif($notification->type === 'watch_party')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                                @elseif($notification->type === 'trending')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                @elseif($notification->type === 'announcement')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.063.04-.125.08-.188.117-1.127.677-2.33 1.115-3.57 1.298a.75.75 0 0 1-.856-.634c-.11-.745-.16-1.5-.16-2.261 0-1.895.312-3.714.887-5.412a.75.75 0 0 1 .792-.516c1.238.169 2.434.586 3.551 1.238.07.04.138.083.206.126m4.354 1.155a6.002 6.002 0 0 1-2.25 4.398m2.25-4.398a6.002 6.002 0 0 0-2.25-4.398m2.25 4.398h2.096c.621 0 1.125.504 1.125 1.125v.75c0 .621-.504 1.125-1.125 1.125H14.7" /></svg>
                                @elseif($notification->type === 'upcoming')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                @elseif($notification->type === 'now_playing')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-2.625 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 0 1 6 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" /></svg>
                                @elseif($notification->type === 'recommendation')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                                @elseif($notification->type === 'watchlist_update')
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                    @elseif($notification->type === 'account')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                @endif
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            @if($targetUrl)
                                <a
                                    href="{{ $targetUrl }}"
                                    wire:navigate
                                    @click="open = false"
                                    class="text-[13px] font-medium leading-snug transition-colors hover:text-amber-400 {{ $isRead ? 'text-zinc-300' : 'text-white' }}"
                                >
                                    {{ $notification->title }}
                                </a>
                            @else
                                <p class="text-[13px] font-medium leading-snug {{ $isRead ? 'text-zinc-300' : 'text-white' }}">
                                    {{ $notification->title }}
                                </p>
                            @endif

                            <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed {{ $isRead ? 'text-zinc-500' : 'text-zinc-400' }}">
                                {{ $notification->message }}
                            </p>
                            <p class="mt-1 text-[11px] text-zinc-500">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                            @if(!$isRead)
                                <button
                                    wire:click="markAsRead({{ $notification->id }})"
                                    class="rounded-md p-1 text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-amber-400"
                                    title="Mark as read"
                                    aria-label="Mark as read"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                </button>
                            @endif
                            <button
                                wire:click="deleteNotification({{ $notification->id }})"
                                class="rounded-md p-1 text-zinc-500 transition-colors hover:bg-white/[0.06] hover:text-red-400"
                                title="Dismiss"
                                aria-label="Dismiss notification"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {{-- Unread indicator --}}
                        @if(!$isRead)
                            <div class="mt-2 shrink-0">
                                <span class="block size-1.5 rounded-full bg-amber-500 shadow-sm shadow-amber-500/50"></span>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        <div class="border-t border-white/[0.06] px-4 py-2.5 bg-zinc-950/40">
            <div class="flex items-center justify-between">
                @guest
                    <span class="text-[11px] text-zinc-500">Public updates & news</span>
                @else
                    <span class="text-[11px] text-zinc-500">Activity & alerts</span>
                @endguest
                <a
                    href="{{ route('notifications') }}"
                    wire:navigate
                    @click="open = false"
                    class="inline-flex items-center gap-1 text-xs font-medium text-amber-500 transition-colors hover:text-amber-400"
                >
                    View all notifications
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
        </div>
    </div>
</div>
