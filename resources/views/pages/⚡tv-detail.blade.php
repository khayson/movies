<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public int $tmdbId;

    public int $selectedSeason = 1;

    public function mount(int $tmdbId): void
    {
        $this->tmdbId = $tmdbId;
    }

    public function selectSeason(int $season): void
    {
        $this->selectedSeason = $season;
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

    public function with(Tmdb $tmdb): array
    {
        $show = $tmdb->details('tv', $this->tmdbId);
        $isFavorited = auth()->check() && auth()->user()->hasFavorited($this->tmdbId, 'tv');
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

        return [
            'show' => $show,
            'isFavorited' => $isFavorited,
            'isUpcoming' => $isUpcoming,
            'cast' => array_slice($show['credits']['cast'] ?? [], 0, 12),
            'seasons' => $seasons,
            'seasonData' => $seasonData,
            'trailer' => $trailer,
            'similar' => array_slice($show['similar']['results'] ?? [], 0, 6),
        ];
    }
};
?>

<div>
    @php $title = $show['name'] ?? 'Untitled'; @endphp

    <div class="relative h-[50vh] min-h-[400px] w-full overflow-hidden">
        @if(!empty($show['backdrop_path']))
            <img src="{{ app(Tmdb::class)->backdropUrl($show['backdrop_path']) }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/70 to-zinc-950/30"></div>
    </div>

    <div class="mx-auto -mt-48 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative flex flex-col gap-8 md:flex-row">
            <div class="w-48 shrink-0 md:w-64">
                <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-800 shadow-2xl">
                    @if(!empty($show['poster_path']))
                        <img src="{{ app(Tmdb::class)->imageUrl($show['poster_path']) }}" alt="{{ $title }}" class="h-full w-full object-cover">
                    @endif
                    @if($isUpcoming)
                        <div class="absolute left-3 top-3 rounded-md bg-amber-600 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-white shadow-lg">
                            Coming Soon
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex-1 pt-4">
                <h1 class="mb-2 text-3xl font-bold md:text-4xl">{{ $title }}</h1>

                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-zinc-400">
                    @if(!empty($show['first_air_date']))
                        <span>{{ $isUpcoming ? \Carbon\Carbon::parse($show['first_air_date'])->format('M d, Y') : Str::substr($show['first_air_date'], 0, 4) }}</span>
                    @endif
                    @if(!empty($show['number_of_seasons']))
                        <span>{{ $show['number_of_seasons'] }} {{ Str::plural('Season', $show['number_of_seasons']) }}</span>
                    @endif
                    @if(!empty($show['vote_average']))
                        <span class="flex items-center gap-1 text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            {{ number_format($show['vote_average'], 1) }}
                        </span>
                    @endif
                    @if(!empty($show['status']))
                        <span class="rounded bg-zinc-800 px-2 py-0.5 text-xs font-medium">{{ $show['status'] }}</span>
                    @endif
                </div>

                @if(!empty($show['genres']))
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach($show['genres'] as $genre)
                            <a href="{{ route('genres.browse', ['type' => 'tv', 'genreId' => $genre['id'], 'genreName' => Str::slug($genre['name'])]) }}"
                               class="rounded-full bg-zinc-800 px-3 py-1 text-xs font-medium text-zinc-300 transition hover:bg-zinc-700 hover:text-white" wire:navigate>
                                {{ $genre['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if(!empty($show['overview']))
                    <p class="mb-6 max-w-2xl leading-relaxed text-zinc-300">{{ $show['overview'] }}</p>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if($isUpcoming)
                        @if($trailer)
                            <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId]) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:bg-amber-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                Watch Trailer
                            </a>
                        @endif
                        <div class="inline-flex items-center gap-2 rounded-lg border border-amber-600/40 bg-amber-600/10 px-4 py-3 text-sm font-medium text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Premieres {{ \Carbon\Carbon::parse($show['first_air_date'])->diffForHumans() }}
                        </div>
                    @else
                        <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => 1, 'episode' => 1]) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-600/20 transition hover:bg-amber-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Watch S1 E1
                        </a>
                    @endif
                    <button
                        wire:click="toggleFavorite('{{ addslashes($title) }}', '{{ $show['poster_path'] ?? '' }}')"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium transition {{ $isFavorited ? 'border-amber-600 bg-amber-600/10 text-amber-400' : 'border-zinc-700 text-zinc-400 hover:border-zinc-500' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        {{ $isFavorited ? 'Favorited' : 'Add to Favorites' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Trailer for upcoming --}}
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

        {{-- Seasons & Episodes (only for released shows) --}}
        @if(!$isUpcoming && count($seasons) > 0)
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-bold">Episodes</h2>
                <div class="scrollbar-hide mb-4 flex gap-2 overflow-x-auto pb-1">
                    @foreach($seasons as $season)
                        @if(($season['season_number'] ?? 0) > 0)
                            <button
                                wire:click="selectSeason({{ $season['season_number'] }})"
                                class="whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition {{ $selectedSeason === $season['season_number'] ? 'bg-amber-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700' }}"
                            >
                                Season {{ $season['season_number'] }}
                            </button>
                        @endif
                    @endforeach
                </div>

                @if($seasonData && !empty($seasonData['episodes']))
                    <div class="space-y-3">
                        @foreach($seasonData['episodes'] as $ep)
                            <a href="{{ route('watch', ['type' => 'tv', 'tmdbId' => $this->tmdbId, 'season' => $selectedSeason, 'episode' => $ep['episode_number']]) }}"
                               class="flex gap-4 rounded-lg bg-zinc-900 p-4 transition hover:bg-zinc-800">
                                <div class="w-40 shrink-0 overflow-hidden rounded-lg bg-zinc-800">
                                    @if(!empty($ep['still_path']))
                                        <img src="{{ app(Tmdb::class)->imageUrl($ep['still_path'], 'w300') }}" alt="" class="aspect-video w-full object-cover" loading="lazy">
                                    @else
                                        <div class="flex aspect-video items-center justify-center text-zinc-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium text-zinc-200">
                                        E{{ $ep['episode_number'] }}. {{ $ep['name'] ?? 'Episode '.$ep['episode_number'] }}
                                    </h3>
                                    @if(!empty($ep['runtime']))
                                        <p class="text-xs text-zinc-500">{{ $ep['runtime'] }} min</p>
                                    @endif
                                    @if(!empty($ep['overview']))
                                        <p class="mt-1 text-sm leading-relaxed text-zinc-400">{{ Str::limit($ep['overview'], 150) }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Cast --}}
        @if(count($cast) > 0)
            <section class="mt-12">
                <h2 class="mb-4 text-xl font-bold">Cast</h2>
                <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
                    @foreach($cast as $person)
                        <div class="w-20 shrink-0 text-center sm:w-24">
                            <div class="mx-auto aspect-square w-full overflow-hidden rounded-full bg-zinc-800">
                                @if(!empty($person['profile_path']))
                                    <img src="{{ app(Tmdb::class)->imageUrl($person['profile_path'], 'w185') }}" alt="{{ $person['name'] }}" class="h-full w-full object-cover" loading="lazy">
                                @endif
                            </div>
                            <p class="mt-2 text-xs font-medium text-zinc-300">{{ $person['name'] }}</p>
                            <p class="text-xs text-zinc-500">{{ Str::limit($person['character'] ?? '', 20) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if(count($similar) > 0)
            @include('partials.media-row', ['title' => 'Similar Shows', 'items' => $similar, 'type' => 'tv'])
        @endif
    </div>

    <div class="pb-16"></div>
</div>
