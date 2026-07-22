<div class="flex flex-col gap-8 md:flex-row">
    {{-- Sidebar nav --}}
    <div class="w-full md:w-56 shrink-0">
        <nav class="flex gap-1 md:flex-col">
            @php
                $settingsNav = [
                    ['route' => 'profile.edit', 'label' => 'Profile', 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ['route' => 'security.edit', 'label' => 'Security', 'icon' => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z'],
                    ['route' => 'appearance.edit', 'label' => 'Appearance', 'icon' => 'M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z'],
                ];
            @endphp
            @foreach($settingsNav as $nav)
                <a href="{{ route($nav['route']) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($nav['route']) ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-white' }}"
                   wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $nav['icon'] }}" />
                    </svg>
                    {{ $nav['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-white">{{ $heading ?? '' }}</h2>
            @if(!empty($subheading))
                <p class="mt-1 text-sm text-zinc-400">{{ $subheading }}</p>
            @endif
        </div>
        <div class="w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
