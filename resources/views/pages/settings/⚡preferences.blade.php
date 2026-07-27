<?php

use App\Support\UserPreferences;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Preferences')]
class extends Component
{
    public string $defaultSource = '';

    public string $preferredType = 'all';

    public string $contentLanguage = 'en';

    public string $streamingCountry = 'us';

    public bool $emailNotifications = true;

    public bool $autoplayTrailers = true;

    public bool $showAdultContent = false;

    public string $streamQuality = '';

    public bool $cinesrcAutoskip = false;

    public bool $cinesrcAutonext = true;

    public bool $preferHlsDirect = false;

    public bool $autoplayOnWatch = true;

    public bool $startMuted = false;

    public bool $resumePrompt = true;

    public int $cinesrcSeek = 10;

    public bool $showContinueWatching = true;

    public bool $blurSpoilers = false;

    public bool $hideFromLeaderboard = false;

    public bool $rememberLastServer = true;

    public bool $autoFallbackOnError = true;

    public bool $showTrailerInServers = true;

    public bool $useProviderScores = true;

    /** @var array<int, string> */
    public array $excludedProviders = [];

    public bool $disableContentPersonalization = false;

    public string $cinesrcBack = 'close';

    public string $dateOfBirth = '';

    public function mount(): void
    {
        $user = auth()->user();
        $prefs = $user->preferences ?? [];
        $defaults = UserPreferences::defaults();

        $this->defaultSource = (string) UserPreferences::get($prefs, 'default_source', $defaults['default_source']);
        $this->preferredType = (string) UserPreferences::get($prefs, 'preferred_type', $defaults['preferred_type']);
        $this->contentLanguage = (string) UserPreferences::get($prefs, 'content_language', $defaults['content_language']);
        $this->streamingCountry = (string) UserPreferences::get($prefs, 'streaming_country', $defaults['streaming_country']);
        $this->emailNotifications = UserPreferences::bool($prefs, 'email_notifications', true);
        $this->autoplayTrailers = UserPreferences::bool($prefs, 'autoplay_trailers', true);
        $this->showAdultContent = UserPreferences::bool($prefs, 'show_adult_content', false);
        $this->streamQuality = (string) UserPreferences::get($prefs, 'stream_quality', '');
        $this->cinesrcAutoskip = UserPreferences::bool($prefs, 'cinesrc_autoskip', false);
        $this->cinesrcAutonext = UserPreferences::bool($prefs, 'cinesrc_autonext', true);
        $this->preferHlsDirect = UserPreferences::bool($prefs, 'prefer_hls_direct', false);
        $this->autoplayOnWatch = UserPreferences::bool($prefs, 'autoplay_on_watch', true);
        $this->startMuted = UserPreferences::bool($prefs, 'start_muted', false);
        $this->resumePrompt = UserPreferences::bool($prefs, 'resume_prompt', true);
        $this->cinesrcSeek = (int) UserPreferences::get($prefs, 'cinesrc_seek', 10);
        $this->showContinueWatching = UserPreferences::bool($prefs, 'show_continue_watching', true);
        $this->blurSpoilers = UserPreferences::bool($prefs, 'blur_spoilers', false);
        $this->hideFromLeaderboard = UserPreferences::bool($prefs, 'hide_from_leaderboard', false);
        $this->rememberLastServer = UserPreferences::bool($prefs, 'remember_last_server', true);
        $this->autoFallbackOnError = UserPreferences::bool($prefs, 'auto_fallback_on_error', true);
        $this->showTrailerInServers = UserPreferences::bool($prefs, 'show_trailer_in_servers', true);
        $this->useProviderScores = UserPreferences::bool($prefs, 'use_provider_scores', true);
        $excluded = UserPreferences::get($prefs, 'excluded_providers', []);
        $this->excludedProviders = is_array($excluded) ? array_values(array_map('strval', $excluded)) : [];
        $this->disableContentPersonalization = UserPreferences::bool($prefs, 'disable_content_personalization', false);
        $this->cinesrcBack = (string) UserPreferences::get($prefs, 'cinesrc_back', 'close');
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

        $seek = in_array($this->cinesrcSeek, [5, 10, 15, 30], true) ? $this->cinesrcSeek : 10;
        $back = in_array($this->cinesrcBack, ['close', ''], true) ? $this->cinesrcBack : 'close';

        $user->update([
            'preferences' => UserPreferences::merge($user->preferences, [
                'default_source' => $this->defaultSource,
                'preferred_type' => $this->preferredType,
                'content_language' => $this->contentLanguage,
                'streaming_country' => $this->streamingCountry,
                'email_notifications' => $this->emailNotifications,
                'autoplay_trailers' => $this->autoplayTrailers,
                'show_adult_content' => $this->showAdultContent,
                'stream_quality' => $this->streamQuality,
                'cinesrc_autoskip' => $this->cinesrcAutoskip,
                'cinesrc_autonext' => $this->cinesrcAutonext,
                'prefer_hls_direct' => $this->preferHlsDirect,
                'autoplay_on_watch' => $this->autoplayOnWatch,
                'start_muted' => $this->startMuted,
                'resume_prompt' => $this->resumePrompt,
                'cinesrc_seek' => $seek,
                'show_continue_watching' => $this->showContinueWatching,
                'blur_spoilers' => $this->blurSpoilers,
                'hide_from_leaderboard' => $this->hideFromLeaderboard,
                'remember_last_server' => $this->rememberLastServer,
                'auto_fallback_on_error' => $this->autoFallbackOnError,
                'show_trailer_in_servers' => $this->showTrailerInServers,
                'use_provider_scores' => $this->useProviderScores,
                'excluded_providers' => array_values(array_unique($this->excludedProviders)),
                'disable_content_personalization' => $this->disableContentPersonalization,
                'cinesrc_back' => $back,
            ]),
        ]);

        Flux::toast(variant: 'success', text: __('Preferences saved.'));
    }

    public function resetPreferences(): void
    {
        $user = auth()->user();
        $user->update([
            'preferences' => UserPreferences::merge($user->preferences, UserPreferences::defaults()),
        ]);

        $this->mount();

        Flux::toast(variant: 'success', text: __('Preferences reset to defaults.'));
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
            ->filter(fn (array $p): bool => in_array($p['driver'] ?? '', ['embed', 'cinesrc'], true))
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
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Preferences')" :subheading="__('Customize streaming, playback, and privacy — including Advanced overrides')">
        <form wire:submit="savePreferences" class="space-y-6">
            {{-- Streaming --}}
            <div id="settings-streaming" class="scroll-mt-28 space-y-5 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ __('Streaming') }}</h3>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Default servers and quality when you hit Play.') }}</p>
                </div>

                <flux:select wire:model="defaultSource" :label="__('Default Streaming Source')" :description="__('Choose your preferred server when watching content')">
                    <flux:select.option value="">{{ __('Auto (first available)') }}</flux:select.option>
                    @foreach($sources as $source)
                        <flux:select.option value="{{ $source['name'] }}">{{ $source['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="streamQuality" :label="__('Preferred Stream Quality')" :description="__('Passed to CineSrc when available')">
                    <flux:select.option value="">{{ __('Auto') }}</flux:select.option>
                    <flux:select.option value="1080">1080p</flux:select.option>
                    <flux:select.option value="720">720p</flux:select.option>
                    <flux:select.option value="480">480p</flux:select.option>
                </flux:select>

                <flux:select wire:model="streamingCountry" :label="__('Streaming Country')" :description="__('Show legal watch/buy options for this country')">
                    <flux:select.option value="us">United States</flux:select.option>
                    <flux:select.option value="gb">United Kingdom</flux:select.option>
                    <flux:select.option value="ca">Canada</flux:select.option>
                    <flux:select.option value="au">Australia</flux:select.option>
                    <flux:select.option value="de">Germany</flux:select.option>
                    <flux:select.option value="fr">France</flux:select.option>
                    <flux:select.option value="es">Spain</flux:select.option>
                    <flux:select.option value="it">Italy</flux:select.option>
                    <flux:select.option value="br">Brazil</flux:select.option>
                    <flux:select.option value="mx">Mexico</flux:select.option>
                    <flux:select.option value="in">India</flux:select.option>
                    <flux:select.option value="jp">Japan</flux:select.option>
                    <flux:select.option value="kr">South Korea</flux:select.option>
                    <flux:select.option value="ng">Nigeria</flux:select.option>
                    <flux:select.option value="gh">Ghana</flux:select.option>
                    <flux:select.option value="za">South Africa</flux:select.option>
                    <flux:select.option value="se">Sweden</flux:select.option>
                    <flux:select.option value="nl">Netherlands</flux:select.option>
                </flux:select>

                <flux:switch wire:model="preferHlsDirect" :label="__('Prefer CineSrc Direct (HLS)')" :description="__('When a direct stream is available, prefer it over the embed player')" />
            </div>

            {{-- Playback --}}
            <div id="settings-playback" class="scroll-mt-28 space-y-5 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ __('Playback') }}</h3>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('How players start and advance through content.') }}</p>
                </div>

                <flux:switch wire:model="autoplayTrailers" :label="__('Autoplay Trailers')" :description="__('Automatically play trailers on detail pages')" />
                <flux:switch wire:model="autoplayOnWatch" :label="__('Autoplay on watch page')" :description="__('Start playback when the watch page loads')" />
                <flux:switch wire:model="startMuted" :label="__('Start muted')" :description="__('Open the CineSrc player muted')" />
                <flux:switch wire:model="resumePrompt" :label="__('Continue / Restart prompt')" :description="__('Ask before resuming from your last position')" />
                <flux:switch wire:model="cinesrcAutonext" :label="__('Auto-play next episode')" :description="__('Advance to the next episode when the current one ends')" />
                <flux:switch wire:model="cinesrcAutoskip" :label="__('Auto-skip intros')" :description="__('Skip intro/recap segments when detected')" />

                <flux:select wire:model="cinesrcSeek" :label="__('Seek button length')" :description="__('Seconds jumped by the player seek controls')">
                    <flux:select.option value="5">5s</flux:select.option>
                    <flux:select.option value="10">10s</flux:select.option>
                    <flux:select.option value="15">15s</flux:select.option>
                    <flux:select.option value="30">30s</flux:select.option>
                </flux:select>
            </div>

            {{-- Discovery --}}
            <div id="settings-discovery" class="scroll-mt-28 space-y-5 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ __('Discovery') }}</h3>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('What shows up on your dashboard and episode lists.') }}</p>
                </div>

                <flux:radio.group wire:model="preferredType" :label="__('Preferred Content Type')" :description="__('Influences recommendations on your dashboard')" variant="segmented">
                    <flux:radio value="all" label="All" />
                    <flux:radio value="movie" label="Movies" />
                    <flux:radio value="tv" label="TV Shows" />
                </flux:radio.group>

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

                <flux:switch wire:model="showContinueWatching" :label="__('Show Continue Watching')" :description="__('Display your in-progress titles on the dashboard')" />
                <flux:switch wire:model="blurSpoilers" :label="__('Blur episode spoilers')" :description="__('Blur episode overviews until you hover')" />
            </div>

            {{-- Notifications --}}
            <div id="settings-notifications" class="scroll-mt-28 space-y-5 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
                <div>
                    <h3 class="text-sm font-semibold text-white">{{ __('Notifications') }}</h3>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Email alerts for activity and watchlist updates.') }}</p>
                </div>
                <flux:switch wire:model="emailNotifications" :label="__('Email Notifications')" :description="__('Receive notifications about new releases and watchlist updates')" />
            </div>

            {{-- Privacy & maturity --}}
            <div id="settings-privacy" class="scroll-mt-28 space-y-5 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        {{ __('Privacy & maturity') }}
                    </h3>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Age verification and public activity visibility.') }}</p>
                </div>

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
                        <p class="text-xs text-red-400">{{ __('You must be 18 or older to access adult content.') }}</p>
                    </div>
                @else
                    <p class="text-xs text-zinc-500">{{ __('Enter your date of birth above to verify your age.') }}</p>
                @endif

                <flux:switch wire:model="hideFromLeaderboard" :label="__('Hide from leaderboard')" :description="__('Exclude your watch activity from the public leaderboard')" />
            </div>

            {{-- Advanced --}}
            <div
                id="settings-advanced"
                class="scroll-mt-28 rounded-2xl border border-amber-500/20 bg-amber-500/[0.03]"
                x-data="{ open: window.location.hash === '#settings-advanced' }"
                x-on:settings:open-advanced.window="open = true"
            >
                <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left sm:px-6">
                    <div>
                        <h3 class="text-sm font-semibold text-amber-100">{{ __('Advanced') }}</h3>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('These override StreamVault defaults. Leave them alone unless you want full manual control.') }}</p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 shrink-0 text-zinc-500 transition" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="open" x-cloak x-transition class="space-y-5 border-t border-amber-500/10 px-5 py-5 sm:px-6">
                    <flux:switch wire:model="rememberLastServer" :label="__('Remember last used server')" :description="__('Boost the server you used last time for this title')" />
                    <flux:switch wire:model="autoFallbackOnError" :label="__('Auto-switch server on error')" :description="__('Automatically try another server when playback fails')" />
                    <flux:switch wire:model="showTrailerInServers" :label="__('Include trailer in server list')" :description="__('Show the YouTube trailer alongside streaming servers')" />
                    <flux:switch wire:model="useProviderScores" :label="__('Use provider reliability scoring')" :description="__('Rank servers by reliability; off uses config order only')" />
                    <flux:switch wire:model="disableContentPersonalization" :label="__('Disable content personalization')" :description="__('Ignore preferred type when building your dashboard feed')" />

                    <flux:select wire:model="cinesrcBack" :label="__('CineSrc back button')" :description="__('What the in-player back control does')">
                        <flux:select.option value="close">{{ __('Close / notify parent') }}</flux:select.option>
                        <flux:select.option value="">{{ __('Hide back button') }}</flux:select.option>
                    </flux:select>

                    <div>
                        <p class="mb-2 text-sm font-medium text-zinc-200">{{ __('Excluded providers') }}</p>
                        <p class="mb-3 text-xs text-zinc-500">{{ __('Checked providers will never appear in the server list.') }}</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($sources as $source)
                                <label class="flex items-center gap-2 rounded-lg border border-white/[0.06] bg-zinc-950/40 px-3 py-2 text-sm text-zinc-300">
                                    <input type="checkbox" wire:model="excludedProviders" value="{{ $source['name'] }}" class="rounded border-zinc-600 bg-zinc-900 text-amber-500 focus:ring-amber-500/40">
                                    {{ $source['name'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button variant="primary" type="submit">
                    {{ __('Save Preferences') }}
                </flux:button>
                <button
                    type="button"
                    wire:click="resetPreferences"
                    wire:confirm="{{ __('Reset all preferences to StreamVault defaults?') }}"
                    class="rounded-lg border border-white/[0.08] px-4 py-2 text-sm font-medium text-zinc-400 transition hover:border-white/[0.15] hover:text-white"
                >
                    {{ __('Reset to defaults') }}
                </button>
            </div>
        </form>

        {{-- Your data --}}
        <section id="settings-your-data" class="mt-10 scroll-mt-28 space-y-4 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
            <div>
                <h3 class="text-sm font-semibold text-white">{{ __('Your data') }}</h3>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Manage saved content and activity data.') }}</p>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-xl border border-white/[0.06] bg-zinc-950/40 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Watch History') }}</p>
                        <p class="text-xs text-zinc-500">{{ $watchHistoryCount }} {{ Str::plural('item', $watchHistoryCount) }}</p>
                    </div>
                    @if($watchHistoryCount > 0)
                        <button wire:click="clearWatchHistory" wire:confirm="{{ __('Are you sure you want to clear your watch history? This cannot be undone.') }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400">{{ __('Clear') }}</button>
                    @endif
                </div>
                <div class="flex items-center justify-between rounded-xl border border-white/[0.06] bg-zinc-950/40 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Watchlist') }}</p>
                        <p class="text-xs text-zinc-500">{{ $watchlistCount }} {{ Str::plural('item', $watchlistCount) }}</p>
                    </div>
                    @if($watchlistCount > 0)
                        <button wire:click="clearWatchlist" wire:confirm="{{ __('Are you sure you want to clear your watchlist? This cannot be undone.') }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400">{{ __('Clear') }}</button>
                    @endif
                </div>
                <div class="flex items-center justify-between rounded-xl border border-white/[0.06] bg-zinc-950/40 p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-200">{{ __('Favorites') }}</p>
                        <p class="text-xs text-zinc-500">{{ $favoritesCount }} {{ Str::plural('item', $favoritesCount) }}</p>
                    </div>
                    @if($favoritesCount > 0)
                        <button wire:click="clearFavorites" wire:confirm="{{ __('Are you sure you want to clear all favorites? This cannot be undone.') }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:border-red-800 hover:bg-red-950/50 hover:text-red-400">{{ __('Clear') }}</button>
                    @endif
                </div>
            </div>
        </section>
    </x-pages::settings.layout>
    </div>
</section>
