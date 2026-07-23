<?php

use App\Models\EpisodeWatch;
use App\Models\Review;
use App\Services\StreamingAvailability;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public int $tmdbId;

    public int $selectedSeason = 1;

    #[Validate('required|integer|min:1|max:10')]
    public int $reviewRating = 8;

    #[Validate('required|string|min:3|max:100')]
    public string $reviewTitle = '';

    #[Validate('nullable|string|max:5000')]
    public string $reviewBody = '';

    public bool $reviewSpoilers = false;

    public bool $showReviewForm = false;

    public bool $showCollectionPicker = false;

    public function mount(int $tmdbId): void
    {
        $this->tmdbId = $tmdbId;
    }

    public function selectSeason(int $season): void
    {
        $this->selectedSeason = $season;
    }

    public function submitReview(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $this->validate();

        $user->reviews()->updateOrCreate(
            ['tmdb_id' => $this->tmdbId, 'media_type' => 'tv'],
            [
                'title' => $this->reviewTitle,
                'rating' => $this->reviewRating,
                'body' => $this->reviewBody ?: null,
                'contains_spoilers' => $this->reviewSpoilers,
            ],
        );

        $this->showReviewForm = false;
        $this->reviewTitle = '';
        $this->reviewBody = '';
        $this->reviewRating = 8;
        $this->reviewSpoilers = false;
    }

    public function deleteReview(): void
    {
        auth()->user()?->reviews()->where('tmdb_id', $this->tmdbId)->where('media_type', 'tv')->delete();
    }

    public function addToCollection(int $collectionId, string $title, ?string $posterPath): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $collection = $user->collections()->find($collectionId);
        if (! $collection) {
            return;
        }

        $collection->items()->firstOrCreate(
            ['tmdb_id' => $this->tmdbId, 'media_type' => 'tv'],
            [
                'title' => $title,
                'poster_path' => $posterPath,
                'sort_order' => $collection->items()->count(),
            ],
        );

        $this->showCollectionPicker = false;
    }

    public function toggleEpisodeWatched(int $season, int $episode): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $existing = $user->episodeWatches()
            ->where('tmdb_id', $this->tmdbId)
            ->where('season_number', $season)
            ->where('episode_number', $episode)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $user->episodeWatches()->create([
                'tmdb_id' => $this->tmdbId,
                'season_number' => $season,
                'episode_number' => $episode,
            ]);
        }
    }

    public function markSeasonWatched(int $season, int $episodeCount): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        for ($ep = 1; $ep <= $episodeCount; $ep++) {
            $user->episodeWatches()->firstOrCreate([
                'tmdb_id' => $this->tmdbId,
                'season_number' => $season,
                'episode_number' => $ep,
            ]);
        }
    }

    public function toggleFavorite(string $title, ?string $posterPath): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $existing = $user->favorites()->where('tmdb_id', $this->tmdbId)->where('media_type', 'tv')->first();
        if ($existing) {
            $existing->delete();
        } else {
            $user->favorites()->create([
                'tmdb_id' => $this->tmdbId,
                'media_type' => 'tv',
                'title' => $title,
                'poster_path' => $posterPath,
            ]);
        }
    }

    public function toggleWatchlist(string $title, ?string $posterPath, ?string $overview, ?string $releaseDate, float $voteAverage): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $existing = $user->watchlist()->where('tmdb_id', $this->tmdbId)->where('media_type', 'tv')->first();
        if ($existing) {
            $existing->delete();
        } else {
            $user->watchlist()->create([
                'tmdb_id' => $this->tmdbId,
                'media_type' => 'tv',
                'title' => $title,
                'poster_path' => $posterPath,
                'overview' => $overview,
                'release_date' => $releaseDate,
                'vote_average' => $voteAverage,
            ]);
        }
    }

    public function with(Tmdb $tmdb, StreamingAvailability $streaming): array
    {
        $show = $tmdb->details('tv', $this->tmdbId);
        $isFavorited = auth()->check() && auth()->user()->hasFavorited($this->tmdbId, 'tv');
        $isOnWatchlist = auth()->check() && auth()->user()->hasOnWatchlist($this->tmdbId, 'tv');
        $firstAirDate = $show['first_air_date'] ?? '';
        $isUpcoming = $firstAirDate && $firstAirDate > now()->toDateString();

        $seasonData = null;
        $seasons = $show['seasons'] ?? [];
        if (count($seasons) > 0 && ! $isUpcoming) {
            try {
                $seasonData = $tmdb->season($this->tmdbId, $this->selectedSeason);
            } catch (\Throwable) {
            }
        }

        $trailer = collect($show['videos']['results'] ?? [])->first(function ($v) {
            return $v['site'] === 'YouTube' && in_array($v['type'], ['Trailer', 'Teaser']);
        });

        $reviews = Review::where('tmdb_id', $this->tmdbId)
            ->where('media_type', 'tv')
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $userCollections = auth()->check()
            ? auth()->user()->collections()->latest()->get()
            : collect();

        $watchedEpisodes = auth()->check()
            ? auth()->user()->episodeWatches()
                ->where('tmdb_id', $this->tmdbId)
                ->get()
                ->map(fn (EpisodeWatch $ew) => $ew->season_number.'-'.$ew->episode_number)
                ->toArray()
            : [];

        $streamingCountry = $streaming->getUserCountry();
        $streamingData = $streaming->getByTmdbId('tv', $this->tmdbId, $streamingCountry);
        $streamingOptions = $streamingData ? $streaming->getStreamingOptions($streamingData, $streamingCountry) : [];

        return [
            'show' => $show,
            'isFavorited' => $isFavorited,
            'isOnWatchlist' => $isOnWatchlist,
            'isUpcoming' => $isUpcoming,
            'cast' => array_slice($show['credits']['cast'] ?? [], 0, 12),
            'seasons' => $seasons,
            'seasonData' => $seasonData,
            'trailer' => $trailer,
            'similar' => array_slice($show['similar']['results'] ?? [], 0, 6),
            'reviews' => $reviews,
            'userReview' => auth()->check() ? $reviews->firstWhere('user_id', auth()->id()) : null,
            'averageUserRating' => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : null,
            'userCollections' => $userCollections,
            'watchedEpisodes' => $watchedEpisodes,
            'streamingOptions' => $streamingOptions,
        ];
    }
};
?>

<div>
    @php $title = $show['name'] ?? 'Untitled'; @endphp

    {{-- Cinematic Backdrop --}}
    <div class="relative h-[55vh] min-h-[420px] w-full overflow-hidden">
        @if(!empty($show['backdrop_path']))
            <img src="{{ app(Tmdb::class)->backdropUrl($show['backdrop_path']) }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/60 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950/80 via-transparent to-transparent"></div>
    </div>

    <div class="mx-auto -mt-56 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative flex flex-col gap-8 md:flex-row">
            <div class="w-48 shrink-0 md:w-64">
                <div class="relative aspect-[2/3] overflow-hidden rounded-2xl bg-zinc-800 shadow-2xl ring-1 ring-white/[0.08]">
                    @if(!empty($show['poster_path']))
                        <img src="{{ app(Tmdb::class)->imageUrl($show['poster_path']) }}" alt="{{ $title }}" class="h-full w-full object-cover">
                    @endif
                    @if($isUpcoming)
                        <div class="absolute left-3 top-3 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-lg shadow-amber-600/30">
                            Coming Soon
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex-1 pt-4">
                <h1 class="mb-3 text-3xl font-bold tracking-tight md:text-4xl lg:text-5xl">{{ $title }}</h1>

                <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                    @if(!empty($show['first_air_date']))
                        <span class="text-zinc-400">{{ $isUpcoming ? \Carbon\Carbon::parse($show['first_air_date'])->format('M d, Y') : Str::substr($show['first_air_date'], 0, 4) }}</span>
                        <span class="text-zinc-700">&bull;</span>
                    @endif
                    @if(!empty($show['number_of_seasons']))
                        <span class="text-zinc-400">{{ $show['number_of_seasons'] }} {{ Str::plural('Season', $show['number_of_seasons']) }}</span>
                        <span class="text-zinc-700">&bull;</span>
                    @endif
                    @if(!empty($show['vote_average']))
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500/10 px-2.5 py-1 text-sm font-semibold text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            {{ number_format($show['vote_average'], 1) }}
                        </span>
                    @endif
                    @if(!empty($show['status']))
                        <span class="rounded-lg bg-white/[0.06] px-2.5 py-1 text-xs font-medium text-zinc-400">{{ $show['status'] }}</span>
                    @endif
                </div>

                @if(!empty($show['genres']))
                    <div class="mb-5 flex flex-wrap gap-2">
                        @foreach($show['genres'] as $genre)
                            <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                               class="rounded-full border border-white/[0.08] bg-white/[0.04] px-3.5 py-1 text-xs font-medium text-zinc-300 transition hover:border-white/[0.15] hover:bg-white/[0.08] hover:text-white" wire:navigate>
                                {{ $genre['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($show['overview']))
                    <p class="mb-6 max-w-2xl text-[15px] leading-relaxed text-zinc-300/90">{{ $show['overview'] }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-2.5">
                    @if($isUpcoming)
                        @if($trailer)
                            <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId]) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600 hover:shadow-amber-500/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Watch Trailer
                            </a>
                        @endif
                        <div class="inline-flex items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/[0.07] px-4 py-3 text-sm font-medium text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Premieres {{ \Carbon\Carbon::parse($show['first_air_date'])->diffForHumans() }}
                        </div>
                    @else
                        <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => 1, 'episode' => 1]) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600 hover:shadow-amber-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Watch S1 E1
                        </a>
                    @endif
                    <button
                        wire:click="toggleFavorite('{{ addslashes($title) }}', '{{ $show['poster_path'] ?? '' }}')"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition {{ $isFavorited ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' : 'border-white/[0.1] bg-white/[0.03] text-zinc-300 hover:border-white/[0.2] hover:bg-white/[0.06]' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        {{ $isFavorited ? 'Favorited' : 'Favorite' }}
                    </button>
                    <button
                        wire:click="toggleWatchlist('{{ addslashes($title) }}', '{{ $show['poster_path'] ?? '' }}', '{{ addslashes(Str::limit($show['overview'] ?? '', 300)) }}', '{{ $show['first_air_date'] ?? '' }}', {{ $show['vote_average'] ?? 0 }})"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium transition {{ $isOnWatchlist ? 'border-purple-500/30 bg-purple-500/10 text-purple-400' : 'border-white/[0.1] bg-white/[0.03] text-zinc-300 hover:border-white/[0.2] hover:bg-white/[0.06]' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $isOnWatchlist ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}" /></svg>
                        {{ $isOnWatchlist ? 'On Watchlist' : 'Watchlist' }}
                    </button>
                    @include('partials.add-to-collection', ['mediaTitle' => $title, 'mediaPoster' => $show['poster_path'] ?? null])
                </div>
            </div>
        </div>

        {{-- Trailer for upcoming --}}
        @if($isUpcoming && $trailer)
            <section class="mt-12">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-amber-500"></span>
                    Official Trailer
                </h2>
                <div class="aspect-video w-full max-w-3xl overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-white/[0.06]">
                    <iframe
                        src="https://www.youtube.com/embed/{{ $trailer['key'] }}"
                        class="h-full w-full"
                        frameborder="0"
                        allowfullscreen
                        allow="autoplay; encrypted-media"
                    ></iframe>
                </div>
            </section>
        @endif

        {{-- Seasons & Episodes --}}
        @if(!$isUpcoming && count($seasons) > 0)
            <section class="mt-12">
                <h2 class="mb-5 flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-blue-500"></span>
                    Episodes
                </h2>
                <div class="scrollbar-hide mb-4 flex gap-2 overflow-x-auto pb-1">
                    @foreach($seasons as $season)
                        @if(($season['season_number'] ?? 0) > 0)
                            <button
                                wire:click="selectSeason({{ $season['season_number'] }})"
                                class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-medium transition {{ $selectedSeason === $season['season_number'] ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'bg-white/[0.05] text-zinc-400 hover:bg-white/[0.1] hover:text-white' }}"
                            >
                                Season {{ $season['season_number'] }}
                            </button>
                        @endif
                    @endforeach
                </div>

                @if($seasonData && !empty($seasonData['episodes']))
                    @auth
                        @php
                            $seasonEpCount = count($seasonData['episodes']);
                            $watchedInSeason = collect($watchedEpisodes)->filter(fn ($key) => str_starts_with($key, $selectedSeason.'-'))->count();
                        @endphp
                        <div class="mb-4 flex items-center justify-between rounded-xl bg-white/[0.03] px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-800">
                                    <div class="h-full rounded-full bg-green-500 transition-all duration-500" style="width: {{ $seasonEpCount > 0 ? ($watchedInSeason / $seasonEpCount) * 100 : 0 }}%"></div>
                                </div>
                                <span class="text-sm text-zinc-400">{{ $watchedInSeason }}/{{ $seasonEpCount }} watched</span>
                            </div>
                            @if($watchedInSeason < $seasonEpCount)
                                <button wire:click="markSeasonWatched({{ $selectedSeason }}, {{ $seasonEpCount }})"
                                        class="rounded-lg bg-white/[0.06] px-3 py-1.5 text-xs font-medium text-zinc-300 transition hover:bg-white/[0.1] hover:text-white">
                                    Mark All Watched
                                </button>
                            @endif
                        </div>
                    @endauth
                    <div class="space-y-2">
                        @foreach($seasonData['episodes'] as $ep)
                            @php $isWatched = in_array($selectedSeason.'-'.$ep['episode_number'], $watchedEpisodes); @endphp
                            <div class="group flex gap-4 rounded-xl border border-white/[0.04] bg-white/[0.02] p-3 transition hover:border-white/[0.08] hover:bg-white/[0.04] {{ $isWatched ? 'opacity-60' : '' }}">
                                <div class="w-40 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                                    <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $selectedSeason, 'episode' => $ep['episode_number']]) }}" class="relative block">
                                        @if(!empty($ep['still_path']))
                                            <img src="{{ app(Tmdb::class)->imageUrl($ep['still_path'], 'w300') }}" alt="" class="aspect-video w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                        @else
                                            <div class="flex aspect-video items-center justify-center text-zinc-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition group-hover:opacity-100">
                                            <div class="flex size-8 items-center justify-center rounded-full bg-amber-600/90 text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $selectedSeason, 'episode' => $ep['episode_number']]) }}" class="flex-1">
                                            <h3 class="font-medium text-zinc-200 transition group-hover:text-white">
                                                E{{ $ep['episode_number'] }}. {{ $ep['name'] ?? 'Episode '.$ep['episode_number'] }}
                                            </h3>
                                            @if(!empty($ep['runtime']))
                                                <p class="mt-0.5 text-xs text-zinc-500">{{ $ep['runtime'] }} min</p>
                                            @endif
                                        </a>
                                        @auth
                                            <button wire:click="toggleEpisodeWatched({{ $selectedSeason }}, {{ $ep['episode_number'] }})"
                                                    class="ml-2 shrink-0 rounded-full p-1.5 transition {{ $isWatched ? 'bg-green-500/15 text-green-400 hover:bg-red-500/15 hover:text-red-400' : 'bg-white/[0.04] text-zinc-500 hover:bg-green-500/15 hover:text-green-400' }}"
                                                    title="{{ $isWatched ? 'Mark unwatched' : 'Mark watched' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $isWatched ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}" /></svg>
                                            </button>
                                        @endauth
                                    </div>
                                    @if(!empty($ep['overview']))
                                        <p class="mt-1.5 text-sm leading-relaxed text-zinc-400">{{ Str::limit($ep['overview'], 150) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @include('partials.where-to-watch', ['tmdbId' => $tmdbId, 'mediaType' => 'tv'])

        {{-- Cast --}}
        @if(count($cast) > 0)
            <section class="mt-12">
                <h2 class="mb-5 flex items-center gap-2 text-xl font-bold">
                    <span class="h-5 w-1 rounded-full bg-purple-500"></span>
                    Cast
                </h2>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($cast as $person)
                        <a href="{{ route('people.detail', $person['id']) }}" class="group w-20 shrink-0 text-center sm:w-24" wire:navigate>
                            <div class="mx-auto aspect-square w-full overflow-hidden rounded-2xl bg-zinc-800 ring-1 ring-white/[0.06] transition group-hover:ring-amber-500/40">
                                @if(!empty($person['profile_path']))
                                    <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                @endif
                            </div>
                            <p class="mt-2 text-xs font-medium text-zinc-300 transition group-hover:text-amber-400">{{ $person['name'] }}</p>
                            <p class="text-[11px] text-zinc-500">{{ Str::limit($person['character'] ?? '', 20) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @include('partials.reviews-section')

        @if(count($similar) > 0)
            @include('partials.media-row', ['title' => 'Similar Shows', 'items' => $similar, 'type' => 'tv'])
        @endif
    </div>

    <div class="pb-16"></div>
</div>
