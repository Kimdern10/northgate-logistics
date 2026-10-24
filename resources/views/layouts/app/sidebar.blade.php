<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('home') }}" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('My account')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Overview') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cube" :href="route('account.shipments')" :current="request()->routeIs('account.shipments*')" wire:navigate>
                        {{ __('My shipments') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('account.addresses')" :current="request()->routeIs('account.addresses')" wire:navigate>
                        {{ __('Address book') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="truck" :href="route('ship')">
                        {{ __('Ship a parcel') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            @if (auth()->user()->is_admin)
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Operations')" class="grid">
                        <flux:sidebar.item icon="chart-bar" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cube" :href="route('admin.shipments.index')" :current="request()->routeIs('admin.shipments.*')" wire:navigate :badge="\App\Models\Shipment::query()->where('status', 'booked')->count() ?: null">
                            {{ __('Shipments & pickups') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="inbox" :href="route('admin.enquiries')" :current="request()->routeIs('admin.enquiries')" wire:navigate :badge="\App\Models\Enquiry::query()->where('status', 'new')->count() ?: null">
                            {{ __('Quote requests') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calculator" :href="route('admin.rates')" :current="request()->routeIs('admin.rates')" wire:navigate>
                            {{ __('Rates') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                    <flux:sidebar.group :heading="__('Website')" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('admin.services')" :current="request()->routeIs('admin.services')" wire:navigate>
                            {{ __('Services') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="map-pin" :href="route('admin.branches')" :current="request()->routeIs('admin.branches')" wire:navigate>
                            {{ __('Branches & hubs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('admin.testimonials')" :current="request()->routeIs('admin.testimonials')" wire:navigate>
                            {{ __('Testimonials') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="question-mark-circle" :href="route('admin.faqs')" :current="request()->routeIs('admin.faqs')" wire:navigate>
                            {{ __('FAQs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
                            {{ __('Site settings') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="globe-alt" :href="route('home')">
                    {{ __('View website') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
