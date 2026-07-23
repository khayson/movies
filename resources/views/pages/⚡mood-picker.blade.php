<?php

use App\Services\Tmdb;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('What\'s Your Mood? — StreamVault')]
class extends Component
{
    #[Url]
    public string $mood = '';

    /**
     * @return array<string, array{label: string, emoji: string, genres: string, type: string}>
     */
    private function moods(): array
    {
        return [
            'feel-good' => ['label' => 'Feel Good', 'emoji' => '😊', 'genres' => '35,10751', 'type' => 'movie'],
            'thrilling' => ['label' => 'Thrilling', 'emoji' => '😱', 'genres' => '53,27', 'type' => 'movie'],
            'romantic' => ['label' => 'Romantic', 'emoji' => '💕', 'genres' => '10749', 'type' => 'movie'],
            'mind-bending' => ['label' => 'Mind-Bending', 'emoji' => '🧠', 'genres' => '878,9648', 'type' => 'movie'],
            'epic-adventure' => ['label' => 'Epic Adventure', 'emoji' => '⚔️', 'genres' => '12,14', 'type' => 'movie'],
            'laugh-out-loud' => ['label' => 'Laugh Out Loud', 'emoji' => '🤣', 'genres' => '35', 'type' => 'movie'],
            'dark-gritty' => ['label' => 'Dark & Gritty', 'emoji' => '🌑', 'genres' => '80,53', 'type' => 'movie'],
            'animated' => ['label' => 'Animated', 'emoji' => '🎨', 'genres' => '16', 'type' => 'movie'],
            'documentary' => ['label' => 'Learn Something', 'emoji' => '📚', 'genres' => '99', 'type' => 'movie'],
            'action-packed' => ['label' => 'Action Packed', 'emoji' => '💥', 'genres' => '28', 'type' => 'movie'],
            'binge-worthy' => ['label' => 'Binge-Worthy TV', 'emoji' => '📺', 'genres' => '18', 'type' => 'tv'],
            'nostalgic' => ['label' => 'Nostalgic', 'emoji' => '✨', 'genres' => '10751,35', 'type' => 'movie'],
        ];
    }

    public function selectMood(string $mood): void
    {
        $this->mood = $mood;
    }

    public function with(Tmdb $tmdb): array
    {
        $moods = $this->moods();
        $results = [];

        if ($this->mood && isset($moods[$this->mood])) {
            $selected = $moods[$this->mood];
            $genreIds = explode(',', $selected['genres']);
            $results = $tmdb->get("/discover/{$selected['type']}", [
                'with_genres' => $selected['genres'],
                'sort_by' => 'popularity.desc',
                'vote_average.gte' => 6,
                'page' => 1,
            ])['results'] ?? [];
        }

        return [
            'moods' => $moods,
            'results' => $results,
            'selectedMood' => $this->mood ? ($moods[$this->mood] ?? null) : null,
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <h1 class="mb-2 text-3xl font-bold">What's Your Mood?</h1>
            <p class="text-zinc-400">Pick a vibe and we'll find the perfect titles for you</p>
        </div>

        {{-- Mood Grid --}}
        <div class="mx-auto mb-10 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            @foreach($moods as $key => $m)
                <button wire:click="selectMood('{{ $key }}')"
                        class="rounded-xl border p-4 text-center transition {{ $mood === $key ? 'border-amber-600 bg-amber-600/10' : 'border-zinc-800 bg-zinc-900/50 hover:border-zinc-700 hover:bg-zinc-900' }}">
                    <span class="text-2xl">{{ $m['emoji'] }}</span>
                    <p class="mt-1 text-sm font-medium {{ $mood === $key ? 'text-amber-400' : 'text-zinc-300' }}">{{ $m['label'] }}</p>
                </button>
            @endforeach
        </div>

        {{-- Results --}}
        @if($selectedMood && count($results) > 0)
            <section>
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                    <span class="text-2xl">{{ $selectedMood['emoji'] }}</span>
                    {{ $selectedMood['label'] }} Picks
                </h2>
                @php $type = $selectedMood['type']; @endphp
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    @foreach(array_slice($results, 0, 18) as $item)
                        <a href="{{ route($type === 'movie' ? 'movies.detail' : 'tv.detail', ['tmdbId' => $item['id']]) }}"
                           class="group" wire:navigate>
                            <div class="aspect-[2/3] overflow-hidden rounded-lg bg-zinc-800">
                                @if(!empty($item['poster_path']))
                                    <img src="{{ app(Tmdb::class)->imageUrl($item['poster_path']) }}" alt="{{ $item['title'] ?? $item['name'] ?? '' }}"
                                         class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                                @endif
                            </div>
                            <p class="mt-2 text-sm font-medium text-zinc-300 group-hover:text-amber-400">{{ Str::limit($item['title'] ?? $item['name'] ?? '', 25) }}</p>
                            @if(!empty($item['vote_average']))
                                <p class="flex items-center gap-1 text-xs text-zinc-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    {{ number_format($item['vote_average'], 1) }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @elseif($mood && !$selectedMood)
            <p class="text-center text-sm text-zinc-500">Mood not found. Try another one!</p>
        @endif
    </div>

    <div class="pb-16"></div>
</div>
