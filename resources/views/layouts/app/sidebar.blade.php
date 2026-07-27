<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            .sidebar-glass { background: rgba(9, 9, 11, 0.85); backdrop-filter: blur(20px) saturate(1.8); -webkit-backdrop-filter: blur(20px) saturate(1.8); }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased" x-data="{ sidebarOpen: false }">
        <div class="flex h-screen overflow-hidden">
            {{-- Mobile sidebar backdrop --}}
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-black/60 lg:hidden"
            ></div>

            {{-- Sidebar (sticky via flex + h-screen on parent) --}}
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="sidebar-glass fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-white/[0.06] transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
            >
                {{-- Logo --}}
                <div class="flex h-16 shrink-0 items-center gap-2.5 px-5">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2.5" wire:navigate>
                        <div class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-700 shadow-lg shadow-amber-600/20 transition group-hover:shadow-amber-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <span class="text-base font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                    </a>
                    <button @click="sidebarOpen = false" class="ml-auto rounded-lg p-1 text-zinc-500 hover:text-white lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Nav section --}}
                <nav class="flex-1 overflow-y-auto scrollbar-hide px-3 pt-6">
                    <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-widest text-zinc-600">News Feed</p>
                    <div class="space-y-1">
                        {{-- Browse --}}
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                           wire:navigate>
                            @if(request()->routeIs('dashboard'))
                                <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-600/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                                </span>
                            @else
                                <span class="flex size-9 items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" /></svg>
                                </span>
                            @endif
                            <span class="{{ request()->routeIs('dashboard') ? 'font-semibold' : '' }}">Browse</span>
                        </a>

                        {{-- Watchlist --}}
                        <a href="{{ route('dashboard') }}#watchlist"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-400 transition-all hover:bg-white/[0.04] hover:text-white"
                           wire:navigate>
                            <span class="flex size-9 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                            </span>
                            Watchlist
                        </a>

                        {{-- Trending --}}
                        <a href="{{ route('trending') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('trending') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                           wire:navigate>
                            @if(request()->routeIs('trending'))
                                <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-red-600 shadow-lg shadow-red-600/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                </span>
                            @else
                                <span class="flex size-9 items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                                </span>
                            @endif
                            <span class="{{ request()->routeIs('trending') ? 'font-semibold' : '' }}">Trending</span>
                        </a>

                        {{-- Trivia --}}
                        <a href="{{ route('quiz') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('quiz') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                           wire:navigate>
                            @if(request()->routeIs('quiz'))
                                <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg shadow-purple-600/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
                                </span>
                            @else
                                <span class="flex size-9 items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
                                </span>
                            @endif
                            <span class="{{ request()->routeIs('quiz') ? 'font-semibold' : '' }}">Trivia</span>
                        </a>

                        {{-- Coming Soon --}}
                        <a href="{{ route('upcoming.index') }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('upcoming.*') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                           wire:navigate>
                            @if(request()->routeIs('upcoming.*'))
                                <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-600/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                </span>
                            @else
                                <span class="flex size-9 items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                                </span>
                            @endif
                            <span class="{{ request()->routeIs('upcoming.*') ? 'font-semibold' : '' }}">Coming Soon</span>
                        </a>
                    </div>

                    @auth
                        <p class="mb-3 mt-8 px-3 text-[11px] font-semibold uppercase tracking-widest text-zinc-600">You</p>
                        <div class="space-y-1">
                            <a href="{{ route('messages') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('messages*') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                               wire:navigate>
                                <span class="flex size-9 items-center justify-center {{ request()->routeIs('messages*') ? 'rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-600/30' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] {{ request()->routeIs('messages*') ? 'text-white' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('messages*') ? '2' : '1.5' }}"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                </span>
                                <span class="{{ request()->routeIs('messages*') ? 'font-semibold' : '' }}">Messages</span>
                            </a>

                            <a href="{{ route('badges') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('badges') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                               wire:navigate>
                                <span class="flex size-9 items-center justify-center {{ request()->routeIs('badges') ? 'rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg shadow-amber-600/30' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] {{ request()->routeIs('badges') ? 'text-white' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('badges') ? '2' : '1.5' }}"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0 1 16.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 0 1-2.27.308 6.023 6.023 0 0 1-2.27-.308" /></svg>
                                </span>
                                <span class="{{ request()->routeIs('badges') ? 'font-semibold' : '' }}">Badges</span>
                            </a>

                            <a href="{{ route('stats') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all {{ request()->routeIs('stats') ? 'text-white' : 'text-zinc-400 hover:bg-white/[0.04] hover:text-white' }}"
                               wire:navigate>
                                <span class="flex size-9 items-center justify-center {{ request()->routeIs('stats') ? 'rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg shadow-violet-600/30' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] {{ request()->routeIs('stats') ? 'text-white' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ request()->routeIs('stats') ? '2' : '1.5' }}"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                                </span>
                                <span class="{{ request()->routeIs('stats') ? 'font-semibold' : '' }}">My Stats</span>
                            </a>
                        </div>
                    @endauth

                    {{-- Following section --}}
                    @auth
                        @php
                            $followingUsers = auth()->user()->following()->limit(6)->get();
                            $followingCount = auth()->user()->following()->count();
                        @endphp
                        <div class="mt-8">
                            <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-widest text-zinc-600">Following</p>
                            @if($followingUsers->isNotEmpty())
                                <div class="space-y-0.5">
                                    @php
                                        $dotColors = ['bg-emerald-500', 'bg-blue-500', 'bg-amber-500', 'bg-rose-500', 'bg-violet-500', 'bg-cyan-500'];
                                    @endphp
                                    @foreach($followingUsers as $i => $followedUser)
                                        <a href="{{ route('user.profile', $followedUser->id) }}"
                                           class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-zinc-400 transition-colors hover:bg-white/[0.04] hover:text-white"
                                           wire:navigate>
                                            <div class="relative flex-shrink-0">
                                                <div class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-zinc-700 to-zinc-800 text-[11px] font-bold text-zinc-300 ring-1 ring-white/[0.06]">
                                                    {{ Str::initials($followedUser->name, true) }}
                                                </div>
                                                <span class="absolute -bottom-0.5 -right-0.5 block size-2.5 rounded-full border-2 border-zinc-950 {{ $dotColors[$i % count($dotColors)] }}"></span>
                                            </div>
                                            <span class="truncate">{{ Str::words($followedUser->name, 2, '') }}</span>
                                        </a>
                                    @endforeach
                                </div>

                                @if($followingCount > 6)
                                    <a href="{{ route('activity.feed') }}" class="mt-2 flex items-center gap-3 px-3 py-2 text-sm font-medium text-amber-500/80 transition-colors hover:text-amber-400" wire:navigate>
                                        <span class="flex size-8 items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </span>
                                        Load more
                                    </a>
                                @endif
                            @else
                                <p class="px-3 py-2 text-xs text-zinc-600">Follow users to see them here</p>
                            @endif
                        </div>
                    @endauth
                </nav>

                {{-- Divider --}}
                <div class="mx-5 border-t border-white/[0.06]"></div>

                {{-- Bottom: Log Out --}}
                <div class="shrink-0 p-3">
                    @auth
                        <flux:modal.trigger name="confirm-logout">
                            <button type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-500 transition-colors hover:bg-white/[0.04] hover:text-zinc-300"
                                    data-test="sidebar-logout-button">
                                <span class="flex size-9 items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                </span>
                                Log Out
                            </button>
                        </flux:modal.trigger>
                    @endauth
                </div>
            </aside>

            {{-- Main area --}}
            <div class="flex flex-1 flex-col overflow-hidden">
                {{-- Top bar (sticky) --}}
                <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-3 border-b border-white/[0.06] bg-zinc-950/80 px-4 backdrop-blur-xl sm:px-6">
                    {{-- Mobile hamburger --}}
                    <button @click="sidebarOpen = true" class="flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>

                    {{-- Back / Forward nav arrows --}}
                    <div class="hidden items-center gap-1 lg:flex">
                        <button onclick="history.back()" class="flex size-8 items-center justify-center rounded-full border border-white/[0.08] text-zinc-400 transition-colors hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white" title="Go back">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button onclick="history.forward()" class="flex size-8 items-center justify-center rounded-full border border-white/[0.08] text-zinc-400 transition-colors hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white" title="Go forward">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>

                    @include('partials.smart-search')

                    {{-- Right actions --}}
                    <div class="flex items-center gap-1">
                        @auth
                            {{-- Watch Parties --}}
                            <a href="{{ route('watch-parties') }}" class="flex size-9 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-white/[0.06] hover:text-white" wire:navigate title="Watch Parties">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>
                            </a>

                            {{-- Notifications --}}
                            <livewire:notification-dropdown />

                            <div class="mx-1 h-5 w-px bg-white/[0.08]"></div>

                            @include('partials.user-account-menu')
                        @else
                            <a href="{{ route('login') }}" class="rounded-lg px-3.5 py-2 text-sm font-medium text-zinc-400 transition-colors hover:text-white" wire:navigate>Sign in</a>
                            <a href="{{ route('register') }}" class="rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-amber-600/20 transition hover:from-amber-500 hover:to-amber-600" wire:navigate>Get Started</a>
                        @endauth
                    </div>
                </header>

                {{-- Page content --}}
                <main class="flex-1 overflow-y-auto">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @auth
            @include('partials.logout-confirm-modal')
        @endauth

        @persist('toast')
            <flux:toast position="top center" />
        @endpersist

        @fluxScripts
    </body>
</html>
