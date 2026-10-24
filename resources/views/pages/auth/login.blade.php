<x-layouts::auth :title="__('Log in')" side="login">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in')" :description="__('Track shipments, book pickups and manage your account.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
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

            <!-- Password -->
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

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <flux:separator text="{{ __('New to '.\App\Models\Setting::get('business_name').'?') }}" />

        <flux:button :href="route('register')" class="w-full" icon="user-plus">{{ __('Create a free account') }}</flux:button>

        <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Just want to track a parcel?') }}
            <flux:link :href="route('track')">{{ __('Track without logging in') }}</flux:link>
        </p>
    </div>
</x-layouts::auth>
