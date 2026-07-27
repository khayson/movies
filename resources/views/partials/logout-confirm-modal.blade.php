<flux:modal name="confirm-logout" class="max-w-md">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Log out?') }}</flux:heading>
            <flux:subheading>
                {{ __('Are you sure you want to log out of your account?') }}
            </flux:subheading>
        </div>

        <div class="flex justify-end gap-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="danger" type="submit" data-test="confirm-logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</flux:modal>
