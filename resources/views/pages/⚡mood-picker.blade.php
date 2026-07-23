<?php

use App\Services\AiRecommender;
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

    public string $customMood = '';

    /**
     * @return array<string, array{label: string, emoji: string, query: string}>
     */
    private function moods(): array
    {
        return [
            'feel-good' => ['label' => 'Feel Good', 'emoji' => '😊', 'query' => 'feel good uplifting movies'],
            'thrilling' => ['label' => 'Thrilling', 'emoji' => '😱', 'query' => 'thrilling suspenseful movies'],
            'romantic' => ['label' => 'Romantic', 'emoji' => '💕', 'query' => 'romantic love story movies'],
            'mind-bending' => ['label' => 'Mind-Bending', 'emoji' => '🧠', 'query' => 'mind bending plot twist movies'],
            'epic-adventure' => ['label' => 'Epic Adventure', 'emoji' => '⚔️', 'query' => 'epic adventure fantasy movies'],
            'laugh-out-loud' => ['label' => 'Laugh Out Loud', 'emoji' => '🤣', 'query' => 'hilarious comedy movies'],
            'dark-gritty' => ['label' => 'Dark & Gritty', 'emoji' => '🌑', 'query' => 'dark gritty crime movies'],
            'animated' => ['label' => 'Animated', 'emoji' => '🎨', 'query' => 'best animated movies'],
            'documentary' => ['label' => 'Learn Something', 'emoji' => '📚', 'query' => 'best documentary films'],
            'action-packed' => ['label' => 'Action Packed', 'emoji' => '💥', 'query' => 'action packed explosive movies'],
            'sad-emotional' => ['label' => 'Sad & Emotional', 'emoji' => '😢', 'query' => 'sad emotional movies that make you cry'],
            'nostalgic' => ['label' => 'Nostalgic', 'emoji' => '✨', 'query' => 'nostalgic classic movies'],
        ];
    }

    public function selectMood(string $mood): void
    {
        $this->mood = $mood;
        $this->customMood = '';
    }

    public function searchCustomMood(): void
    {
        if (strlen($this->customMood) >= 3) {
            $this->mood = 'custom';
        }
    }

    public function with(AiRecommender $ai): array
    {
        $moods = $this->moods();
        $results = [];
        $searchQuery = '';

        if ($this->mood === 'custom' && strlen($this->customMood) >= 3) {
            $searchQuery = $this->customMood;
            $data = $ai->search($this->customMood);
            $results = $data['movies'] ?? [];
        } elseif ($this->mood && isset($moods[$this->mood])) {
            $searchQuery = $moods[$this->mood]['query'];
            $data = $ai->search($moods[$this->mood]['query']);
            $results = $data['movies'] ?? [];
        }

        return [
            'moods' => $moods,
            'results' => $results,
            'searchQuery' => $searchQuery,
            'selectedMood' => $this->mood && $this->mood !== 'custom' ? ($moods[$this->mood] ?? null) : null,
            'isCustom' => $this->mood === 'custom',
        ];
    }
};
?>

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <h1 class="mb-2 text-3xl font-bold">What's Your Mood?</h1>
            <p class="text-zinc-400">Pick a vibe or describe what you're looking for — AI will find the perfect movies</p>
        </div>

        {{-- Custom AI Search --}}
        <div class="mx-auto mb-8 max-w-2xl">
            <form wire:submit="searchCustomMood" class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                    </svg>
                </div>
                <input
                    wire:model="customMood"
                    type="text"
                    placeholder="Describe what you want to watch... e.g. 'rainy day cozy movies' or '90s action classics'"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-900 py-3.5 pl-12 pr-24 text-white placeholder-zinc-500 outline-none transition focus:border-amber-600 focus:ring-1 focus:ring-amber-600"
                >
                <button type="submit"
                        class="absolute inset-y-1.5 right-1.5 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-500">
                    Ask AI
                </button>
            </form>
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

        {{-- Loading --}}
        <div wire:loading class="py-8 text-center">
            <svg class="mx-auto size-6 animate-spin text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-zinc-400">AI is finding movies for you...</p>
        </div>

        {{-- Results --}}
        <div wire:loading.remove>
            @if(count($results) > 0)
                <section>
                    <h2 class="mb-1 flex items-center gap-2 text-xl font-bold">
                        @if($selectedMood)
                            <span class="text-2xl">{{ $selectedMood['emoji'] }}</span>
                            {{ $selectedMood['label'] }} Picks
                        @elseif($isCustom)
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                            </svg>
                            AI Recommendations
                        @endif
                    </h2>
                    <p class="mb-4 text-sm text-zinc-500">{{ count($results) }} movies found</p>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach($results as $item)
                            <a href="{{ route('movies.detail', ['tmdbId' => $item['id']]) }}"
                               class="group" wire:navigate>
                                <div class="aspect-[2/3] overflow-hidden rounded-lg bg-zinc-800">
                                    @if(!empty($item['poster_path']))
                                        <img src="https://image.tmdb.org/t/p/w500{{ $item['poster_path'] }}" alt="{{ $item['title'] ?? '' }}"
                                             class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy">
                                    @endif
                                </div>
                                <p class="mt-2 text-sm font-medium text-zinc-300 group-hover:text-amber-400">{{ Str::limit($item['title'] ?? '', 25) }}</p>
                                <div class="flex items-center gap-2 text-xs text-zinc-500">
                                    @if(!empty($item['vote_average']))
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                            {{ number_format($item['vote_average'], 1) }}
                                        </span>
                                    @endif
                                    @if(!empty($item['release_date']))
                                        <span>{{ Str::substr($item['release_date'], 0, 4) }}</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @elseif($mood)
                <p class="text-center text-sm text-zinc-500">No recommendations found. Try a different mood or description!</p>
            @endif
        </div>
    </div>

    <div class="pb-16"></div>
</div>
