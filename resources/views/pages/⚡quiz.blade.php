<?php

use App\Models\QuizScore;
use App\Services\Tmdb;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Movie & TV Trivia — StreamVault')]
class extends Component
{
    public string $quizType = '';

    public function saveScore(int $score, int $total, int $timeSeconds): void
    {
        $user = auth()->user();
        if (! $user || ! $this->quizType) {
            return;
        }

        QuizScore::create([
            'user_id' => $user->id,
            'quiz_type' => $this->quizType,
            'score' => $score,
            'total' => $total,
            'time_seconds' => $timeSeconds,
        ]);
    }

    public function with(Tmdb $tmdb): array
    {
        View::share('ogTitle', 'Movie & TV Trivia — ' . config('app.name'));
        View::share('ogDescription', 'Test your movie and TV knowledge with fun trivia quizzes!');

        $popular = $tmdb->popular('movie')['results'] ?? [];
        $topRated = $tmdb->topRated('movie')['results'] ?? [];
        $popularTv = $tmdb->popular('tv')['results'] ?? [];

        $pool = collect(array_merge($popular, $topRated, $popularTv))
            ->filter(fn (array $item) => ! empty($item['backdrop_path']) && ! empty($item['overview']))
            ->unique('id')
            ->shuffle()
            ->values();

        $questions = [];
        $usedIds = [];

        foreach ($pool->take(40) as $item) {
            if (count($questions) >= 10) {
                break;
            }

            $title = $item['title'] ?? $item['name'] ?? '';
            $year = Str::substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
            $rating = round($item['vote_average'] ?? 0, 1);

            if (! $title || ! $year) {
                continue;
            }

            $wrongAnswers = $pool
                ->filter(fn (array $other) => $other['id'] !== $item['id'] && ! in_array($other['id'], $usedIds))
                ->take(3)
                ->map(fn (array $other) => $other['title'] ?? $other['name'] ?? 'Unknown')
                ->values()
                ->all();

            if (count($wrongAnswers) < 3) {
                continue;
            }

            $usedIds[] = $item['id'];

            $options = collect(array_merge([$title], $wrongAnswers))->shuffle()->values()->all();

            $questions[] = [
                'backdrop' => $tmdb->backdropUrl($item['backdrop_path'], 'w1280'),
                'answer' => $title,
                'options' => $options,
                'year' => $year,
                'rating' => $rating,
                'overview' => Str::limit($item['overview'], 120),
                'type' => isset($item['first_air_date']) ? 'TV Show' : 'Movie',
            ];
        }

        $leaderboard = QuizScore::where('quiz_type', 'guess_title')
            ->select('user_id')
            ->selectRaw('MAX(score) as best_score')
            ->selectRaw('MIN(time_seconds) as best_time')
            ->selectRaw('COUNT(*) as attempts')
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->orderBy('best_time')
            ->limit(10)
            ->with('user:id,name')
            ->get();

        return [
            'questions' => $questions,
            'leaderboard' => $leaderboard,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="border-b border-white/[0.06] bg-gradient-to-b from-purple-950/10 to-transparent">
        <div class="mx-auto max-w-7xl px-4 pb-6 pt-10 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">Movie & TV Trivia</span>
            </h1>
            <p class="mt-2 text-sm text-zinc-500">Test your knowledge — can you name the movie from its backdrop?</p>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if(count($questions) >= 5)
            <div
                x-data="{
                    questions: {{ Js::from($questions) }},
                    current: 0,
                    score: 0,
                    answered: false,
                    selectedAnswer: null,
                    finished: false,
                    startTime: Date.now(),
                    totalTime: 0,
                    streak: 0,
                    bestStreak: 0,

                    select(option) {
                        if (this.answered) return;
                        this.selectedAnswer = option;
                        this.answered = true;
                        if (option === this.questions[this.current].answer) {
                            this.score++;
                            this.streak++;
                            if (this.streak > this.bestStreak) this.bestStreak = this.streak;
                        } else {
                            this.streak = 0;
                        }
                    },

                    next() {
                        if (this.current < this.questions.length - 1) {
                            this.current++;
                            this.answered = false;
                            this.selectedAnswer = null;
                        } else {
                            this.finished = true;
                            this.totalTime = Math.round((Date.now() - this.startTime) / 1000);
                            @auth
                                $wire.set('quizType', 'guess_title');
                                $wire.saveScore(this.score, this.questions.length, this.totalTime);
                            @endauth
                        }
                    },

                    restart() {
                        this.questions = this.questions.sort(() => Math.random() - 0.5);
                        this.current = 0;
                        this.score = 0;
                        this.answered = false;
                        this.selectedAnswer = null;
                        this.finished = false;
                        this.startTime = Date.now();
                        this.streak = 0;
                        this.bestStreak = 0;
                    },

                    get progress() {
                        return ((this.current + 1) / this.questions.length) * 100;
                    },

                    get q() {
                        return this.questions[this.current];
                    }
                }"
            >
                {{-- Quiz in progress --}}
                <template x-if="!finished">
                    <div>
                        {{-- Progress bar --}}
                        <div class="mb-6 flex items-center gap-3">
                            <span class="text-sm font-medium tabular-nums text-zinc-400" x-text="(current + 1) + '/' + questions.length"></span>
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/[0.06]">
                                <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-500" :style="'width:' + progress + '%'"></div>
                            </div>
                            <div class="flex items-center gap-1.5 text-sm font-semibold tabular-nums text-purple-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                                <span x-text="score"></span>
                            </div>
                        </div>

                        {{-- Backdrop image --}}
                        <div class="relative mb-6 overflow-hidden rounded-2xl border border-white/[0.06]">
                            <div class="aspect-video w-full bg-zinc-800">
                                <img :src="q.backdrop" :alt="'Question ' + (current + 1)" class="h-full w-full object-cover">
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 to-transparent"></div>

                            {{-- Hint badges --}}
                            <div class="absolute bottom-4 left-4 flex items-center gap-2">
                                <span class="rounded-md bg-black/60 px-2 py-0.5 text-xs font-semibold text-zinc-300 backdrop-blur-sm" x-text="q.year"></span>
                                <span class="rounded-md bg-purple-500/20 px-2 py-0.5 text-xs font-semibold text-purple-300 backdrop-blur-sm" x-text="q.type"></span>
                                <span class="rounded-md bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-300 backdrop-blur-sm">
                                    <span x-text="q.rating"></span>/10
                                </span>
                            </div>

                            {{-- Streak indicator --}}
                            <div x-show="streak >= 2" x-transition class="absolute right-4 top-4 rounded-md bg-orange-500/20 px-2 py-0.5 text-xs font-bold text-orange-400 backdrop-blur-sm">
                                <span x-text="streak + 'x streak'"></span>
                            </div>
                        </div>

                        {{-- Question --}}
                        <h2 class="mb-4 text-center text-lg font-semibold text-zinc-200">What is this title?</h2>

                        {{-- Answer options --}}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="(option, i) in q.options" :key="i">
                                <button
                                    @click="select(option)"
                                    class="rounded-xl border px-5 py-4 text-left text-sm font-medium transition"
                                    :class="{
                                        'border-white/[0.06] bg-white/[0.02] text-zinc-300 hover:border-purple-500/30 hover:bg-purple-500/5 hover:text-white': !answered,
                                        'border-green-500/40 bg-green-500/10 text-green-400': answered && option === q.answer,
                                        'border-red-500/40 bg-red-500/10 text-red-400': answered && option === selectedAnswer && option !== q.answer,
                                        'border-white/[0.04] bg-white/[0.01] text-zinc-600': answered && option !== q.answer && option !== selectedAnswer,
                                    }"
                                    :disabled="answered"
                                    x-text="option"
                                ></button>
                            </template>
                        </div>

                        {{-- Result + Next --}}
                        <div x-show="answered" x-transition class="mt-6 text-center">
                            <p class="mb-3 text-sm" :class="selectedAnswer === q.answer ? 'text-green-400' : 'text-red-400'" x-text="selectedAnswer === q.answer ? 'Correct!' : 'Wrong! It was ' + q.answer"></p>
                            <p class="mb-4 text-xs text-zinc-500" x-text="q.overview"></p>
                            <button @click="next()" class="rounded-lg bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-500">
                                <span x-text="current < questions.length - 1 ? 'Next Question' : 'See Results'"></span>
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Results screen --}}
                <template x-if="finished">
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-6 flex size-24 items-center justify-center rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 ring-2 ring-purple-500/30">
                            <span class="text-4xl font-black tabular-nums text-purple-400" x-text="score"></span>
                        </div>
                        <h2 class="mb-2 text-2xl font-bold">
                            <span x-text="score + '/' + questions.length"></span>
                        </h2>
                        <p class="mb-1 text-sm text-zinc-400" x-text="'Completed in ' + totalTime + ' seconds'"></p>
                        <p class="mb-1 text-sm text-zinc-400" x-text="'Best streak: ' + bestStreak + 'x'"></p>
                        <p class="mb-8 text-lg font-medium" :class="{
                            'text-green-400': score >= questions.length * 0.8,
                            'text-amber-400': score >= questions.length * 0.5 && score < questions.length * 0.8,
                            'text-red-400': score < questions.length * 0.5,
                        }" x-text="score >= questions.length * 0.8 ? 'Outstanding! True cinephile!' : score >= questions.length * 0.5 ? 'Good job! Keep watching!' : 'Better luck next time!'"></p>

                        @guest
                            <p class="mb-6 text-xs text-zinc-600">
                                <a href="{{ route('register') }}" wire:navigate class="text-purple-400 hover:underline">Sign up</a> to save your scores and appear on the leaderboard!
                            </p>
                        @endguest

                        <button @click="restart()" class="rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-purple-600/20 transition hover:from-purple-500 hover:to-pink-500">
                            Play Again
                        </button>
                    </div>
                </template>
            </div>
        @else
            <div class="py-20 text-center">
                <p class="text-zinc-500">Not enough data to generate a quiz right now. Try again later.</p>
            </div>
        @endif

        {{-- Leaderboard --}}
        @if($leaderboard->isNotEmpty())
            <div class="mt-16">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-bold">
                    <span class="h-5 w-1 rounded-full bg-purple-500"></span>
                    Top Players
                </h2>
                <div class="overflow-hidden rounded-2xl border border-white/[0.06]">
                    @foreach($leaderboard as $i => $entry)
                        <div class="flex items-center gap-4 border-b border-white/[0.04] px-5 py-3 last:border-0 {{ $i < 3 ? 'bg-purple-500/[0.03]' : '' }}">
                            <span class="w-6 text-center text-sm font-bold tabular-nums {{ $i === 0 ? 'text-amber-400' : ($i === 1 ? 'text-zinc-300' : ($i === 2 ? 'text-amber-700' : 'text-zinc-600')) }}">{{ $i + 1 }}</span>
                            <div class="flex size-8 items-center justify-center rounded-lg bg-purple-600/20 text-xs font-bold text-purple-400">
                                {{ Str::upper(Str::substr($entry->user->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-200">{{ $entry->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-zinc-600">{{ $entry->attempts }} {{ Str::plural('attempt', $entry->attempts) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold tabular-nums text-purple-400">{{ $entry->best_score }}/10</p>
                                @if($entry->best_time)
                                    <p class="text-xs tabular-nums text-zinc-600">{{ $entry->best_time }}s</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
