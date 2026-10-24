@props([
    'title' => null,
    'side' => 'login',
])

@php
    $site = \App\Models\Setting::values();
    $panel = match ($side) {
        'register' => [
            'heading' => 'Your shipping, all in one place.',
            'text' => 'A free account works like My UPS or My FedEx: everything you send and receive, in one dashboard.',
            'points' => [
                ['cube', 'See every parcel you send or receive'],
                ['book-open', 'Save addresses and book pickups in seconds'],
                ['arrow-path', 'Ship again in one click'],
                ['bell', 'Email alerts at every tracking update'],
            ],
        ],
        default => [
            'heading' => 'Welcome back.',
            'text' => 'Log in to track your shipments, book pickups and download your waybills.',
            'points' => [
                ['map-pin', 'Live tracking from pickup to doorstep'],
                ['globe-alt', 'Air and ocean freight to 40+ countries'],
                ['building-storefront', 'Warehousing and e-commerce fulfillment'],
            ],
        ],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            @keyframes ng-dash { to { stroke-dashoffset: -400; } }
            @keyframes ng-pulse { 0% { transform: scale(.5); opacity: 1; } 100% { transform: scale(2.6); opacity: 0; } }
            .ng-route { stroke-dasharray: 6 8; animation: ng-dash 24s linear infinite; }
            .ng-pulse { transform-box: fill-box; transform-origin: center; animation: ng-pulse 2.6s ease-out infinite; }
            @media (prefers-reduced-motion: reduce) { .ng-route, .ng-pulse { animation: none; } }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        <div class="grid min-h-svh lg:grid-cols-2">
            {{-- Brand panel --}}
            <aside class="relative hidden overflow-hidden bg-[#0c2340] text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
                <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                    <defs>
                        <pattern id="auth-dots" width="22" height="22" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.3" fill="#fff" fill-opacity=".1"/></pattern>
                        <radialGradient id="auth-glow"><stop offset="0" stop-color="#ff6b1a" stop-opacity=".9"/><stop offset="1" stop-color="#ff6b1a" stop-opacity="0"/></radialGradient>
                        <radialGradient id="auth-sky" cx="85%" cy="0%" r="80%"><stop offset="0" stop-color="#1f4f8f"/><stop offset="1" stop-color="#0c2340" stop-opacity="0"/></radialGradient>
                    </defs>
                    <rect width="600" height="800" fill="url(#auth-sky)"/>
                    <rect width="600" height="800" fill="url(#auth-dots)"/>
                    {{-- Routes stay in the right-hand and bottom edges so they never cross the text. --}}
                    <path id="auth-r1" class="ng-route" d="M300 790 C 400 700, 500 690, 570 590" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="1.6"/>
                    <path id="auth-r2" class="ng-route" d="M480 120 C 540 160, 590 220, 570 330" fill="none" stroke="#fff" stroke-opacity=".2" stroke-width="1.6"/>
                    @foreach ([[300, 790], [570, 590], [480, 120], [570, 330]] as [$x, $y])
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="16" fill="url(#auth-glow)" class="ng-pulse" style="animation-delay: {{ $loop->index * .6 }}s"/>
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#ff6b1a"/>
                    @endforeach
                    <circle r="5" fill="#ffb38a"><animateMotion dur="7s" repeatCount="indefinite"><mpath href="#auth-r1"/></animateMotion></circle>
                    <circle r="5" fill="#ffb38a"><animateMotion dur="9s" repeatCount="indefinite"><mpath href="#auth-r2"/></animateMotion></circle>
                </svg>

                <a href="{{ route('home') }}" class="relative flex items-center gap-3 text-lg font-bold">
                    <x-site-logo class="size-10" style="--navy: #ff6b1a; --orange: #0c2340" />
                    {{ $site['business_name'] }}
                </a>

                <div class="relative max-w-md">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight">{{ $panel['heading'] }}</h2>
                    <p class="mt-4 text-lg text-[#c4d2e6]">{{ $panel['text'] }}</p>
                    <ul class="mt-8 space-y-4">
                        @foreach ($panel['points'] as [$icon, $point])
                            <li class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-xl bg-white/10 text-[#ffb38a]"><flux:icon :name="$icon" variant="mini" /></span>
                                <span class="font-medium">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-sm text-[#9cadc3]">{{ $site['phone'] }} · {{ $site['email'] }}</p>
            </aside>

            {{-- Form --}}
            <main class="flex flex-col">
                <div class="flex items-center justify-between gap-4 p-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-zinc-900 lg:invisible dark:text-white">
                        <x-site-logo class="size-8" />
                        {{ $site['business_name'] }}
                    </a>
                    <nav class="flex items-center gap-1 text-sm font-medium">
                        <a href="{{ route('home') }}" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white">
                            <flux:icon.home variant="mini" /> Home
                        </a>
                        <a href="{{ route('track') }}" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/10 dark:hover:text-white">
                            <flux:icon.magnifying-glass variant="mini" /> Track
                        </a>
                    </nav>
                </div>

                <div class="flex flex-1 items-center justify-center px-6 pb-12">
                    <div class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-8 shadow-xl shadow-zinc-900/5 sm:p-10 dark:border-white/10 dark:bg-zinc-900">
                        <div class="flex flex-col gap-6">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
