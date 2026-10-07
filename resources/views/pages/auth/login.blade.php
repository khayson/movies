<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            x-data="authForm({
                defaultRedirect: '{{ session('url.intended', route('dashboard')) }}',
                twoFactorRoute: '{{ route('two-factor.login') }}',
                successHeading: '{{ __('Welcome back!') }}',
                successText: '{{ __('You have been successfully logged in.') }}',
                errorHeading: '{{ __('Login failed') }}',
                defaultErrorMessage: '{{ __('These credentials do not match our records.') }}'
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

            <!-- Email Address -->
            <div>
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <div x-show="errors.email" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.email ? errors.email[0] : ''"></div>
            </div>

            <!-- Password -->
            <div>
                <div class="relative">
                    <flux:input
                        name="password"
                        :label="__('Password')"
                        type="password"
                        required
                        autocomplete="current-password"
                        :placeholder="__('Password')"
                        viewable
                    />

                    @if (Route::has('password.request'))
                        <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                            {{ __('Forgot your password?') }}
                        </flux:link>
                    @endif
                </div>
                <div x-show="errors.password" x-cloak class="mt-1 text-xs text-red-500 dark:text-red-400" x-text="errors.password ? errors.password[0] : ''"></div>
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full relative overflow-hidden transition-all duration-200"
                    x-bind:disabled="loading"
                    loading="false"
                    data-test="login-button"
                >
                    <span x-show="!loading" class="inline-flex items-center justify-center gap-2">
                        {{ __('Log in') }}
                    </span>
                    <span x-show="loading" x-cloak class="inline-flex items-center justify-center gap-2">
                        <svg class="animate-spin size-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Signing in...') }}</span>
                    </span>
                </flux:button>
            </div>
        </form>

        @if ($errors->any())
            <div x-data x-init="$nextTick(() => {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: { heading: '{{ __('Login failed') }}', text: '{{ $errors->first() }}' },
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

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
