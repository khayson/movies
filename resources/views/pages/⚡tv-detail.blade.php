<?php

use App\Models\EpisodeWatch;
use App\Models\Review;
use App\Services\Imdb;
use App\Services\RottenTomatoes;
use App\Services\StreamingAvailability;
use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
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

    public function with(Tmdb $tmdb, StreamingAvailability $streaming, Imdb $imdb, RottenTomatoes $rottenTomatoes): array
    {
        $show = $tmdb->details('tv', $this->tmdbId);

        if (empty($show['id']) && empty($show['name'])) {
            abort(404);
        }

        View::share('ogTitle', ($show['name'] ?? 'TV Show') . ' — ' . config('app.name'));
        View::share('ogDescription', Str::limit($show['overview'] ?? '', 200));
        View::share('ogImage', ! empty($show['backdrop_path']) ? $tmdb->backdropUrl($show['backdrop_path']) : null);
        View::share('ogType', 'video.tv_show');

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
            return ($v['site'] ?? '') === 'YouTube' && in_array($v['type'] ?? '', ['Trailer', 'Teaser']);
        });

        $videos = collect($show['videos']['results'] ?? [])
            ->filter(fn (array $v) => ($v['site'] ?? '') === 'YouTube' && in_array($v['type'] ?? '', ['Trailer', 'Teaser', 'Featurette', 'Clip']))
            ->sortBy(fn (array $v) => match ($v['type'] ?? '') { 'Trailer' => 0, 'Teaser' => 1, default => 2 })
            ->take(6)
            ->values()
            ->all();

        $tmdbReviews = array_slice($show['reviews']['results'] ?? [], 0, 10);

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

        $totalEpisodes = $show['number_of_episodes'] ?? 0;
        $watchedCount = count($watchedEpisodes);
        $progressPercent = $totalEpisodes > 0 ? round(($watchedCount / $totalEpisodes) * 100) : 0;

        $nextEpisode = null;

        if (! $isUpcoming && is_array($seasonData) && ! empty($seasonData['episodes'])) {
            foreach ($seasonData['episodes'] as $episode) {
                if (! is_array($episode)) {
                    continue;
                }

                $episodeNumber = (int) ($episode['episode_number'] ?? 0);

                if ($episodeNumber < 1) {
                    continue;
                }

                if (! in_array($this->selectedSeason.'-'.$episodeNumber, $watchedEpisodes, true)) {
                    $nextEpisode = [
                        'season' => $this->selectedSeason,
                        'episode' => $episodeNumber,
                        'name' => $episode['name'] ?? 'Episode '.$episodeNumber,
                        'still_path' => $episode['still_path'] ?? null,
                        'overview' => $episode['overview'] ?? '',
                    ];

                    break;
                }
            }
        }

        $streamingCountry = $streaming->getUserCountry();
        $streamingData = $streaming->getByTmdbId('tv', $this->tmdbId, $streamingCountry);
        $streamingOptions = $streamingData ? $streaming->getStreamingOptions($streamingData, $streamingCountry) : [];
        $imdbRatings = $imdb->ratings($show['external_ids']['imdb_id'] ?? null);
        $airYear = ! empty($show['first_air_date']) ? (int) Str::substr($show['first_air_date'], 0, 4) : null;
        $rtScores = $rottenTomatoes->scores($show['name'] ?? null, 'tv', $airYear);

        return [
            'show' => $show,
            'isFavorited' => $isFavorited,
            'isOnWatchlist' => $isOnWatchlist,
            'isUpcoming' => $isUpcoming,
            'cast' => array_slice($show['credits']['cast'] ?? [], 0, 12),
            'seasons' => $seasons,
            'seasonData' => $seasonData,
            'trailer' => $trailer,
            'videos' => $videos,
            'similar' => $tmdb->relatedFromDetails($show, 12),
            'reviews' => $reviews,
            'userReview' => auth()->check() ? $reviews->firstWhere('user_id', auth()->id()) : null,
            'averageUserRating' => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : null,
            'tmdbReviews' => $tmdbReviews,
            'userCollections' => $userCollections,
            'watchedEpisodes' => $watchedEpisodes,
            'totalEpisodes' => $totalEpisodes,
            'watchedCount' => $watchedCount,
            'progressPercent' => $progressPercent,
            'nextEpisode' => $nextEpisode,
            'streamingOptions' => $streamingOptions,
            'imdbRatings' => $imdbRatings,
            'rtScores' => $rtScores,
        ];
    }
};
?>

<div>
    @php
        $title = $show['name'] ?? 'Untitled';
        $playSeason = $nextEpisode['season'] ?? 1;
        $playEpisode = $nextEpisode['episode'] ?? 1;
        $playLabel = $watchedCount > 0
            ? 'Continue · S'.$playSeason.' E'.$playEpisode
            : 'Watch S'.$playSeason.' E'.$playEpisode;
        $network = collect($show['networks'] ?? [])->first();
        $creator = collect($show['created_by'] ?? [])->first();
        $seasonList = collect($seasons)->filter(fn ($season) => ($season['season_number'] ?? 0) > 0)->values();
        $defaultTab = ! $isUpcoming && $seasonList->isNotEmpty()
            ? 'episodes'
            : (count($videos) > 0 ? 'trailers' : (count($cast) > 0 ? 'cast' : 'reviews'));
    @endphp

    <section class="hero-bleed relative min-h-[78vh] w-full overflow-hidden lg:min-h-[calc(78vh_+_4rem)]">
        @if(!empty($show['backdrop_path']))
            <img src="{{ app(Tmdb::class)->backdropUrl($show['backdrop_path']) }}" alt="" class="absolute inset-0 size-full scale-105 object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/55 to-zinc-950/20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/40 to-transparent"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(245,158,11,0.12),transparent_45%)]"></div>

        <div class="relative mx-auto flex min-h-[78vh] max-w-7xl flex-col justify-end gap-8 px-4 pb-10 pt-28 sm:px-6 lg:min-h-[calc(78vh_+_4rem)] lg:flex-row lg:items-end lg:px-8 lg:pb-14 lg:pt-36">
            <div class="hidden w-48 shrink-0 sm:block lg:w-56">
                <div class="relative aspect-[2/3] overflow-hidden rounded-2xl bg-zinc-800 shadow-[0_30px_80px_rgba(0,0,0,0.55)] ring-1 ring-white/10">
                    @if(!empty($show['poster_path']))
                        <img src="{{ app(Tmdb::class)->imageUrl($show['poster_path']) }}" alt="{{ $title }}" class="size-full object-cover">
                    @endif
                    @if($isUpcoming)
                        <div class="absolute left-3 top-3 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-white shadow-lg shadow-amber-600/30">
                            Coming Soon
                        </div>
                    @endif
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-300">Series</span>
                    @if(!empty($show['status']))
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-zinc-300">{{ $show['status'] }}</span>
                    @endif
                    @if($network)
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-zinc-300">{{ $network['name'] }}</span>
                    @endif
                </div>

                <h1 class="max-w-4xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">{{ $title }}</h1>

                @if(!empty($show['tagline']))
                    <p class="mt-3 max-w-2xl text-base italic text-zinc-400">{{ $show['tagline'] }}</p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-2 text-sm">
                    @if(!empty($show['first_air_date']))
                        <span class="text-zinc-300">{{ $isUpcoming ? \Carbon\Carbon::parse($show['first_air_date'])->format('M d, Y') : Str::substr($show['first_air_date'], 0, 4) }}</span>
                    @endif
                    @if(!empty($show['last_air_date']) && ! $isUpcoming)
                        <span class="text-zinc-600">–</span>
                        <span class="text-zinc-300">{{ Str::substr($show['last_air_date'], 0, 4) }}</span>
                    @endif
                    @if(!empty($show['number_of_seasons']))
                        <span class="text-zinc-600">·</span>
                        <span class="text-zinc-300">{{ $show['number_of_seasons'] }} {{ Str::plural('Season', $show['number_of_seasons']) }}</span>
                    @endif
                    @if(!empty($show['vote_average']))
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500/10 px-2.5 py-1 text-sm font-semibold text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            {{ number_format($show['vote_average'], 1) }}
                        </span>
                    @endif
                    @if(!empty($imdbRatings['rating']))
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-yellow-500/10 px-2.5 py-1 text-sm font-semibold text-yellow-400">
                            <span class="text-[10px] font-black">IMDb</span>
                            {{ number_format($imdbRatings['rating'], 1) }}
                        </span>
                    @endif
                    @if(($imdbRatings['metascore'] ?? null) !== null)
                        @php
                            $meta = (int) $imdbRatings['metascore'];
                            $metaTone = $meta >= 61 ? 'bg-emerald-500/15 text-emerald-400' : ($meta >= 40 ? 'bg-yellow-500/15 text-yellow-400' : 'bg-red-500/15 text-red-400');
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-semibold {{ $metaTone }}">
                            <span class="text-[10px] font-black uppercase">Meta</span>
                            {{ $meta }}
                        </span>
                    @endif
                    @if(($rtScores['tomatometer'] ?? null) !== null)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-red-500/10 px-2.5 py-1 text-sm font-semibold text-red-400">
                            <span class="text-[10px] font-black uppercase">RT</span>
                            {{ $rtScores['tomatometer'] }}%
                        </span>
                    @endif
                </div>

                @if(!empty($show['genres']))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($show['genres'] as $genre)
                            @if(!empty($genre['id']))
                                <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'] ?? 'genre')]) }}"
                                   class="rounded-full border border-white/10 bg-white/5 px-3.5 py-1 text-xs font-medium text-zinc-300 transition hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-white" wire:navigate>
                                    {{ $genre['name'] ?? 'Genre' }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(!empty($show['overview']))
                    <p class="mt-5 max-w-3xl text-[15px] leading-relaxed text-zinc-300/90">{{ $show['overview'] }}</p>
                @endif

                <div class="mt-7 flex flex-wrap items-center gap-2.5">
                    @if($isUpcoming)
                        @if($trailer)
                            <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId]) }}"
                               class="inline-flex h-12 items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-6 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Watch Trailer
                            </a>
                        @endif
                        <div class="inline-flex h-12 items-center gap-2 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 text-sm font-medium text-amber-300">
                            Premieres {{ \Carbon\Carbon::parse($show['first_air_date'])->diffForHumans() }}
                        </div>
                    @else
                        <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $playSeason, 'episode' => $playEpisode]) }}"
                           class="inline-flex h-12 items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-6 text-sm font-semibold text-white shadow-lg shadow-amber-600/25 transition hover:from-amber-500 hover:to-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            {{ $playLabel }}
                        </a>
                    @endif
                    <button
                        wire:click="toggleFavorite('{{ addslashes($title) }}', '{{ $show['poster_path'] ?? '' }}')"
                        class="inline-flex h-12 items-center gap-2 rounded-xl border px-4 text-sm font-medium transition {{ $isFavorited ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' : 'border-white/10 bg-white/5 text-zinc-300 hover:border-white/20 hover:bg-white/10' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <span class="hidden sm:inline">{{ $isFavorited ? 'Favorited' : 'Favorite' }}</span>
                    </button>
                    <button
                        wire:click="toggleWatchlist('{{ addslashes($title) }}', '{{ $show['poster_path'] ?? '' }}', '{{ addslashes(Str::limit($show['overview'] ?? '', 300)) }}', '{{ $show['first_air_date'] ?? '' }}', {{ $show['vote_average'] ?? 0 }})"
                        class="inline-flex h-12 items-center gap-2 rounded-xl border px-4 text-sm font-medium transition {{ $isOnWatchlist ? 'border-purple-500/30 bg-purple-500/10 text-purple-400' : 'border-white/10 bg-white/5 text-zinc-300 hover:border-white/20 hover:bg-white/10' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $isOnWatchlist ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}" /></svg>
                        {{ $isOnWatchlist ? 'On Watchlist' : 'Watchlist' }}
                    </button>
                    @include('partials.add-to-collection', ['mediaTitle' => $title, 'mediaPoster' => $show['poster_path'] ?? null])
                    @include('partials.share-buttons', ['shareTitle' => $title . ' — StreamVault', 'shareUrl' => route('tv.detail', $this->tmdbId)])
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/6 bg-white/3 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Seasons</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $show['number_of_seasons'] ?? $seasonList->count() }}</p>
            </div>
            <div class="rounded-2xl border border-white/6 bg-white/3 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Episodes</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-white">{{ $totalEpisodes }}</p>
            </div>
            <div class="rounded-2xl border border-white/6 bg-white/3 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Created by</p>
                <p class="mt-1 truncate text-lg font-semibold text-white">{{ $creator['name'] ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-white/6 bg-white/3 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Your progress</p>
                @auth
                    <p class="mt-1 text-lg font-semibold text-emerald-400">{{ $watchedCount }}/{{ max((int) $totalEpisodes, 1) }}</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/8">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400" style="width: {{ $progressPercent }}%"></div>
                    </div>
                @else
                    <p class="mt-1 text-lg font-semibold text-zinc-400">Sign in to track</p>
                @endauth
            </div>
        </div>

        @auth
            @if($nextEpisode && $watchedCount > 0)
                <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $nextEpisode['season'], 'episode' => $nextEpisode['episode']]) }}"
                   class="mt-6 flex gap-4 overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-500/8 transition hover:border-amber-500/40 hover:bg-amber-500/12">
                    <div class="w-40 shrink-0 bg-zinc-900 sm:w-56">
                        @if(!empty($nextEpisode['still_path']))
                            <img src="{{ app(Tmdb::class)->imageUrl($nextEpisode['still_path'], 'w300') }}" alt="" class="aspect-video size-full object-cover">
                        @else
                            <div class="flex aspect-video items-center justify-center text-amber-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-10" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col justify-center py-4 pr-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-400">Continue watching</p>
                        <p class="mt-1 text-lg font-semibold text-white">S{{ $nextEpisode['season'] }} E{{ $nextEpisode['episode'] }} · {{ $nextEpisode['name'] }}</p>
                        @if(!empty($nextEpisode['overview']))
                            <p class="mt-1 line-clamp-2 text-sm text-zinc-400">{{ $nextEpisode['overview'] }}</p>
                        @endif
                    </div>
                </a>
            @endif
        @endauth

        <div x-data="{ tab: '{{ $defaultTab }}' }" class="mt-12">
            <div class="scrollbar-hide flex gap-2 overflow-x-auto border-b border-white/8 pb-px">
                @if(! $isUpcoming && $seasonList->isNotEmpty())
                    <button type="button" @click="tab = 'episodes'" :class="tab === 'episodes' ? 'border-amber-500 text-white' : 'border-transparent text-zinc-400 hover:text-white'" class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition">Episodes</button>
                @endif
                @if(count($videos) > 0)
                    <button type="button" @click="tab = 'trailers'" :class="tab === 'trailers' ? 'border-amber-500 text-white' : 'border-transparent text-zinc-400 hover:text-white'" class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition">Trailers</button>
                @endif
                @if(count($cast) > 0)
                    <button type="button" @click="tab = 'cast'" :class="tab === 'cast' ? 'border-amber-500 text-white' : 'border-transparent text-zinc-400 hover:text-white'" class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition">Cast</button>
                @endif
                <button type="button" @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-amber-500 text-white' : 'border-transparent text-zinc-400 hover:text-white'" class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition">Reviews</button>
            </div>

            @if(! $isUpcoming && $seasonList->isNotEmpty())
                <div x-show="tab === 'episodes'" class="pt-6">
                    <div class="scrollbar-hide mb-5 flex gap-2 overflow-x-auto">
                        @foreach($seasonList as $season)
                            <button
                                wire:click="selectSeason({{ $season['season_number'] }})"
                                class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium transition {{ $selectedSeason === $season['season_number'] ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'bg-white/5 text-zinc-400 hover:bg-white/10 hover:text-white' }}"
                            >
                                Season {{ $season['season_number'] }}
                                @if(!empty($season['episode_count']))
                                    <span class="ml-1 text-xs opacity-70">{{ $season['episode_count'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    @if($seasonData && !empty($seasonData['episodes']))
                        @auth
                            @php
                                $seasonEpCount = count($seasonData['episodes']);
                                $watchedInSeason = collect($watchedEpisodes)->filter(fn ($key) => str_starts_with($key, $selectedSeason.'-'))->count();
                            @endphp
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/6 bg-white/3 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-1.5 w-28 overflow-hidden rounded-full bg-zinc-800">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $seasonEpCount > 0 ? ($watchedInSeason / $seasonEpCount) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="text-sm text-zinc-400">{{ $watchedInSeason }}/{{ $seasonEpCount }} watched</span>
                                </div>
                                @if($watchedInSeason < $seasonEpCount)
                                    <button wire:click="markSeasonWatched({{ $selectedSeason }}, {{ $seasonEpCount }})"
                                            class="rounded-lg bg-white/6 px-3 py-1.5 text-xs font-medium text-zinc-300 transition hover:bg-white/10 hover:text-white">
                                        Mark All Watched
                                    </button>
                                @endif
                            </div>
                        @endauth

                        <div wire:loading.flex wire:target="selectSeason" class="min-h-40 items-center justify-center rounded-2xl border border-white/6 bg-white/3">
                            <p class="text-sm text-zinc-400">Loading episodes…</p>
                        </div>

                        <div wire:loading.remove wire:target="selectSeason" class="grid gap-3">
                            @foreach($seasonData['episodes'] as $ep)
                                @php $isWatched = in_array($selectedSeason.'-'.$ep['episode_number'], $watchedEpisodes); @endphp
                                <div class="group grid grid-cols-[auto_1fr] gap-4 rounded-2xl border border-white/6 bg-white/3 p-3 transition hover:border-white/12 hover:bg-white/6 {{ $isWatched ? 'opacity-70' : '' }}">
                                    <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $selectedSeason, 'episode' => $ep['episode_number']]) }}" class="relative w-36 shrink-0 overflow-hidden rounded-xl bg-zinc-800 sm:w-52">
                                        @if(!empty($ep['still_path']))
                                            <img src="{{ app(Tmdb::class)->imageUrl($ep['still_path'], 'w300') }}" alt="" class="aspect-video w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                        @else
                                            <div class="flex aspect-video items-center justify-center text-zinc-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 transition group-hover:opacity-100">
                                            <div class="flex size-10 items-center justify-center rounded-full bg-amber-600 text-white shadow-lg shadow-amber-600/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4 translate-x-px" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                            </div>
                                        </div>
                                        <span class="absolute left-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-white">E{{ $ep['episode_number'] }}</span>
                                    </a>
                                    <div class="min-w-0 py-0.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $selectedSeason, 'episode' => $ep['episode_number']]) }}" class="min-w-0 flex-1">
                                                <h3 class="font-semibold text-zinc-100 transition group-hover:text-white">{{ $ep['name'] ?? 'Episode '.$ep['episode_number'] }}</h3>
                                                <p class="mt-0.5 text-xs text-zinc-500">
                                                    @if(!empty($ep['air_date']))
                                                        {{ \Carbon\Carbon::parse($ep['air_date'])->format('M d, Y') }}
                                                    @endif
                                                    @if(!empty($ep['runtime']))
                                                        <span class="text-zinc-600">·</span> {{ $ep['runtime'] }} min
                                                    @endif
                                                </p>
                                            </a>
                                            @auth
                                                <button wire:click="toggleEpisodeWatched({{ $selectedSeason }}, {{ $ep['episode_number'] }})"
                                                        class="shrink-0 rounded-full p-1.5 transition {{ $isWatched ? 'bg-emerald-500/15 text-emerald-400 hover:bg-red-500/15 hover:text-red-400' : 'bg-white/5 text-zinc-500 hover:bg-emerald-500/15 hover:text-emerald-400' }}"
                                                        title="{{ $isWatched ? 'Mark unwatched' : 'Mark watched' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $isWatched ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}" /></svg>
                                                </button>
                                            @endauth
                                        </div>
                                        @if(!empty($ep['overview']))
                                            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-zinc-400">{{ $ep['overview'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if(count($videos) > 0)
                <div x-show="tab === 'trailers'" x-cloak class="pt-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($videos as $video)
                            <div x-data="{ playing: false }" class="group">
                                <div class="relative aspect-video overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-white/8 transition group-hover:ring-white/20">
                                    <template x-if="!playing">
                                        <div class="relative size-full cursor-pointer" @click="playing = true">
                                            <img src="https://img.youtube.com/vi/{{ $video['key'] }}/hqdefault.jpg" alt="{{ $video['name'] }}" class="size-full object-cover" loading="lazy">
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover:bg-black/20">
                                                <div class="flex size-14 items-center justify-center rounded-full bg-red-600/90 text-white shadow-lg shadow-red-600/30 transition group-hover:scale-110">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                                </div>
                                            </div>
                                            <div class="absolute bottom-2 left-2">
                                                <span class="rounded bg-black/70 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">{{ $video['type'] }}</span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="playing">
                                        <iframe
                                            src="https://www.youtube.com/embed/{{ $video['key'] }}?autoplay=1"
                                            class="size-full"
                                            frameborder="0"
                                            allowfullscreen
                                            allow="autoplay; encrypted-media"
                                        ></iframe>
                                    </template>
                                </div>
                                <p class="mt-2 text-sm font-medium text-zinc-300">{{ Str::limit($video['name'], 50) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(count($cast) > 0)
                <div x-show="tab === 'cast'" x-cloak class="pt-6">
                    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6">
                        @foreach($cast as $person)
                            @if(!empty($person['id']))
                                <a href="{{ route('people.detail', $person['id']) }}" class="group text-center" wire:navigate>
                                    <div class="mx-auto aspect-square overflow-hidden rounded-2xl bg-zinc-800 ring-1 ring-white/8 transition group-hover:ring-amber-500/40">
                                        @if(!empty($person['profile_path']))
                                            <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}" class="size-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                        @endif
                                    </div>
                                    <p class="mt-2 text-xs font-medium text-zinc-300 transition group-hover:text-amber-400">{{ $person['name'] }}</p>
                                    <p class="text-[11px] text-zinc-500">{{ Str::limit($person['character'] ?? '', 24) }}</p>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div x-show="tab === 'reviews'" x-cloak class="pt-2">
                @include('partials.reviews-section', [
                    'tmdbRating' => $show['vote_average'] ?? null,
                    'tmdbVoteCount' => $show['vote_count'] ?? 0,
                    'imdbId' => $show['external_ids']['imdb_id'] ?? null,
                    'imdbRating' => $imdbRatings['rating'] ?? null,
                    'imdbVotes' => $imdbRatings['votes'] ?? null,
                    'metascore' => $imdbRatings['metascore'] ?? null,
                    'rtTomatometer' => $rtScores['tomatometer'] ?? null,
                    'rtAudience' => $rtScores['audience'] ?? null,
                    'rtConsensus' => $rtScores['consensus'] ?? null,
                ])
            </div>
        </div>

        @include('partials.where-to-watch', ['tmdbId' => $tmdbId, 'mediaType' => 'tv'])

        @if(count($similar) > 0)
            @include('partials.media-row', ['title' => 'More Like This', 'items' => $similar, 'type' => 'tv', 'style' => 'scroll'])
        @endif
    </div>
</div>
