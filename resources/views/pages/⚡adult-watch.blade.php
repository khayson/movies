<?php

use App\Services\AdultContentProvider;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Private player — StreamVault')]
class extends Component
{
    public string $provider;

    public string $key;

    public int $activeSource = 0;

    public function mount(string $provider, string $key): void
    {
        abort_unless(array_key_exists($provider, AdultContentProvider::TUBE_PROVIDERS), 404);
        abort_if(AdultContentProvider::decodeWatchKey($key) === null, 404);

        $this->provider = $provider;
        $this->key = $key;
    }

    public function selectSource(int $index): void
    {
        $this->activeSource = max(0, $index);
    }

    public function quickExit(): mixed
    {
        session()->forget('auth.password_confirmed_at');

        return $this->redirectRoute('home', navigate: true);
    }

    public function with(AdultContentProvider $adultProvider): array
    {
        $reference = (string) AdultContentProvider::decodeWatchKey($this->key);
        $video = $adultProvider->video($this->provider, $reference);

        abort_if($video === null, 404);

        $sources = $video['sources'];
        $searchTerms = Str::of($video['title'])->replaceMatches('/[^\pL\pN\s]+/u', ' ')->squish()->words(5, '')->toString();

        return [
            'video' => $video,
            'providerName' => AdultContentProvider::TUBE_PROVIDERS[$this->provider],
            'sources' => $sources,
            'source' => $sources[$this->activeSource] ?? $sources[0] ?? null,
            'activeIndex' => isset($sources[$this->activeSource]) ? $this->activeSource : 0,
            'related' => $adultProvider->related($this->provider, $reference),
            'otherProviders' => collect(AdultContentProvider::TUBE_PROVIDERS)->except($this->provider)->all(),
            'searchTerms' => $searchTerms,
            'blurPreviews' => auth()->user()?->adultBlurPreviews() ?? true,
            'stealthMode' => auth()->user()?->adultStealthEnabled() ?? false,
        ];
    }
}; ?>

<div class="relative" x-data x-on:keydown.shift.escape.window="$wire.quickExit()">
    {{-- Cinematic backdrop --}}
    @if($video['thumbnail'] !== '' && ! $blurPreviews)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[600px] overflow-hidden">
            <img src="{{ $video['thumbnail'] }}" alt="" class="h-full w-full object-cover opacity-15 blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-b from-zinc-950/30 via-zinc-950/80 to-zinc-950"></div>
        </div>
    @else
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[400px] bg-gradient-to-b from-red-950/30 via-zinc-950/80 to-zinc-950"></div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{-- Top bar --}}
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('adult.browse', ['provider' => $provider]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-white/[0.06] bg-white/[0.03] px-3 py-1.5 text-sm text-zinc-400 transition hover:border-white/[0.12] hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ $stealthMode ? __('Back to vault') : __('Back to 18+') }}
            </a>
            <button type="button" wire:click="quickExit" class="rounded-lg border border-white/[0.08] bg-zinc-950/60 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-400 transition hover:border-red-500/40 hover:text-white">
                {{ __('Quick exit') }}
                <span class="ml-1 hidden text-[10px] text-zinc-600 sm:inline">Shift+Esc</span>
            </button>
        </div>

        <div class="mb-5 rounded-2xl border border-white/[0.04] bg-white/[0.015] px-4 py-2.5">
            <p class="text-[11px] text-zinc-500">
                <strong class="font-medium text-red-400/80">18+ only.</strong> {{ config('app.name') }} does not host any video content. Streams come from {{ $providerName }} and other third-party providers.
            </p>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            {{-- Main column --}}
            <div class="min-w-0 flex-1">
                <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-900/80 shadow-2xl shadow-black/50">
                    <div class="relative aspect-video w-full bg-black" wire:key="adult-player-{{ $activeIndex }}">
                        @if($source === null)
                            <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                                <p class="text-sm text-zinc-400">No playable source for this video right now.</p>
                                <p class="text-xs text-zinc-600">Try another video or check back later.</p>
                            </div>
                        @elseif($source['type'] === 'embed')
                            <iframe
                                src="{{ $source['url'] }}"
                                class="size-full"
                                allowfullscreen
                                allow="autoplay; fullscreen; picture-in-picture"
                                referrerpolicy="origin"
                                sandbox="allow-forms allow-scripts allow-same-origin allow-popups"
                            ></iframe>
                        @else
                            <video
                                class="size-full"
                                controls
                                autoplay
                                playsinline
                                @if($source['type'] === 'mp4') src="{{ $source['url'] }}" @endif
                                x-data="{
                                    failed: false,
                                    next: {{ $activeIndex + 1 < count($sources) ? $activeIndex + 1 : 'null' }},
                                    fail() {
                                        if (this.failed || this.next === null) return;
                                        this.failed = true;
                                        $wire.selectSource(this.next);
                                    },
                                    init() {
                                        this.$el.addEventListener('error', () => this.fail());
                                        @if($source['type'] === 'hls')
                                            const url = @js($source['url']);
                                            const start = () => {
                                                if (this.$el.canPlayType('application/vnd.apple.mpegurl')) {
                                                    this.$el.src = url;
                                                    return;
                                                }
                                                if (! window.Hls || ! window.Hls.isSupported()) {
                                                    this.fail();
                                                    return;
                                                }
                                                const hls = new window.Hls({ enableWorker: true });
                                                hls.loadSource(url);
                                                hls.attachMedia(this.$el);
                                                hls.on(window.Hls.Events.ERROR, (_event, data) => { if (data?.fatal) this.fail(); });
                                            };
                                            if (window.Hls) {
                                                start();
                                            } else {
                                                const script = document.createElement('script');
                                                script.src = 'https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js';
                                                script.onload = start;
                                                script.onerror = () => this.fail();
                                                document.head.appendChild(script);
                                            }
                                        @endif
                                    },
                                }"
                            ></video>
                        @endif
                    </div>
                </div>

                {{-- Source switcher --}}
                @if(count($sources) > 0)
                    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-white/[0.06] bg-white/[0.02] px-4 py-3">
                        <span class="mr-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Sources</span>
                        @foreach($sources as $i => $s)
                            <button
                                type="button"
                                wire:click="selectSource({{ $i }})"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-sm font-medium transition {{ $activeIndex === $i ? 'bg-red-600/15 text-red-300 ring-1 ring-red-500/30' : 'border border-white/[0.08] bg-white/[0.03] text-zinc-400 hover:border-white/[0.15] hover:text-white' }}"
                            >
                                <span class="size-1.5 rounded-full {{ $activeIndex === $i ? 'bg-red-400' : 'bg-emerald-500' }}"></span>
                                {{ $s['label'] }}
                            </button>
                        @endforeach
                        <span wire:loading wire:target="selectSource" class="text-xs text-zinc-500">Switching…</span>
                    </div>
                @endif

                {{-- Title + metadata --}}
                <div class="mt-6">
                    <h1 class="text-xl font-bold tracking-tight sm:text-2xl lg:text-3xl">{{ $video['title'] }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-md bg-red-600/80 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-white">{{ $providerName }}</span>
                        @if($video['rating'] !== '')
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $video['rating'] }}
                            </span>
                        @endif
                        @if($video['duration'] !== '')
                            <span class="text-zinc-500">&bull; {{ $video['duration'] }}</span>
                        @endif
                        @if($video['views'] !== '')
                            <span class="text-zinc-500">&bull; {{ $video['views'] }} views</span>
                        @endif
                    </div>
                </div>

                {{-- Other sources --}}
                @if($searchTerms !== '')
                    <div class="mt-6 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-4">
                        <h2 class="text-sm font-bold text-white">Find it on other sources</h2>
                        <p class="mt-1 text-xs text-zinc-500">Search “{{ $searchTerms }}” on another provider.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($otherProviders as $slug => $name)
                                <a href="{{ route('adult.browse', ['provider' => $slug, 'q' => $searchTerms]) }}" wire:navigate class="rounded-xl border border-white/[0.08] bg-white/[0.03] px-3.5 py-1.5 text-sm font-medium text-zinc-400 transition hover:border-red-500/30 hover:text-white">
                                    {{ $name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar: more from this provider --}}
            <div class="w-full lg:w-80 xl:w-96">
                <div class="overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.02]">
                    <div class="flex items-center justify-between border-b border-white/[0.04] bg-white/[0.02] px-4 py-3">
                        <h3 class="text-sm font-bold text-white">More from {{ $providerName }}</h3>
                        <span class="rounded-md bg-white/[0.06] px-2 py-0.5 text-[11px] tabular-nums text-zinc-400">{{ count($related) }}</span>
                    </div>
                    <div class="max-h-[640px] space-y-1 overflow-y-auto p-2">
                        @forelse($related as $item)
                            <a
                                href="{{ route('adult.watch', ['provider' => $provider, 'key' => \App\Services\AdultContentProvider::encodeWatchKey(\App\Services\AdultContentProvider::watchReference($provider, $item))]) }}"
                                wire:navigate
                                class="group flex gap-3 rounded-xl p-2 transition hover:bg-white/[0.04]"
                            >
                                <div class="relative aspect-video w-32 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                                    @if(! empty($item['thumbnail']))
                                        <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy" class="size-full object-cover transition duration-300 group-hover:scale-105 {{ $blurPreviews ? 'blur-md group-hover:blur-0' : '' }}">
                                    @endif
                                    @if(! empty($item['duration']))
                                        <span class="absolute bottom-1 right-1 rounded bg-black/80 px-1 py-0.5 text-[10px] font-medium text-white">{{ $item['duration'] }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="line-clamp-2 text-sm font-medium text-zinc-300 group-hover:text-white">{{ $item['title'] }}</p>
                                    @if(! empty($item['views']))
                                        <p class="mt-1 text-xs text-zinc-600">{{ $item['views'] }} views</p>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="px-2 py-6 text-center text-xs text-zinc-600">Nothing else to show right now.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
