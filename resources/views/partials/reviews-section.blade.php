{{-- Reviews section partial --}}
{{-- Required: $reviews, $userReview, $averageUserRating, $showReviewForm, $reviewRating, $reviewTitle, $reviewBody, $reviewSpoilers --}}
<section class="mt-12">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold">Reviews</h2>
            @if($averageUserRating)
                <span class="flex items-center gap-1 rounded-md bg-amber-600/10 px-2 py-1 text-sm font-medium text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    {{ $averageUserRating }}/10
                </span>
                <span class="text-sm text-zinc-500">({{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }})</span>
            @endif
        </div>
        @auth
            @if(!$userReview)
                <button wire:click="$toggle('showReviewForm')" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">
                    Write Review
                </button>
            @endif
        @else
            <a href="{{ route('login') }}" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700" wire:navigate>
                Sign in to review
            </a>
        @endauth
    </div>

    @if($showReviewForm)
        <div class="mb-6 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
            <form wire:submit="submitReview">
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-zinc-400">Rating</label>
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 10; $i++)
                            <button type="button" wire:click="$set('reviewRating', {{ $i }})"
                                    class="rounded p-1 transition {{ $reviewRating >= $i ? 'text-amber-400' : 'text-zinc-600 hover:text-zinc-400' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </button>
                        @endfor
                        <span class="ml-2 text-sm font-medium text-zinc-300">{{ $reviewRating }}/10</span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-zinc-400">Title</label>
                    <input type="text" wire:model="reviewTitle" placeholder="Summarize your thoughts..."
                           class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-600" />
                    @error('reviewTitle') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-zinc-400">Review (optional)</label>
                    <textarea wire:model="reviewBody" rows="4" placeholder="Share your detailed thoughts..."
                              class="w-full rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-600"></textarea>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" wire:model="reviewSpoilers" id="spoilers" class="rounded border-zinc-600 bg-zinc-800 text-amber-600 focus:ring-amber-600">
                    <label for="spoilers" class="text-sm text-zinc-400">Contains spoilers</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">Submit Review</button>
                    <button type="button" wire:click="$set('showReviewForm', false)" class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    @if($reviews->count() > 0)
        <div class="space-y-4">
            @foreach($reviews as $review)
                <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
                    <div class="mb-2 flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex size-8 items-center justify-center rounded-full bg-amber-600/20 text-xs font-bold text-amber-400">
                                {{ Str::upper(Str::substr($review->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-200">{{ $review->user->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $review->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="flex items-center gap-1 rounded-md bg-amber-600/10 px-2 py-1 text-sm font-bold text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $review->rating }}/10
                            </div>
                            @if($review->user_id === auth()->id())
                                <button wire:click="deleteReview" wire:confirm="Delete your review?" class="rounded p-1 text-zinc-500 transition hover:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    <h4 class="mb-1 text-sm font-semibold text-zinc-200">{{ $review->title }}</h4>
                    @if($review->body)
                        @if($review->contains_spoilers)
                            <details class="group">
                                <summary class="cursor-pointer text-xs font-medium text-amber-500">Contains spoilers — click to reveal</summary>
                                <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $review->body }}</p>
                            </details>
                        @else
                            <p class="text-sm leading-relaxed text-zinc-400">{{ $review->body }}</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-zinc-500">No reviews yet. Be the first to share your thoughts!</p>
    @endif
</section>
