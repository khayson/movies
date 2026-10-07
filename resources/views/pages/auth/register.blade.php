<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form
            method="POST"
            action="{{ route('register.store') }}"
            x-data="authForm({
                defaultRedirect: '{{ session('url.intended', route('dashboard')) }}',
                successHeading: '{{ __('Welcome to StreamVault!') }}',
                successText: '{{ __('Your account has been created successfully.') }}',
                errorHeading: '{{ __('Registration failed') }}',
                defaultErrorMessage: '{{ __('Please verify your information and try again.') }}'
            })"
            @submit.prevent="submit"
            class="flex flex-col gap-6"
        >
            @csrf

            <!-- Form Error Banner -->
            <div
                x-show="errorMessage"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="p-3 text-sm rounded-lg bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 flex items-center gap-2"
            >
                <flux:icon icon="exclamation-circle" variant="mini" class="size-4 shrink-0 text-red-500" />
                <span x-text="errorMessage"></span>
            </div>

            <!-- Name -->
            <div>
                <flux:input
                    name="name"
                    :label="__('Name')"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Full name')"
                />
                <div x-show="errors.name" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.name ? errors.name[0] : ''"></div>
            </div>

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <div x-show="errors.email" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.email ? errors.email[0] : ''"></div>
            </div>

            <!-- Password -->
            <div>
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <div x-show="errors.password" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.password ? errors.password[0] : ''"></div>
            </div>

            <!-- Confirm Password -->
            <div>
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <div x-show="errors.password_confirmation" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.password_confirmation ? errors.password_confirmation[0] : ''"></div>
            </div>

            <div class="flex items-center justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full relative overflow-hidden transition-all duration-200"
                    x-bind:disabled="loading"
                    loading="false"
                    data-test="register-user-button"
                >
                    <span x-show="!loading" class="inline-flex items-center justify-center gap-2">
                        {{ __('Create account') }}
                    </span>
                    <span x-show="loading" x-cloak class="inline-flex items-center justify-center gap-2">
                        <svg class="animate-spin size-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Creating account...') }}</span>
                    </span>
                </flux:button>
            </div>
        </form>

        @if ($errors->any())
            <div x-data x-init="$nextTick(() => {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: { heading: '{{ __('Registration failed') }}', text: '{{ $errors->first() }}' },
                        dataset: { variant: 'danger' },
                        duration: 6000
                    }
                }));
            })"></div>
        @endif

        @if (session('status'))
            <div x-data x-init="$nextTick(() => {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: { text: '{{ session('status') }}' },
                        dataset: { variant: 'info' },
                        duration: 5000
                    }
                }));
            })"></div>
        @endif

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
