<?php

use App\Services\AdultContentProvider;
use App\Services\SourceResolver;
use App\Services\Tmdb;
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
    public string $provider = 'xnxx';

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
        $this->page = 1;
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
    private function fetchTmdb(Tmdb $tmdb): array
    {
        $params = [
            'include_adult' => true,
            'page' => $this->page,
            'certification_country' => 'US',
            'sort_by' => $this->sort ?: 'popularity.desc',
            'certification' => 'NC-17|X',
        ];

        if ($this->search !== '') {
            $data = $tmdb->get('/search/movie', [
                'query' => $this->search,
                'include_adult' => true,
                'page' => $this->page,
            ]);
        } else {
            $data = $tmdb->get('/discover/movie', $params);
        }

        $videos = collect($data['results'] ?? [])
            ->map(fn (array $item): array => [
                'id' => (string) $item['id'],
                'title' => $item['title'] ?? 'Untitled',
                'thumbnail' => ! empty($item['poster_path'])
                    ? "https://image.tmdb.org/t/p/w342{$item['poster_path']}"
                    : '',
                'duration' => '',
                'views' => '',
                'rating' => ! empty($item['vote_average']) ? number_format($item['vote_average'], 1) : '',
                'embed_url' => '',
                'provider' => 'TMDB',
                'tmdb_id' => $item['id'],
                'year' => ! empty($item['release_date']) ? substr($item['release_date'], 0, 4) : '',
            ])
            ->all();

        return [
            'videos' => $videos,
            'total_pages' => min($data['total_pages'] ?? 1, 500),
        ];
    }

    public function with(Tmdb $tmdb, AdultContentProvider $adultProvider): array
    {
        $data = match ($this->provider) {
            'xnxx' => $this->fetchXnxx($adultProvider),
            'pornhub' => $this->fetchPornhub($adultProvider),
            'xvideos' => $this->fetchXvideos($adultProvider),
            'eporner' => $this->fetchEporner($adultProvider),
            'redtube' => $this->fetchRedtube($adultProvider),
            'tmdb' => $this->fetchTmdb($tmdb),
            default => ['videos' => [], 'total_pages' => 1],
        };

        $externalOnly = collect(config('sources.adult_providers', []))
            ->filter(fn (array $p): bool => ($p['driver'] ?? '') === 'external')
            ->reject(fn (array $p): bool => in_array($p['name'], ['Eporner', 'RedTube', 'PornHub', 'XVideos']))
            ->values()
            ->all();

        $providers = [
            ['value' => 'xnxx', 'label' => 'XNXX'],
            ['value' => 'pornhub', 'label' => 'PornHub'],
            ['value' => 'xvideos', 'label' => 'XVideos'],
            ['value' => 'eporner', 'label' => 'Eporner'],
            ['value' => 'redtube', 'label' => 'RedTube'],
            ['value' => 'tmdb', 'label' => 'Movies (TMDB)'],
        ];

        $sortOptions = match ($this->provider) {
            'xnxx' => [
                ['value' => 'trending', 'label' => 'Trending'],
                ['value' => 'milf', 'label' => 'MILF'],
                ['value' => 'teen', 'label' => 'Teen'],
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
            'tmdb' => [
                ['value' => 'popularity.desc', 'label' => 'Popular'],
                ['value' => 'vote_average.desc', 'label' => 'Top Rated'],
                ['value' => 'primary_release_date.desc', 'label' => 'Recent'],
            ],
            default => [],
        };

        return [
            'videos' => $data['videos'],
            'totalPages' => $data['total_pages'],
            'providers' => $providers,
            'sortOptions' => $sortOptions,
            'externalOnly' => $externalOnly,
        ];
    }
}; ?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-red-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl">Adult Content</h1>
                    <p class="text-sm text-zinc-400">18+ only. Age-verified access.</p>
                </div>
            </div>
        </div>

        {{-- Age gate notice --}}
        <div class="mb-6 rounded-lg border border-red-800/40 bg-red-950/20 px-4 py-3">
            <p class="text-xs text-red-400">
                <strong>Warning:</strong> This section contains adult content. By accessing this page you confirm you are 18 years or older. Content sourced directly from third-party adult platforms via their APIs.
            </p>
        </div>

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
                    placeholder="Search {{ $provider === 'tmdb' ? 'movies' : strtoupper($provider) }}..."
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

        {{-- Results --}}
        @if(count($videos) > 0)
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach($videos as $video)
                    <div
                        @if($provider === 'xnxx' && !empty($video['video_link']))
                            wire:click="openXnxxPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'pornhub' && !empty($video['video_link']))
                            wire:click="openPornhubPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'xvideos' && !empty($video['video_link']))
                            wire:click="openXvideosPlayer('{{ $video['video_link'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'eporner' && !empty($video['id']))
                            wire:click="openEpornerPlayer('{{ $video['id'] }}', '{{ addslashes($video['title']) }}')"
                        @elseif($provider === 'tmdb' && !empty($video['tmdb_id']))
                            wire:click="openTmdbPlayer({{ $video['tmdb_id'] }}, '{{ addslashes($video['title']) }}')"
                        @elseif(!empty($video['embed_url']))
                            wire:click="openPlayer('{{ $video['embed_url'] }}', '{{ addslashes($video['title']) }}')"
                        @endif
                        class="group cursor-pointer"
                    >
                        <div class="relative overflow-hidden rounded-lg bg-zinc-800 {{ $provider === 'tmdb' ? 'aspect-[2/3]' : 'aspect-video' }}">
                            @if(!empty($video['thumbnail']))
                                <img src="{{ $video['thumbnail'] }}"
                                     alt="{{ $video['title'] }}"
                                     class="size-full object-cover transition duration-300 group-hover:scale-105"
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
