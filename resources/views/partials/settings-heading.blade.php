<div class="relative mb-8 w-full">
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-zinc-500 transition hover:text-white" wire:navigate>
            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Settings') }}</h1>
            <p class="text-sm text-zinc-400">{{ __('Manage your profile and account settings') }}</p>
        </div>
    </div>
    <div class="mt-6 h-px bg-zinc-800"></div>
</div>
