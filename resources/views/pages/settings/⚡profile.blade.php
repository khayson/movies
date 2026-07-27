<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Profile settings')]
class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <div id="settings-profile" class="mb-6 scroll-mt-28 flex items-center gap-4 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5">
            <div class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-lg font-bold text-white">
                {{ auth()->user()->initials() }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-base font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-sm text-zinc-500">{{ auth()->user()->email }}</p>
                <div class="mt-2 flex flex-wrap gap-3 text-xs text-zinc-500">
                    <span>{{ __('Member since') }} {{ auth()->user()->created_at->format('M d, Y') }}</span>
                    <span class="{{ auth()->user()->hasVerifiedEmail() ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ auth()->user()->hasVerifiedEmail() ? __('Email verified') : __('Email not verified') }}
                    </span>
                </div>
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="space-y-6 rounded-2xl border border-white/[0.06] bg-white/[0.02] p-5 sm:p-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <div id="settings-delete-account" class="mt-8 scroll-mt-28 rounded-2xl border border-red-500/20 bg-red-500/[0.04] p-5 sm:p-6">
                <livewire:pages::settings.delete-user-form />
            </div>
        @endif
    </x-pages::settings.layout>
    </div>
</section>
