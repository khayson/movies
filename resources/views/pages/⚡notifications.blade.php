<?php

use App\Models\UserNotification;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.guest')]
#[Title('Notifications — StreamVault')]
class extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
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

        Flux::toast(variant: 'success', text: 'Notification marked as read.');
    }

    public function markAllAsRead(): void
    {
        if (auth()->check()) {
            UserNotification::where('user_id', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $allIds = UserNotification::forVisitor(auth()->id())->pluck('id')->all();
        $read = array_unique(array_merge(session('read_notifications', []), $allIds));
        session(['read_notifications' => $read]);

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

        Flux::toast(text: 'Notification removed.');
    }

    public function with(): array
    {
        $dismissed = session('dismissed_notifications', []);
        $readIds = session('read_notifications', []);

        $query = UserNotification::forVisitor(auth()->id())->latest();

        if (! empty($dismissed)) {
            $query->whereNotIn('id', $dismissed);
        }

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
            if (! empty($readIds)) {
                $query->whereNotIn('id', $readIds);
            }
        } elseif ($this->filter === 'announcements') {
            $query->where('type', 'announcement');
        } elseif ($this->filter === 'releases') {
            $query->where('type', 'new_release');
        } elseif ($this->filter === 'upcoming') {
            $query->whereIn('type', ['upcoming', 'now_playing']);
        } elseif ($this->filter === 'recommendations') {
            $query->where('type', 'recommendation');
        } elseif ($this->filter === 'account') {
            $query->where('type', 'account');
        }

        $notifications = $query->paginate(15);

        $unreadQuery = UserNotification::forVisitor(auth()->id())->whereNull('read_at');
        if (! empty($readIds)) {
            $unreadQuery->whereNotIn('id', $readIds);
        }
        if (! empty($dismissed)) {
            $unreadQuery->whereNotIn('id', $dismissed);
        }
        $unreadCount = $unreadQuery->count();

        return [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ];
    }
};
?>

<div wire:poll.15s class="min-h-screen py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Notifications</h1>
                    @if($unreadCount > 0)
                        <span class="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-400">
                            {{ $unreadCount }} new
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-zinc-400">
                    Stay up-to-date with new movie releases, watch parties, community updates, and announcements.
                </p>
            </div>

            @if($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/[0.08] bg-white/[0.04] px-4 py-2 text-sm font-medium text-zinc-300 transition-colors hover:bg-white/[0.08] hover:text-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Mark all as read
                </button>
            @endif
        </div>

        {{-- Guest Callout Banner --}}
        @guest
            <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-center gap-3.5">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-white">Viewing Public Announcements & Releases</h2>
                        <p class="text-xs text-zinc-400">Sign in to unlock personalized notifications for new episodes, friends' reviews, and watch party invites.</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('login') }}" class="rounded-xl px-3.5 py-2 text-xs font-medium text-zinc-300 transition-colors hover:bg-white/[0.06] hover:text-white" wire:navigate>
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:from-amber-500 hover:to-amber-600" wire:navigate>
                        Get Started
                    </a>
                </div>
            </div>
        @endguest

        {{-- Filter Tabs --}}
        <div class="mb-6 flex items-center gap-1.5 overflow-x-auto border-b border-white/[0.06] pb-3 text-sm">
            @foreach(['all' => 'All', 'unread' => 'Unread', 'upcoming' => 'Upcoming', 'recommendations' => 'For You', 'releases' => 'New Releases', 'announcements' => 'Announcements', 'account' => 'Account'] as $key => $label)
                <button
                    wire:click="setFilter('{{ $key }}')"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-medium transition-colors {{ $filter === $key ? 'bg-amber-500/15 text-amber-400' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Notifications List --}}
        @if($notifications->isEmpty())
            <div class="rounded-2xl border border-white/[0.06] bg-zinc-900/30 py-16 text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-800/60 text-zinc-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-zinc-300">No notifications found</h3>
                <p class="mt-1 text-sm text-zinc-500">
                    @if($filter !== 'all')
                        No notifications match the "{{ $filter }}" filter.
                    @else
                        Check back soon for new announcements, releases, and platform updates.
                    @endif
                </p>
                @if($filter !== 'all')
                    <button wire:click="setFilter('all')" class="mt-4 text-xs font-medium text-amber-500 transition hover:text-amber-400">
                        Reset filter
                    </button>
                @endif
            </div>
        @else
            <div class="space-y-2.5">
                @foreach($notifications as $notification)
                    @php
                        $isRead = (bool) $notification->read_at || in_array($notification->id, session('read_notifications', []), true);
                        $targetUrl = $notification->link ?: ($notification->tmdb_id && $notification->media_type ? route($notification->media_type === 'movie' ? 'movies.detail' : 'tv.detail', $notification->tmdb_id) : null);
                    @endphp
                    <div
                        wire:key="page-notif-{{ $notification->id }}"
                        class="group relative flex items-start gap-4 rounded-2xl border p-4 transition-all {{ $isRead ? 'border-white/[0.06] bg-zinc-900/30' : 'border-amber-500/25 bg-amber-500/[0.03] shadow-lg shadow-amber-500/[0.02]' }}"
                    >
                        {{-- Poster or Icon Thumbnail --}}
                        @if($notification->poster_path)
                            @if($targetUrl)
                                <a href="{{ $targetUrl }}" wire:navigate class="shrink-0">
                                    <img
                                        src="https://image.tmdb.org/t/p/w92{{ $notification->poster_path }}"
                                        alt=""
                                        class="h-16 w-11 shrink-0 rounded-xl object-cover ring-1 ring-white/[0.08] transition group-hover:scale-105"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <img
                                    src="https://image.tmdb.org/t/p/w92{{ $notification->poster_path }}"
                                    alt=""
                                    class="h-16 w-11 shrink-0 rounded-xl object-cover ring-1 ring-white/[0.08]"
                                    loading="lazy"
                                >
                            @endif
                        @else
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $isRead ? 'bg-zinc-800 text-zinc-500' : 'bg-amber-500/15 text-amber-400' }}">
                                @if($notification->type === 'follow')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                                @elseif($notification->type === 'new_release')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                @elseif($notification->type === 'watch_party')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                                @elseif($notification->type === 'trending')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                @elseif($notification->type === 'announcement')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.063.04-.125.08-.188.117-1.127.677-2.33 1.115-3.57 1.298a.75.75 0 0 1-.856-.634c-.11-.745-.16-1.5-.16-2.261 0-1.895.312-3.714.887-5.412a.75.75 0 0 1 .792-.516c1.238.169 2.434.586 3.551 1.238.07.04.138.083.206.126m4.354 1.155a6.002 6.002 0 0 1-2.25 4.398m2.25-4.398a6.002 6.002 0 0 0-2.25-4.398m2.25 4.398h2.096c.621 0 1.125.504 1.125 1.125v.75c0 .621-.504 1.125-1.125 1.125H14.7" /></svg>
                                @elseif($notification->type === 'upcoming')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                @elseif($notification->type === 'now_playing')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-2.625 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 0 1 6 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" /></svg>
                                @elseif($notification->type === 'recommendation')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" /></svg>
                                @elseif($notification->type === 'watchlist_update')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                                @elseif($notification->type === 'account')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                                @endif
                            </div>
                        @endif

                        {{-- Details --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($targetUrl)
                                            <a
                                                href="{{ $targetUrl }}"
                                                wire:navigate
                                                class="text-sm font-semibold text-white transition hover:text-amber-400"
                                            >
                                                {{ $notification->title }}
                                            </a>
                                        @else
                                            <p class="text-sm font-semibold text-white">{{ $notification->title }}</p>
                                        @endif

                                        @if(!$notification->user_id)
                                            <span class="rounded-md bg-white/[0.06] px-1.5 py-0.5 text-[10px] font-medium text-zinc-400">
                                                Public
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm leading-relaxed {{ $isRead ? 'text-zinc-400' : 'text-zinc-300' }}">
                                        {{ $notification->message }}
                                    </p>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex shrink-0 items-center gap-1">
                                    @if(!$isRead)
                                        <button
                                            wire:click="markAsRead({{ $notification->id }})"
                                            class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-amber-400"
                                            title="Mark as read"
                                            aria-label="Mark as read"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </button>
                                    @endif
                                    <button
                                        wire:click="deleteNotification({{ $notification->id }})"
                                        class="rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-white/[0.06] hover:text-red-400"
                                        title="Delete"
                                        aria-label="Delete notification"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-2.5 flex items-center gap-3 text-xs text-zinc-500">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                @if($targetUrl)
                                    <span class="text-zinc-700">&bull;</span>
                                    <a href="{{ $targetUrl }}" wire:navigate class="inline-flex items-center gap-1 font-medium text-amber-500 transition hover:text-amber-400">
                                        View details
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Glow indicator on the left for unread --}}
                        @if(!$isRead)
                            <div class="absolute -left-px top-4 bottom-4 w-1 rounded-r bg-amber-500 shadow-sm shadow-amber-500/80"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
