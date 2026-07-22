<?php

use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.guest')]
#[Title('Preferences')]
class extends Component
{
    public string $defaultSource = '';

    public string $preferredType = 'all';

    public string $contentLanguage = 'en';

    public bool $autoplayTrailers = true;

    public bool $showAdultContent = false;

    public string $dateOfBirth = '';

    public function mount(): void
    {
        $user = auth()->user();
        $prefs = $user->preferences ?? [];
        $this->defaultSource = $prefs['default_source'] ?? '';
        $this->preferredType = $prefs['preferred_type'] ?? 'all';
        $this->contentLanguage = $prefs['content_language'] ?? 'en';
        $this->autoplayTrailers = $prefs['autoplay_trailers'] ?? true;
        $this->showAdultContent = $prefs['show_adult_content'] ?? false;
        $this->dateOfBirth = $user->date_of_birth?->format('Y-m-d') ?? '';
    }

    public function savePreferences(): void
    {
        $user = auth()->user();

        if ($this->showAdultContent) {
            if (empty($this->dateOfBirth)) {
                $this->addError('dateOfBirth', 'Date of birth is required to enable adult content.');
                $this->showAdultContent = false;

                return;
            }

            $dob = \Illuminate\Support\Carbon::parse($this->dateOfBirth);
            if ($dob->age < 18) {
                $this->addError('dateOfBirth', 'You must be 18 or older to enable adult content.');
                $this->showAdultContent = false;

                return;
            }
        }

        if (! empty($this->dateOfBirth)) {
            $user->update(['date_of_birth' => $this->dateOfBirth]);
        }

        $user->update([
            'preferences' => [
                'default_source' => $this->defaultSource,
                'preferred_type' => $this->preferredType,
                'content_language' => $this->contentLanguage,
                'autoplay_trailers' => $this->autoplayTrailers,
                'show_adult_content' => $this->showAdultContent,
            ],
        ]);

        Flux::toast(variant: 'success', text: __('Preferences saved.'));
    }

    public function clearWatchHistory(): void
    {
        auth()->user()->watchHistory()->delete();
        Flux::toast(variant: 'success', text: __('Watch history cleared.'));
    }

    public function clearWatchlist(): void
    {
        auth()->user()->watchlist()->delete();
        Flux::toast(variant: 'success', text: __('Watchlist cleared.'));
    }

    public function clearFavorites(): void
    {
        auth()->user()->favorites()->delete();
        Flux::toast(variant: 'success', text: __('Favorites cleared.'));
    }

    public function with(): array
    {
        $sources = collect(config('sources.providers', []))
            ->filter(fn (array $p): bool => ($p['driver'] ?? '') === 'embed')
            ->values()
            ->toArray();

        $user = auth()->user();

        return [
            'sources' => $sources,
            'user' => $user,
            'isAdult' => $user->isAdult(),
            'favoritesCount' => $user->favorites()->count(),
            'watchHistoryCount' => $user->watchHistory()->count(),
            'watchlistCount' => $user->watchlist()->count(),
        ];
    }
}; ?>

<section class="w-full">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Preferences')" :subheading="__('Customize your streaming experience')">
        <form wire:submit="savePreferences" class="mt-6 space-y-8">
            {{-- Default streaming source --}}
            <div>
                <flux:select wire:model="defaultSource" :label="__('Default Streaming Source')" :description="__('Choose your preferred server when watching content')">
                    <flux:select.option value="">{{ __('Auto (first available)') }}</flux:select.option>
                    @foreach($sources as $source)
                        <flux:select.option value="{{ $source['name'] }}">{{ $source['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Preferred content type --}}
            <div>
                <flux:radio.group wire:model="preferredType" :label="__('Preferred Content Type')" :description="__('Influences recommendations on your dashboard')" variant="segmented">
                    <flux:radio value="all" label="All" />
                    <flux:radio value="movie" label="Movies" />
                    <flux:radio value="tv" label="TV Shows" />
                </flux:radio.group>
            </div>

            {{-- Content language --}}
            <div>
                <flux:select wire:model="contentLanguage" :label="__('Content Language')" :description="__('Preferred language for content metadata from TMDB')">
                    <flux:select.option value="en">English</flux:select.option>
                    <flux:select.option value="es">Espa&ntilde;ol</flux:select.option>
                    <flux:select.option value="fr">Fran&ccedil;ais</flux:select.option>
                    <flux:select.option value="de">Deutsch</flux:select.option>
                    <flux:select.option value="pt">Portugu&ecirc;s</flux:select.option>
                    <flux:select.option value="ja">Japanese</flux:select.option>
                    <flux:select.option value="ko">Korean</flux:select.option>
                    <flux:select.option value="zh">Chinese</flux:select.option>
                    <flux:select.option value="ar">Arabic</flux:select.option>
                    <flux:select.option value="hi">Hindi</flux:select.option>
                </flux:select>
            </div>

            {{-- Autoplay trailers --}}
            <div>
                <flux:switch wire:model="autoplayTrailers" :label="__('Autoplay Trailers')" :description="__('Automatically play trailers on detail pages')" />
            </div>

            {{-- Adult content section --}}
            <div class="rounded-lg border border-zinc-700 bg-zinc-900/50 p-5">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    {{ __('Adult Content (18+)') }}
                </h3>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Age verification required. You must provide your date of birth and be at least 18 years old.') }}</p>

                <div class="mt-4 space-y-4">
                    <flux:input
                        wire:model="dateOfBirth"
                        type="date"
                        :label="__('Date of Birth')"
                        max="{{ now()->subYears(13)->format('Y-m-d') }}"
                    />
                    @error('dateOfBirth')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    @if($isAdult)
                        <flux:switch wire:model="showAdultContent" :label="__('Show Adult Content')" :description="__('Include adult-rated titles in browse and search results')" />
                    @elseif(!empty($dateOfBirth))
                        <div class="flex items-center gap-2 rounded-lg border border-red-800/50 bg-red-950/30 px-3 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            <p class="text-xs text-red-400">{{ __('You must be 18 or older to access adult content.') }}</p>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">{{ __('Enter your date of birth above to verify your age.') }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Save Preferences') }}
                </flux:button>
            </div>
        </form>

        {{-- Data management --}}
        <section class="mt-12 border-t border-zinc-800 pt-8">
            <h3 class="text-lg font-semibold text-white">{{ __('Data Management') }}</h3>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Manage your saved content and activity data') }}</p>

            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/50 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Watch History') }}</p>
                        <p class="text-xs text-zinc-500">{{ $watchHistoryCount }} {{ Str::plural('item', $watchHistoryCount) }}</p>
                    </div>
                    @if($watchHistoryCount > 0)
                        <button
                            wire:click="clearWatchHistory"
                            wire:confirm="{{ __('Are you sure you want to clear your watch history? This cannot be undone.') }}"
                            class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400"
                        >
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>

                <div class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/50 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Watchlist') }}</p>
                        <p class="text-xs text-zinc-500">{{ $watchlistCount }} {{ Str::plural('item', $watchlistCount) }}</p>
                    </div>
                    @if($watchlistCount > 0)
                        <button
                            wire:click="clearWatchlist"
                            wire:confirm="{{ __('Are you sure you want to clear your watchlist? This cannot be undone.') }}"
                            class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400"
                        >
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>

                <div class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900/50 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Favorites') }}</p>
                        <p class="text-xs text-zinc-500">{{ $favoritesCount }} {{ Str::plural('item', $favoritesCount) }}</p>
                    </div>
                    @if($favoritesCount > 0)
                        <button
                            wire:click="clearFavorites"
                            wire:confirm="{{ __('Are you sure you want to clear all favorites? This cannot be undone.') }}"
                            class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400"
                        >
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>
            </div>
        </section>

        {{-- Account info --}}
        <section class="mt-8 border-t border-zinc-800 pt-8">
            <h3 class="text-lg font-semibold text-white">{{ __('Account Information') }}</h3>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Email') }}</span>
                    <span class="text-zinc-300">{{ $user->email }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Member since') }}</span>
                    <span class="text-zinc-300">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Email verified') }}</span>
                    <span class="{{ $user->hasVerifiedEmail() ? 'text-green-400' : 'text-amber-400' }}">
                        {{ $user->hasVerifiedEmail() ? __('Verified') : __('Not verified') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Adult content') }}</span>
                    @if($user->canViewAdultContent())
                        <span class="text-green-400">{{ __('Enabled') }}</span>
                    @elseif($isAdult)
                        <span class="text-zinc-400">{{ __('Disabled') }}</span>
                    @else
                        <span class="text-zinc-500">{{ __('Age not verified') }}</span>
                    @endif
                </div>
            </div>
        </section>
    </x-pages::settings.layout>
    </div>
</section>
