{{-- Reviews section partial --}}
{{-- Required: $reviews, $userReview, $averageUserRating, $showReviewForm, $reviewRating, $reviewTitle, $reviewBody, $reviewSpoilers --}}
@php
    $tmdbReviews = $tmdbReviews ?? [];
    $tmdbRating = $tmdbRating ?? null;
    $tmdbVoteCount = $tmdbVoteCount ?? 0;
    $imdbId = $imdbId ?? null;
    $imdbRating = $imdbRating ?? null;
    $imdbVotes = $imdbVotes ?? null;
    $metascore = $metascore ?? null;
    $rtTomatometer = $rtTomatometer ?? null;
    $rtAudience = $rtAudience ?? null;
    $rtConsensus = $rtConsensus ?? null;
    $communityCount = $reviews->count();
    $tmdbReviewCount = count($tmdbReviews);
    $totalReviews = $communityCount + $tmdbReviewCount;
@endphp

<section class="mt-12">
    <h2 class="mb-6 flex items-center gap-2 text-xl font-bold">
        <span class="h-5 w-1 rounded-full bg-amber-500"></span>
        Ratings & Reviews
    </h2>

    {{-- Ratings Overview --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {{-- TMDB Rating --}}
        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-5">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-teal-500/15">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-teal-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <span class="text-sm font-semibold text-zinc-300">TMDB</span>
            </div>
            @if($tmdbRating)
                <p class="text-3xl font-bold text-white">{{ round($tmdbRating, 1) }}<span class="text-base font-normal text-zinc-500">/10</span></p>
                <p class="mt-1 text-xs text-zinc-500">{{ number_format($tmdbVoteCount) }} votes</p>
            @else
                <p class="text-sm text-zinc-500">Not rated yet</p>
            @endif
        </div>

        {{-- IMDb Rating --}}
        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-5">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-yellow-500/15">
                    <span class="text-xs font-black text-yellow-400">IMDb</span>
                </div>
                <span class="text-sm font-semibold text-zinc-300">IMDb</span>
            </div>
            @if($imdbRating)
                <p class="text-3xl font-bold text-white">{{ number_format($imdbRating, 1) }}<span class="text-base font-normal text-zinc-500">/10</span></p>
                <p class="mt-1 text-xs text-zinc-500">
                    @if($imdbVotes)
                        {{ number_format($imdbVotes) }} votes
                    @else
                        From IMDb
                    @endif
                    @if($imdbId)
                        · <a href="https://www.imdb.com/title/{{ $imdbId }}/" target="_blank" rel="noopener noreferrer" class="text-yellow-400/80 hover:text-yellow-300">Open</a>
                    @endif
                </p>
            @elseif($imdbId)
                <a href="https://www.imdb.com/title/{{ $imdbId }}/" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-yellow-400 transition hover:text-yellow-300">
                    View on IMDb
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                </a>
                <p class="mt-1 text-xs text-zinc-500">Score unavailable right now</p>
            @else
                <p class="text-sm text-zinc-500">Not available</p>
            @endif
        </div>

        {{-- Metascore --}}
        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-5">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/15">
                    <span class="text-[10px] font-black uppercase tracking-wide text-emerald-400">Meta</span>
                </div>
                <span class="text-sm font-semibold text-zinc-300">Metascore</span>
            </div>
            @if($metascore !== null)
                @php
                    $metaTone = $metascore >= 61 ? 'bg-emerald-500 text-white' : ($metascore >= 40 ? 'bg-yellow-500 text-zinc-950' : 'bg-red-500 text-white');
                @endphp
                <div class="flex items-end gap-3">
                    <span class="inline-flex size-12 items-center justify-center rounded-lg text-xl font-black {{ $metaTone }}">{{ $metascore }}</span>
                    <p class="pb-1 text-xs text-zinc-500">Critics aggregate</p>
                </div>
            @else
                <p class="text-sm text-zinc-500">Not available</p>
            @endif
        </div>

        {{-- Rotten Tomatoes --}}
        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-5">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-red-500/15">
                    <span class="text-[10px] font-black uppercase tracking-wide text-red-400">RT</span>
                </div>
                <span class="text-sm font-semibold text-zinc-300">Rotten Tomatoes</span>
            </div>
            @if($rtTomatometer !== null || $rtAudience !== null)
                <div class="flex flex-wrap gap-4">
                    @if($rtTomatometer !== null)
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $rtTomatometer }}<span class="text-sm font-normal text-zinc-500">%</span></p>
                            <p class="text-[11px] text-zinc-500">Tomatometer</p>
                        </div>
                    @endif
                    @if($rtAudience !== null)
                        <div>
                            <p class="text-2xl font-bold text-white">{{ $rtAudience }}<span class="text-sm font-normal text-zinc-500">%</span></p>
                            <p class="text-[11px] text-zinc-500">Audience</p>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-zinc-500">Not available</p>
            @endif
        </div>

        {{-- Community Rating --}}
        <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-5">
            <div class="mb-3 flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-lg bg-amber-500/15">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                </div>
                <span class="text-sm font-semibold text-zinc-300">Community</span>
            </div>
            @if($averageUserRating)
                <p class="text-3xl font-bold text-white">{{ $averageUserRating }}<span class="text-base font-normal text-zinc-500">/10</span></p>
                <p class="mt-1 text-xs text-zinc-500">{{ $communityCount }} {{ Str::plural('review', $communityCount) }}</p>
            @else
                <p class="text-sm text-zinc-500">No ratings yet</p>
            @endif
        </div>
    </div>

    @if($rtConsensus)
        <p class="mb-8 rounded-2xl border border-white/[0.06] bg-white/[0.03] px-5 py-4 text-sm leading-relaxed text-zinc-400">
            <span class="font-semibold text-red-400">Critics Consensus</span>
            <span class="mx-2 text-zinc-600">·</span>
            {{ $rtConsensus }}
        </p>
    @endif

    {{-- Tabbed Reviews --}}
    <div x-data="{ activeTab: 'all' }">
        {{-- Tab buttons --}}
        <div class="mb-6 flex items-center gap-2 border-b border-white/[0.06] pb-3">
            <button @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition">
                All <span class="ml-1 opacity-70">({{ $totalReviews }})</span>
            </button>
            <button @click="activeTab = 'community'"
                    :class="activeTab === 'community' ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition">
                Community <span class="ml-1 opacity-70">({{ $communityCount }})</span>
            </button>
            <button @click="activeTab = 'tmdb'"
                    :class="activeTab === 'tmdb' ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/20' : 'border border-white/[0.06] bg-white/[0.03] text-zinc-400 hover:border-white/[0.12] hover:text-white'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition">
                TMDB <span class="ml-1 opacity-70">({{ $tmdbReviewCount }})</span>
            </button>

            <div class="ml-auto">
                @auth
                    @if(!$userReview)
                        <button wire:click="$toggle('showReviewForm')" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-500">
                            Write Review
                        </button>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-xl bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-700" wire:navigate>
                        Sign in to review
                    </a>
                @endauth
            </div>
        </div>

        {{-- Review form --}}
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

        {{-- Community reviews --}}
        <div x-show="activeTab === 'all' || activeTab === 'community'" x-cloak>
            @if($communityCount > 0)
                <div x-show="activeTab === 'all'" class="mb-4 flex items-center gap-2">
                    <div class="flex size-6 items-center justify-center rounded-md bg-amber-500/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-400">Community Reviews</h3>
                </div>
                <div class="space-y-4">
                    @foreach($reviews as $review)
                        <div class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4">
                            <div class="mb-2 flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="flex size-8 items-center justify-center rounded-full bg-amber-600/20 text-xs font-bold text-amber-400">
                                        {{ Str::upper(Str::substr($review->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-zinc-200">{{ $review->user->name }}</p>
                                            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-400">Community</span>
                                        </div>
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
                <div x-show="activeTab === 'community'" class="rounded-xl border border-white/[0.06] bg-white/[0.03] py-10 text-center">
                    <p class="text-sm text-zinc-500">No community reviews yet. Be the first to share your thoughts!</p>
                </div>
            @endif
        </div>

        {{-- TMDB reviews --}}
        <div x-show="activeTab === 'all' || activeTab === 'tmdb'" x-cloak>
            @if($tmdbReviewCount > 0)
                <div x-show="activeTab === 'all'" class="mb-4 mt-8 flex items-center gap-2">
                    <div class="flex size-6 items-center justify-center rounded-md bg-teal-500/15">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-teal-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-zinc-400">TMDB Reviews</h3>
                </div>
                <div class="space-y-4">
                    @foreach($tmdbReviews as $tmdbReview)
                        @php
                            $authorName = $tmdbReview['author_details']['name'] ?? $tmdbReview['author'] ?? 'Anonymous';
                            $authorRating = $tmdbReview['author_details']['rating'] ?? null;
                            $avatarPath = $tmdbReview['author_details']['avatar_path'] ?? null;
                            $createdAt = !empty($tmdbReview['created_at']) ? \Carbon\Carbon::parse($tmdbReview['created_at'])->diffForHumans() : '';
                            $content = $tmdbReview['content'] ?? '';
                        @endphp
                        <div class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4">
                            <div class="mb-2 flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    @if($avatarPath && !Str::startsWith($avatarPath, '/'))
                                        <img src="{{ $avatarPath }}" alt="{{ $authorName }}" class="size-8 rounded-full object-cover">
                                    @elseif($avatarPath)
                                        <img src="https://image.tmdb.org/t/p/w45{{ $avatarPath }}" alt="{{ $authorName }}" class="size-8 rounded-full object-cover">
                                    @else
                                        <div class="flex size-8 items-center justify-center rounded-full bg-teal-600/20 text-xs font-bold text-teal-400">
                                            {{ Str::upper(Str::substr($authorName, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-zinc-200">{{ $authorName }}</p>
                                            <span class="rounded bg-teal-500/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-teal-400">TMDB</span>
                                        </div>
                                        @if($createdAt)
                                            <p class="text-xs text-zinc-500">{{ $createdAt }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($authorRating)
                                    <div class="flex items-center gap-1 rounded-md bg-teal-600/10 px-2 py-1 text-sm font-bold text-teal-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        {{ $authorRating }}/10
                                    </div>
                                @endif
                            </div>
                            <div x-data="{ expanded: false }">
                                <div :class="expanded ? '' : 'line-clamp-4'" class="prose-sm prose-invert max-w-none text-sm leading-relaxed text-zinc-400">{!! Str::markdown(Str::limit($content, 2000)) !!}</div>
                                @if(Str::length($content) > 300)
                                    <button @click="expanded = !expanded" class="mt-2 text-xs font-medium text-teal-400 transition hover:text-teal-300" x-text="expanded ? 'Show less' : 'Read more'"></button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div x-show="activeTab === 'tmdb'" class="rounded-xl border border-white/[0.06] bg-white/[0.03] py-10 text-center">
                    <p class="text-sm text-zinc-500">No TMDB reviews available for this title.</p>
                </div>
            @endif
        </div>

        {{-- Empty state when no reviews at all --}}
        @if($totalReviews === 0)
            <div class="rounded-xl border border-white/[0.06] bg-white/[0.03] py-10 text-center">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-white/[0.04]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                </div>
                <p class="text-sm text-zinc-500">No reviews yet. Be the first to share your thoughts!</p>
            </div>
        @endif
    </div>
</section>
