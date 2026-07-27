<div class="relative mb-8 w-full">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-white/[0.05] hover:text-white" wire:navigate aria-label="{{ __('Back to dashboard') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ __('Settings') }}</h1>
                <p class="text-sm text-zinc-400">{{ __('Manage your profile and account settings') }}</p>
            </div>
        </div>

        @auth
            <div class="flex items-center gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-3 py-2.5 sm:ms-auto">
                <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 text-sm font-bold text-white">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                </div>
                @if(auth()->user()->hasVerifiedEmail())
                    <span class="hidden shrink-0 rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-400 sm:inline">{{ __('Verified') }}</span>
                @else
                    <span class="hidden shrink-0 rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-400 sm:inline">{{ __('Unverified') }}</span>
                @endif
            </div>
        @endauth
    </div>
    <div class="mt-6 h-px bg-white/[0.06]"></div>
</div>
