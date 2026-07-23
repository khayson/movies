<?php

use App\Models\Review;
use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public int $tmdbId;

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

    public function submitReview(): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $this->validate();

        $user->reviews()->updateOrCreate(
            ['tmdb_id' => $this->tmdbId, 'media_type' => 'movie'],
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
        auth()->user()?->reviews()->where('tmdb_id', $this->tmdbId)->where('media_type', 'movie')->delete();
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
            ['tmdb_id' => $this->tmdbId, 'media_type' => 'movie'],
            [
                'title' => $title,
                'poster_path' => $posterPath,
                'sort_order' => $collection->items()->count(),
            ],
        );

        $this->showCollectionPicker = false;
    }

    public function toggleFavorite(string $title, ?string $posterPath): void
    {
        $user = auth()->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $existing = $user->favorites()->where('tmdb_id', $this->tmdbId)->where('media_type', 'movie')->first();
        if ($existing) {
            $existing->delete();
        } else {
            $user->favorites()->create([
                'tmdb_id' => $this->tmdbId,
                'media_type' => 'movie',
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

        $existing = $user->watchlist()->where('tmdb_id', $this->tmdbId)->where('media_type', 'movie')->first();
        if ($existing) {
            $existing->delete();
        } else {
            $user->watchlist()->create([
                'tmdb_id' => $this->tmdbId,
                'media_type' => 'movie',
                'title' => $title,
                'poster_path' => $posterPath,
                'overview' => $overview,
                'release_date' => $releaseDate,
                'vote_average' => $voteAverage,
            ]);
        }
    }

    public function with(Tmdb $tmdb): array
    {
        $movie = $tmdb->details('movie', $this->tmdbId);
        $isFavorited = auth()->check() && auth()->user()->hasFavorited($this->tmdbId, 'movie');
        $isOnWatchlist = auth()->check() && auth()->user()->hasOnWatchlist($this->tmdbId, 'movie');
        $releaseDate = $movie['release_date'] ?? '';
        $isUpcoming = $releaseDate && $releaseDate > now()->toDateString();

        $reviews = Review::where('tmdb_id', $this->tmdbId)
            ->where('media_type', 'movie')
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $userReview = auth()->check()
            ? $reviews->firstWhere('user_id', auth()->id())
            : null;

        $userCollections = auth()->check()
            ? auth()->user()->collections()->latest()->get()
            : collect();

        return [
            'movie' => $movie,
            'isFavorited' => $isFavorited,
            'isOnWatchlist' => $isOnWatchlist,
            'isUpcoming' => $isUpcoming,
            'cast' => array_slice($movie['credits']['cast'] ?? [], 0, 12),
            'trailer' => collect($movie['videos']['results'] ?? [])->firstWhere('type', 'Trailer'),
            'similar' => array_slice($movie['similar']['results'] ?? [], 0, 6),
            'reviews' => $reviews,
            'userReview' => $userReview,
            'averageUserRating' => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : null,
            'userCollections' => $userCollections,
        ];
    }
};
?>

<div>
    @php $title = $movie['title'] ?? 'Untitled'; @endphp

    {{-- Backdrop --}}
    <div class="relative h-[50vh] min-h-[400px] w-full overflow-hidden">
        @if(!empty($movie['backdrop_path']))
            <img src="{{ app(Tmdb::class)->backdropUrl($movie['backdrop_path']) }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30"></div>
    </div>

    <div class="mx-auto -mt-48 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative flex flex-col gap-8 md:flex-row">
            {{-- Poster --}}
            <div class="w-48 shrink-0 md:w-64">
                <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800 shadow-2xl">
                    @if(!empty($movie['poster_path']))
                        <img src="{{ app(Tmdb::class)->imageUrl($movie['poster_path']) }}" alt="{{ $title }}" class="h-full w-full object-cover">
                    @endif
                    @if($isUpcoming)
                        <div class="absolute left-3 top-3 rounded-md bg-amber-600 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-white shadow-lg">
                            Coming Soon
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-1 pt-4">
                <h1 class="mb-2 text-3xl font-bold md:text-4xl">{{ $title }}</h1>

                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-zinc-400">
                    @if(!empty($movie['release_date']))
                        <span>{{ $isUpcoming ? \Carbon\Carbon::parse($movie['release_date'])->format('M d, Y') : Str::substr($movie['release_date'], 0, 4) }}</span>
                    @endif
                    @if(!empty($movie['runtime']))
                        <span>{{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m</span>
                    @endif
                    @if(!empty($movie['vote_average']))
                        <span class="flex items-center gap-1 text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            {{ number_format($movie['vote_average'], 1) }}
                        </span>
                    @endif
                    @if(!empty($movie['status']))
                        <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-medium">{{ $movie['status'] }}</span>
                    @endif
                </div>

                @if(!empty($movie['genres']))
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($movie['genres'] as $genre)
                            <a href="{{ route('genres.browse', ['type' => 'movie', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                               class="rounded-full bg-zinc-800 px-3 py-1 text-xs font-medium text-zinc-300 transition hover:bg-zinc-700 hover:text-white" wire:navigate>
                                {{ $genre['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($movie['overview']))
                    <p class="mb-6 max-w-2xl leading-relaxed text-zinc-300">{{ $movie['overview'] }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if($isUpcoming)
                        {{-- Upcoming: show trailer button --}}
                        @if($trailer)
                            <a href="{{ route('watch', ['type' => 'movie', 'tmdbId' => $this->tmdbId]) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:bg-amber-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Watch Trailer
                            </a>
                        @endif
                        <div class="inline-flex items-center gap-2 rounded-lg border border-amber-600/40 bg-amber-600/10 px-4 py-3 text-sm font-medium text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Releases {{ \Carbon\Carbon::parse($movie['release_date'])->diffForHumans() }}
                        </div>
                    @else
                        <a href="{{ route('watch', ['type' => 'movie', 'tmdbId' => $this->tmdbId]) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:bg-amber-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Watch Now
                        </a>
                    @endif
                    <button
                        wire:click="toggleFavorite('{{ addslashes($title) }}', '{{ $movie['poster_path'] ?? '' }}')"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium transition {{ $isFavorited ? 'border-amber-600 bg-amber-600/10 text-amber-400' : 'border-zinc-700 text-zinc-400 hover:border-zinc-500' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        {{ $isFavorited ? 'Favorited' : 'Favorite' }}
                    </button>
                    <button
                        wire:click="toggleWatchlist('{{ addslashes($title) }}', '{{ $movie['poster_path'] ?? '' }}', '{{ addslashes(Str::limit($movie['overview'] ?? '', 300)) }}', '{{ $movie['release_date'] ?? '' }}', {{ $movie['vote_average'] ?? 0 }})"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium transition {{ $isOnWatchlist ? 'border-purple-600 bg-purple-600/10 text-purple-400' : 'border-zinc-700 text-zinc-400 hover:border-zinc-500' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $isOnWatchlist ? 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' : 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}" /></svg>
                        {{ $isOnWatchlist ? 'On Watchlist' : 'Watchlist' }}
                    </button>
                    @include('partials.add-to-collection', ['mediaTitle' => $title, 'mediaPoster' => $movie['poster_path'] ?? null])
                </div>
            </div>
        </div>

        {{-- Trailer embed for upcoming --}}
        @if($isUpcoming && $trailer)
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-bold">Official Trailer</h2>
                <div class="aspect-video w-full max-w-3xl overflow-hidden rounded-xl bg-zinc-900">
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

        {{-- Cast --}}
        @if(count($cast) > 0)
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-bold">Cast</h2>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($cast as $person)
                        <a href="{{ route('people.detail', $person['id']) }}" class="group w-20 shrink-0 text-center sm:w-24" wire:navigate>
                            <div class="mx-auto aspect-square w-full overflow-hidden rounded-full bg-zinc-800">
                                @if(!empty($person['profile_path']))
                                    <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}" class="h-full w-full object-cover" loading="lazy">
                                @endif
                            </div>
                            <p class="mt-2 text-xs font-medium text-zinc-300 group-hover:text-amber-400">{{ $person['name'] }}</p>
                            <p class="text-xs text-zinc-500">{{ Str::limit($person['character'] ?? '', 20) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @include('partials.reviews-section')

        {{-- Similar --}}
        @if(count($similar) > 0)
            @include('partials.media-row', ['title' => 'Similar Movies', 'items' => $similar, 'type' => 'movie'])
        @endif
    </div>

    <div class="pb-16"></div>
</div>
