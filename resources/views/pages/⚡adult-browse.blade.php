<?php

use App\Services\AdultContentProvider;
use App\Services\SourceResolver;
use App\Services\Tmdb;
use App\Support\AdultSafety;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Adult — StreamVault')]
class extends Component
{
    #[Url]
    public string $provider = 'tmdb';

    #[Url]
    public string $sort = '';

    public int $page = 1;

    public string $search = '';

    public ?string $embedUrl = null;

    public string $embedTitle = '';

    public string $videoSrc = '';

    public string $videoHls = '';

    public string $playerType = 'iframe';

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
        $this->page = 1;
        $this->search = '';
        $this->sort = '';
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function searchVideos(): void
    {
        if (AdultSafety::isBlockedQuery($this->search)) {
            $this->search = '';
        }

        $this->page = 1;
    }

    public function quickExit(): mixed
    {
        session()->forget('auth.password_confirmed_at');

        return $this->redirectRoute('home', navigate: true);
    }

    public function openPlayer(string $embedUrl, string $title): void
    {
        $this->embedUrl = $embedUrl;
        $this->embedTitle = $title;
        $this->videoSrc = '';
        $this->videoHls = '';
        $this->playerType = 'iframe';
    }

    public function openXnxxPlayer(string $videoLink, string $title): void
    {
        $adultProvider = app(AdultContentProvider::class);
        $stream = $adultProvider->xnxxDownload($videoLink);

        $this->embedTitle = $stream['title'] ?? $title;

        if ($stream && ! empty($stream['hls'])) {
            $this->videoHls = $stream['hls'];
            $this->videoSrc = $stream['video_high'] ?: ($stream['video_low'] ?: '');
            $this->embedUrl = null;
            $this->playerType = 'native';
        } elseif ($stream && ! empty($stream['video_high'])) {
            $this->videoSrc = $stream['video_high'];
            $this->videoHls = '';
            $this->embedUrl = null;
            $this->playerType = 'native';
        } else {
            $this->embedUrl = $videoLink;
            $this->videoSrc = '';
            $this->videoHls = '';
            $this->playerType = 'iframe';
        }
    }

    public function openPornhubPlayer(string $videoLink, string $title): void
    {
        $adultProvider = app(AdultContentProvider::class);
        $stream = $adultProvider->pornhubDownload($videoLink);

        $this->embedTitle = $stream['title'] ?? $title;

        if ($stream && ! empty($stream['hls'])) {
            $this->videoHls = $stream['hls'];
            $this->videoSrc = $stream['video_high'] ?: ($stream['video_low'] ?: '');
            $this->embedUrl = null;
            $this->playerType = 'native';
        } elseif ($stream && ! empty($stream['video_high'])) {
            $this->videoSrc = $stream['video_high'];
            $this->videoHls = '';
            $this->embedUrl = null;
            $this->playerType = 'native';
        } else {
            $this->embedUrl = $videoLink;
            $this->videoSrc = '';
            $this->videoHls = '';
            $this->playerType = 'iframe';
        }
    }

    public function openEpornerPlayer(string $videoId, string $title): void
    {
        $adultProvider = app(AdultContentProvider::class);
        $stream = $adultProvider->epornerDownload($videoId);

        $this->embedTitle = $stream['title'] ?? $title;

        if ($stream && (! empty($stream['hls']) || ! empty($stream['video_high']))) {
            $this->videoHls = $stream['hls'] ?? '';
            $this->videoSrc = $stream['video_high'] ?: ($stream['video_low'] ?: '');
            $this->embedUrl = null;
            $this->playerType = 'native';
        } else {
            $this->embedUrl = "https://www.eporner.com/embed/{$videoId}/";
            $this->videoSrc = '';
            $this->videoHls = '';
            $this->playerType = 'iframe';
        }
    }

    public function openXvideosPlayer(string $videoLink, string $title): void
    {
        $adultProvider = app(AdultContentProvider::class);
        $stream = $adultProvider->xvideosDownload($videoLink);

        $this->embedTitle = $stream['title'] ?? $title;

        if ($stream && ! empty($stream['hls'])) {
            $this->videoHls = $stream['hls'];
            $this->videoSrc = $stream['video_high'] ?: ($stream['video_low'] ?: '');
            $this->embedUrl = null;
            $this->playerType = 'native';
        } elseif ($stream && ! empty($stream['video_high'])) {
            $this->videoSrc = $stream['video_high'];
            $this->videoHls = '';
            $this->embedUrl = null;
            $this->playerType = 'native';
        } else {
            $this->embedUrl = $videoLink;
            $this->videoSrc = '';
            $this->videoHls = '';
            $this->playerType = 'iframe';
        }
    }

    public function openTmdbPlayer(int $tmdbId, string $title): void
    {
        $resolver = app(SourceResolver::class);
        $resolved = $resolver->resolveAdult($tmdbId);
        $sources = $resolved['embed'];
        if (count($sources) > 0) {
            $this->embedUrl = $sources[0]['url'];
            $this->embedTitle = $title;
            $this->videoSrc = '';
            $this->videoHls = '';
            $this->playerType = 'iframe';
        }
    }

    public function closePlayer(): void
    {
        $this->embedUrl = null;
        $this->embedTitle = '';
        $this->videoSrc = '';
        $this->videoHls = '';
        $this->playerType = 'iframe';
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchXnxx(AdultContentProvider $adultProvider): array
    {
        if (AdultSafety::isBlockedQuery($this->sort)) {
            $this->sort = '';
        }

        if ($this->search !== '') {
            return $adultProvider->xnxx(query: $this->search, page: $this->page, mode: 'search');
        }

        if ($this->sort !== '' && $this->sort !== 'trending') {
            return $adultProvider->xnxx(page: $this->page, mode: 'category', category: $this->sort);
        }

        return $adultProvider->xnxx(page: $this->page, mode: 'trending');
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPornhub(AdultContentProvider $adultProvider): array
    {
        if ($this->search !== '') {
            return $adultProvider->pornhub(query: $this->search, page: $this->page, mode: 'search');
        }

        return $adultProvider->pornhub(page: $this->page, mode: 'trending');
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchXvideos(AdultContentProvider $adultProvider): array
    {
        $query = $this->search !== '' ? $this->search : 'trending';

        return $adultProvider->xvideos(query: $query, page: $this->page);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchEporner(AdultContentProvider $adultProvider): array
    {
        $order = $this->sort ?: 'top-weekly';

        return $adultProvider->eporner($this->search, $this->page, $order);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRedtube(AdultContentProvider $adultProvider): array
    {
        $order = $this->sort ?: 'mostviewed';

        return $adultProvider->redtube($this->search, $this->page, $order);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTmdb(Tmdb $tmdb, string $mediaType = 'movie'): array
    {
        $isTv = $mediaType === 'tv';
        $endpoint = $isTv ? '/discover/tv' : '/discover/movie';
        $searchEndpoint = $isTv ? '/search/tv' : '/search/movie';
        $params = [
            'include_adult' => true,
            'page' => $this->page,
            'sort_by' => $this->sort ?: 'popularity.desc',
        ];

        if (! $isTv) {
            $params['certification_country'] = 'US';
            $params['certification'] = 'NC-17|X';
        }

        if ($this->search !== '') {
            $data = $tmdb->get($searchEndpoint, [
                'query' => $this->search,
                'include_adult' => true,
                'page' => $this->page,
            ]);
        } else {
            $data = $tmdb->get($endpoint, $params);
        }

        $videos = collect($data['results'] ?? [])
            ->filter(function (array $item) use ($isTv): bool {
                if ($this->search !== '' || $isTv) {
                    return (bool) ($item['adult'] ?? false);
                }

                return true;
            })
            ->map(fn (array $item): array => [
                'id' => (string) $item['id'],
                'title' => $item['title'] ?? ($item['name'] ?? 'Untitled'),
                'thumbnail' => $tmdb->imageUrl((string) ($item['poster_path'] ?? ''), 'w342'),
                'duration' => '',
                'views' => '',
                'rating' => ! empty($item['vote_average']) ? number_format((float) $item['vote_average'], 1) : '',
                'embed_url' => '',
                'provider' => 'TMDB',
                'tmdb_id' => $item['id'],
                'media_type' => $mediaType,
                'year' => substr((string) ($item['release_date'] ?? $item['first_air_date'] ?? ''), 0, 4),
            ])
            ->all();

        return [
            'videos' => AdultSafety::rejectBlockedTitles($videos),
            'total_pages' => min($data['total_pages'] ?? 1, 500),
        ];
    }

    public function with(Tmdb $tmdb, AdultContentProvider $adultProvider): array
    {
        if (AdultSafety::isBlockedQuery($this->search) || AdultSafety::isBlockedQuery($this->sort)) {
            $this->search = '';
            $this->sort = '';
        }

        $data = match ($this->provider) {
            'xnxx' => $this->fetchXnxx($adultProvider),
            'pornhub' => $this->fetchPornhub($adultProvider),
            'xvideos' => $this->fetchXvideos($adultProvider),
            'eporner' => $this->fetchEporner($adultProvider),
            'redtube' => $this->fetchRedtube($adultProvider),
            'tmdb_tv' => $this->fetchTmdb($tmdb, 'tv'),
            default => $this->fetchTmdb($tmdb),
        };

        $externalOnly = collect(config('sources.adult_providers', []))
            ->filter(fn (array $p): bool => ($p['driver'] ?? '') === 'external')
            ->reject(fn (array $p): bool => in_array($p['name'], ['Eporner', 'RedTube', 'PornHub', 'XVideos']))
            ->values()
            ->all();

        $providers = [
            ['value' => 'tmdb', 'label' => 'Movies'],
            ['value' => 'tmdb_tv', 'label' => 'Series'],
            ['value' => 'xnxx', 'label' => 'XNXX'],
            ['value' => 'pornhub', 'label' => 'PornHub'],
            ['value' => 'xvideos', 'label' => 'XVideos'],
            ['value' => 'eporner', 'label' => 'Eporner'],
            ['value' => 'redtube', 'label' => 'RedTube'],
        ];

        $sortOptions = match ($this->provider) {
            'xnxx' => [
                ['value' => 'trending', 'label' => 'Trending'],
                ['value' => 'milf', 'label' => 'MILF'],
                ['value' => 'amateur', 'label' => 'Amateur'],
                ['value' => 'anal', 'label' => 'Anal'],
            ],
            'pornhub' => [
                ['value' => 'trending', 'label' => 'Trending'],
            ],
            'xvideos' => [
                ['value' => 'trending', 'label' => 'Search to browse'],
            ],
            'eporner' => [
                ['value' => 'top-weekly', 'label' => 'Top Weekly'],
                ['value' => 'top-monthly', 'label' => 'Top Monthly'],
                ['value' => 'latest', 'label' => 'Latest'],
                ['value' => 'longest', 'label' => 'Longest'],
            ],
            'redtube' => [
                ['value' => 'mostviewed', 'label' => 'Most Viewed'],
                ['value' => 'rating', 'label' => 'Top Rated'],
                ['value' => 'newest', 'label' => 'Newest'],
            ],
            'tmdb', 'tmdb_tv' => [
                ['value' => 'popularity.desc', 'label' => 'Popular'],
                ['value' => 'vote_average.desc', 'label' => 'Top Rated'],
                ['value' => $this->provider === 'tmdb_tv' ? 'first_air_date.desc' : 'primary_release_date.desc', 'label' => 'Recent'],
            ],
            default => [],
        };

        return [
            'videos' => $data['videos'],
            'totalPages' => $data['total_pages'],
            'providers' => $providers,
            'sortOptions' => $sortOptions,
            'externalOnly' => $externalOnly,
            'blurPreviews' => auth()->user()?->adultBlurPreviews() ?? true,
            'stealthMode' => auth()->user()?->adultStealthEnabled() ?? false,
        ];
    }
}; ?>

@php
    $isTmdbCatalog = in_array($provider, ['tmdb', 'tmdb_tv'], true);
@endphp

<div
    x-data
    x-on:keydown.escape.window="$wire.closePlayer()"
    x-on:keydown.shift.escape.window="$wire.quickExit()"
>
    <div class="relative overflow-hidden border-b border-white/[0.06]">
        <div class="absolute inset-0 bg-gradient-to-b from-red-950/50 via-zinc-950/85 to-zinc-950"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col gap-6 px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-400/80">{{ $stealthMode ? __('Private vault') : __('Adult vault') }}</p>
                    <h1 class="mt-2 text-4xl font-bold tracking-tight sm:text-5xl">
                        <span class="bg-gradient-to-r from-red-400 to-amber-400 bg-clip-text text-transparent">{{ __('18+') }}</span>
                    </h1>
                    <p class="mt-2 max-w-xl text-sm text-zinc-400">
                        {{ __('Age-verified catalog. Movies and series play in the StreamVault player. Tube sources stay in a private overlay.') }}
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="quickExit"
                    class="rounded-lg border border-white/[0.08] bg-zinc-950/60 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-400 transition hover:border-red-500/40 hover:text-white"
                >
                    {{ __('Quick exit') }}
                    <span class="ml-1 hidden text-[10px] text-zinc-600 sm:inline">Shift+Esc</span>
                </button>
            </div>

            <div class="rounded-lg border border-red-800/40 bg-red-950/20 px-4 py-3">
                <p class="text-xs text-red-300/90">
                    <strong>{{ __('18+ only.') }}</strong>
                    {{ __('Confirm you are an adult. Stealth mode hides this activity from continue watching, stats, and the leaderboard.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Provider tabs --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach($providers as $p)
                <button
                    wire:click="setProvider('{{ $p['value'] }}')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $provider === $p['value'] ? 'bg-red-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-white' }}"
                >
                    {{ $p['label'] }}
                </button>
            @endforeach

            {{-- External-only sites --}}
            @foreach($externalOnly as $ext)
                <a href="{{ $ext['url'] }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-800/60 px-4 py-2 text-sm font-medium text-zinc-500 transition hover:bg-zinc-700 hover:text-white">
                    {{ $ext['name'] }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                </a>
            @endforeach
        </div>

        {{-- Search & sort bar --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <form wire:submit="searchVideos" class="flex flex-1 gap-2">
                <input
                    type="text"
                    wire:model="search"
                    placeholder="Search {{ $isTmdbCatalog ? ($provider === 'tmdb_tv' ? 'series' : 'movies') : strtoupper($provider) }}..."
                    class="flex-1 rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-red-600 focus:ring-1 focus:ring-red-600"
                />
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-500">
                    Search
                </button>
            </form>

            <div class="scrollbar-hide flex gap-2 overflow-x-auto">
                @foreach($sortOptions as $opt)
                    <button
                        wire:click="$set('sort', '{{ $opt['value'] }}')"
                        class="shrink-0 rounded-md px-3 py-1.5 text-xs font-medium transition {{ ($sort === $opt['value'] || ($sort === '' && $loop->first)) ? 'bg-zinc-700 text-white' : 'bg-zinc-800/60 text-zinc-500 hover:bg-zinc-700 hover:text-white' }}"
                    >
                        {{ $opt['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Player modal --}}
        @if($embedUrl || $videoSrc || $videoHls)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" wire:click.self="closePlayer">
                <div class="w-full max-w-5xl rounded-xl border border-zinc-700 bg-zinc-900 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-zinc-800 px-4 py-3">
                        <h3 class="truncate pr-4 text-sm font-semibold">{{ $embedTitle }}</h3>
                        <button wire:click="closePlayer" class="shrink-0 rounded-lg p-1 text-zinc-400 transition hover:bg-zinc-800 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="aspect-video w-full bg-black">
                        @if($playerType === 'native' && ($videoHls || $videoSrc))
                            <video
                                controls
                                autoplay
                                class="size-full"
                                poster=""
                                @if($videoSrc) src="{{ $videoSrc }}" @endif
                            >
                                @if($videoHls)
                                    <source src="{{ $videoHls }}" type="application/x-mpegURL" />
                                @endif
                                @if($videoSrc)
                                    <source src="{{ $videoSrc }}" type="video/mp4" />
                                @endif
                                Your browser does not support the video tag.
                            </video>
                        @elseif($embedUrl)
                            <iframe
                                src="{{ $embedUrl }}"
                                class="size-full"
                                allowfullscreen
                                allow="autoplay; fullscreen"
                                referrerpolicy="origin"
                                sandbox="allow-forms allow-scripts allow-same-origin allow-popups"
                            ></iframe>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div wire:loading class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            @foreach(range(1, 12) as $skeleton)
                <div class="overflow-hidden rounded-lg bg-zinc-800/80 {{ $isTmdbCatalog ? 'aspect-[2/3]' : 'aspect-video' }} animate-pulse"></div>
            @endforeach
        </div>

        {{-- Results --}}
        <div wire:loading.remove>
        @if(count($videos) > 0)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($videos as $video)
                    @php
                        $watchUrl = $isTmdbCatalog && ! empty($video['tmdb_id'])
                            ? route('watch', ['type' => $video['media_type'] ?? ($provider === 'tmdb_tv' ? 'tv' : 'movie'), 'tmdbId' => $video['tmdb_id']])
                            : null;
                    @endphp
                    <div
                        @if($watchUrl)
                            {{-- TMDB titles open the full player --}}
                        @elseif($provider === 'xnxx' && !empty($video['video_link']))
                            wire:click="openXnxxPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'pornhub' && !empty($video['video_link']))
                            wire:click="openPornhubPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'xvideos' && !empty($video['video_link']))
                            wire:click="openXvideosPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'eporner' && !empty($video['id']))
                            wire:click="openEpornerPlayer('{{ $video['id'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif(!empty($video['embed_url']))
                            wire:click="openPlayer('{{ $video['embed_url'] }}', '{{ addslashes($video['title']) }}')"
                        @endif
                        class="group {{ $watchUrl ? '' : 'cursor-pointer' }}"
                    >
                        @if($watchUrl)
                            <a href="{{ $watchUrl }}" wire:navigate class="block">
                        @endif
                        <div class="relative overflow-hidden rounded-lg bg-zinc-800 {{ $isTmdbCatalog ? 'aspect-[2/3]' : 'aspect-video' }}">
                            @if(!empty($video['thumbnail']))
                                <img src="{{ $video['thumbnail'] }}"
                                     alt="{{ $video['title'] }}"
                                     class="size-full object-cover transition duration-300 group-hover:scale-105 {{ $blurPreviews ? 'blur-md group-hover:blur-0 group-focus-within:blur-0' : '' }}"
                                     loading="lazy" />
                            @else
                                <div class="flex size-full items-center justify-center text-zinc-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                </div>
                            @endif

                            {{-- Play overlay --}}
                            <div class="absolute inset-0 flex items-center justify-center bg-black/0 transition group-hover:bg-black/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-10 text-white opacity-0 transition group-hover:opacity-100" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z" clip-rule="evenodd" />
                                </svg>
                            </div>

                            {{-- Duration badge --}}
                            @if(!empty($video['duration']))
                                <div class="absolute bottom-1.5 right-1.5 rounded bg-black/80 px-1.5 py-0.5 text-xs font-medium text-white">
                                    {{ $video['duration'] }}
                                </div>
                            @endif

                            {{-- Rating badge --}}
                            @if(!empty($video['rating']) && $video['rating'] > 0)
                                <div class="absolute right-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-xs font-medium text-amber-400">
                                    {{ $video['rating'] }}
                                </div>
                            @endif

                            {{-- Provider badge --}}
                            <div class="absolute left-1.5 top-1.5 rounded bg-red-600/80 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                                {{ $video['provider'] }}
                            </div>
                        </div>
                        <div class="mt-2">
                            <p class="line-clamp-2 text-sm font-medium text-zinc-200 group-hover:text-white">{{ $video['title'] }}</p>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                                @if(!empty($video['views']))
                                    <span>{{ $video['views'] }} views</span>
                                @endif
                                @if(!empty($video['year']))
                                    <span>{{ $video['year'] }}</span>
                                @endif
                            </div>
                        </div>
                        @if($watchUrl)
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8 flex items-center justify-center gap-4">
                @if($page > 1)
                    <button wire:click="previousPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Previous</button>
                @endif
                <span class="text-sm text-zinc-500">Page {{ $page }} of {{ $totalPages }}</span>
                @if($page < $totalPages)
                    <button wire:click="nextPage" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm text-zinc-300 transition hover:bg-zinc-700">Next</button>
                @endif
            </div>
        @else
            <div class="py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-4 size-12 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                <p class="text-lg text-zinc-400">No content found</p>
                <p class="mt-1 text-sm text-zinc-600">Try a different search or category</p>
            </div>
        @endif
        </div>
    </div>
</div>
