@php
    $shareTitle = $shareTitle ?? 'Check this out';
    $shareUrl = $shareUrl ?? url()->current();
    $encodedTitle = urlencode($shareTitle);
    $encodedUrl = urlencode($shareUrl);
@endphp

<div x-data="{ showShare: false, copied: false }" class="relative inline-block">
    <button @click="showShare = !showShare"
            class="flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-white/[0.04] px-3 py-2 text-sm text-zinc-400 transition hover:border-white/[0.15] hover:bg-white/[0.06] hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" /></svg>
        Share
    </button>

    <div x-show="showShare"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.outside="showShare = false"
         x-cloak
         class="absolute right-0 top-full z-30 mt-2 w-52 rounded-xl border border-white/[0.08] bg-zinc-900 p-2 shadow-xl">

        {{-- Copy link --}}
        <button @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.06]">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
            <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
        </button>

        {{-- Twitter/X --}}
        <a href="https://twitter.com/intent/tweet?text={{ $encodedTitle }}&url={{ $encodedUrl }}"
           target="_blank" rel="noopener"
           class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.06]">
            <svg class="size-4 text-zinc-500" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            X (Twitter)
        </a>

        {{-- Facebook --}}
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}"
           target="_blank" rel="noopener"
           class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.06]">
            <svg class="size-4 text-zinc-500" viewBox="0 0 24 24" fill="currentColor"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 1.09.07 1.373.14v3.322a9 9 0 0 0-.73-.029c-1.036 0-1.436.392-1.436 1.412v2.713h3.46l-.732 3.667h-2.729v8.107C19.396 22.52 23 18.16 23 12.956 23 7.09 18.523 2.309 12.963 2.309c-5.56 0-10.064 4.781-10.064 10.647 0 3.96 2.186 7.398 5.466 9.218z"/></svg>
            Facebook
        </a>

        {{-- WhatsApp --}}
        <a href="https://wa.me/?text={{ $encodedTitle }}%20{{ $encodedUrl }}"
           target="_blank" rel="noopener"
           class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.06]">
            <svg class="size-4 text-zinc-500" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
            WhatsApp
        </a>

        {{-- Reddit --}}
        <a href="https://reddit.com/submit?url={{ $encodedUrl }}&title={{ $encodedTitle }}"
           target="_blank" rel="noopener"
           class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-white/[0.06]">
            <svg class="size-4 text-zinc-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.895 14.53c.023.175.035.353.035.535 0 2.737-3.183 4.955-7.11 4.955s-7.11-2.218-7.11-4.955c0-.182.012-.36.035-.535a1.8 1.8 0 0 1-.762-1.465c0-.995.807-1.8 1.8-1.8.489 0 .933.195 1.258.513 1.24-.895 2.948-1.47 4.847-1.54l.91-4.29a.3.3 0 0 1 .36-.235l3.04.645a1.29 1.29 0 1 1-.135.645l-2.715-.577-.81 3.81c1.863.09 3.535.66 4.75 1.54.325-.318.77-.513 1.258-.513.993 0 1.8.807 1.8 1.8a1.8 1.8 0 0 1-.766 1.472zM9.57 13.05a1.29 1.29 0 1 0 0 2.58 1.29 1.29 0 0 0 0-2.58zm5.04 3.42a.3.3 0 0 0 0-.42c-.117-.117-.305-.117-.42 0-.39.39-1.14.585-2.19.585s-1.8-.195-2.19-.585a.3.3 0 0 0-.42 0 .3.3 0 0 0 0 .42c.555.555 1.44.81 2.61.81s2.055-.255 2.61-.81zm-.21-1.14a1.29 1.29 0 1 0 0-2.58 1.29 1.29 0 0 0 0 2.58z"/></svg>
            Reddit
        </a>
    </div>
</div>
