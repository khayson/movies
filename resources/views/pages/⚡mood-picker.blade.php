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

    public function with(AiRecommender $ai, Tmdb $tmdb): array
    {
        $moods = $this->moods();
        $results = [];
        $searchQuery = '';
        $usedFallback = false;
        $unavailable = false;

        if ($this->mood === 'custom' && strlen($this->customMood) >= 3) {
            $searchQuery = $this->customMood;
            $data = $ai->search($this->customMood);
            $results = $data['movies'] ?? [];
            $unavailable = (bool) ($data['unavailable'] ?? false);
        } elseif ($this->mood && isset($moods[$this->mood])) {
            $searchQuery = $moods[$this->mood]['query'];
            $data = $ai->search($moods[$this->mood]['query']);
            $results = $data['movies'] ?? [];
            $unavailable = (bool) ($data['unavailable'] ?? false);
        }

        if ($searchQuery !== '' && $results === []) {
            $fallback = collect($tmdb->search($searchQuery)['results'] ?? [])
                ->filter(fn (array $item): bool => in_array($item['media_type'] ?? '', ['movie', 'tv'], true))
                ->take(18)
                ->values()
                ->all();

            if ($fallback !== []) {
                $results = $fallback;
                $usedFallback = true;
            }
        }

        return [
            'moods' => $moods,
            'results' => $results,
            'searchQuery' => $searchQuery,
            'selectedMood' => $this->mood && $this->mood !== 'custom' ? ($moods[$this->mood] ?? null) : null,
            'isCustom' => $this->mood === 'custom',
            'usedFallback' => $usedFallback,
            'unavailable' => $unavailable && ! $usedFallback,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-fuchsia-950/15 via-zinc-950/80 to-zinc-950"></div>
        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-12 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="mb-3 flex items-center justify-center gap-3">
                    <span class="h-6 w-1 rounded-full bg-fuchsia-500"></span>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-fuchsia-400/80">AI Powered</p>
                    <span class="h-6 w-1 rounded-full bg-fuchsia-500"></span>
                </div>
                <h1 class="text-4xl font-bold tracking-tight md:text-5xl">What's Your Mood?</h1>
                <p class="mt-2 text-sm text-zinc-400">Pick a vibe or describe what you're looking for — we'll suggest matching titles</p>
            </div>

            {{-- Custom AI Search --}}
            <div class="mx-auto mt-8 max-w-2xl">
                <form wire:submit="searchCustomMood" class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-fuchsia-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                        </svg>
                    </div>
                    <input
                        wire:model="customMood"
                        type="text"
                        placeholder="Describe what you want to watch... e.g. 'rainy day cozy movies'"
                        class="w-full rounded-2xl border border-white/[0.08] bg-white/[0.03] py-4 pl-12 pr-24 text-white placeholder-zinc-500 outline-none transition focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/30"
                    >
                    <button type="submit"
                            class="absolute inset-y-1.5 right-1.5 rounded-xl bg-fuchsia-600 px-5 text-sm font-semibold text-white shadow-lg shadow-fuchsia-600/20 transition hover:bg-fuchsia-500">
                        Ask AI
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Mood Grid --}}
        <div class="mx-auto mb-10 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
            @foreach($moods as $key => $m)
                <button wire:click="selectMood('{{ $key }}')"
                        class="rounded-2xl border p-5 text-center transition-all duration-200 {{ $mood === $key ? 'border-fuchsia-500/40 bg-fuchsia-500/10 scale-[1.02]' : 'border-white/[0.06] bg-white/[0.02] hover:border-white/[0.12] hover:bg-white/[0.04]' }}">
                    <span class="text-3xl">{{ $m['emoji'] }}</span>
                    <p class="mt-2 text-sm font-medium {{ $mood === $key ? 'text-fuchsia-400' : 'text-zinc-300' }}">{{ $m['label'] }}</p>
                </button>
            @endforeach
        </div>

        {{-- Loading --}}
        <div wire:loading class="py-8 text-center">
            <svg class="mx-auto size-6 animate-spin text-fuchsia-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-zinc-400">Finding movies for you...</p>
        </div>

        {{-- Results --}}
        <div wire:loading.remove>
            @if(count($results) > 0)
                <section>
                    @if($usedFallback)
                        <p class="mb-4 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">AI recommendations unavailable — showing TMDB matches instead.</p>
                    @endif
                    <h2 class="mb-1 flex items-center gap-2 text-xl font-bold">
                        @if($selectedMood)
                            <span class="text-2xl">{{ $selectedMood['emoji'] }}</span>
                            {{ $selectedMood['label'] }} Picks
                        @elseif($isCustom)
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-fuchsia-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                            </svg>
                            {{ $usedFallback ? 'Suggested Titles' : 'AI Recommendations' }}
                        @endif
                    </h2>
                    <p class="mb-5 text-sm text-zinc-500">{{ count($results) }} titles found</p>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach($results as $item)
                            @include('partials.media-card', ['item' => $item, 'type' => $item['media_type'] ?? 'movie'])
                        @endforeach
                    </div>
                </section>
            @elseif($mood && $unavailable)
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                    <p class="text-zinc-500">Recommendations are unavailable right now. Try again later or use Search.</p>
                </div>
            @elseif($mood)
                <div class="rounded-2xl border border-white/[0.06] bg-white/[0.02] py-20 text-center">
                    <p class="text-zinc-500">No recommendations found. Try a different mood or description!</p>
                </div>
            @endif
        </div>
    </div>
</div>
