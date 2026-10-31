<x-layouts::auth :title="__('Verify your email')" side="register">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Check your inbox')" :description="__('We emailed a 6-digit code to :email. Enter it below to finish creating your account.', ['email' => auth()->user()->email])" />

        <livewire:pages::auth.verify-email-code />

        <form method="POST" action="{{ route('logout') }}" class="border-t border-zinc-200 pt-5 text-center dark:border-white/10">
            @csrf
            <flux:button variant="ghost" type="submit" class="cursor-pointer text-sm" data-test="logout-button">
                {{ __('Wrong email? Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
