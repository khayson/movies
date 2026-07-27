{{-- Auth user account menu: Dashboard, Settings, Log out --}}
<div x-data="{ open: false }" class="relative ml-1">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 transition-colors hover:bg-white/[0.06] {{ request()->routeIs('dashboard', 'profile.edit', 'preferences.edit', 'appearance.edit', 'security.edit') ? 'bg-white/[0.06]' : '' }}"
        aria-haspopup="menu"
        :aria-expanded="open"
    >
        <div class="flex size-7 items-center justify-center rounded-md bg-gradient-to-br from-amber-500/80 to-amber-700/80 text-xs font-bold text-white">
            {{ auth()->user()->initials() }}
        </div>
        <span class="hidden text-sm font-medium text-zinc-300 sm:inline">{{ Str::words(auth()->user()->name, 1, '') }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="hidden size-3.5 text-zinc-500 transition sm:block" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl border border-white/[0.08] bg-zinc-900 p-1.5 shadow-2xl shadow-black/50"
        role="menu"
    >
        <div class="border-b border-white/[0.06] px-3 py-2.5">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
        </div>

        <div class="py-1">
            <a
                href="{{ route('dashboard') }}"
                @click="open = false"
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.05] hover:text-white"
                role="menuitem"
                wire:navigate
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25a2.25 2.25 0 0 1-2.25-2.25v-2.25Z" />
                </svg>
                {{ __('Dashboard') }}
            </a>

            <a
                href="{{ route('profile.edit') }}"
                @click="open = false"
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.05] hover:text-white"
                role="menuitem"
                wire:navigate
                data-test="nav-settings-link"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.992l-1.003-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                {{ __('Settings') }}
            </a>
        </div>

        <div class="border-t border-white/[0.06] pt-1">
            <flux:modal.trigger name="confirm-logout">
                <button
                    type="button"
                    @click="open = false"
                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-white/[0.05] hover:text-white"
                    role="menuitem"
                    data-test="logout-button"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    {{ __('Log out') }}
                </button>
            </flux:modal.trigger>
        </div>
    </div>
</div>
