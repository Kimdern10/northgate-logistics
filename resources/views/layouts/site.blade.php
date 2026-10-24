@props([
    'title' => null,
    'description' => null,
    'current' => null,
    'jsonLd' => null,
])

@php
    $site = \App\Models\Setting::values();
    $pageTitle = filled($title) ? $title.' · '.$site['business_name'] : $site['business_name'].' · International shipping, warehousing & fulfilment';
    $metaDescription = $description ?: $site['tagline'];
    $links = [
        'services' => ['Services', route('services.index')],
        'track' => ['Track', route('track')],
        'quote' => ['Get a quote', route('quote')],
        'locations' => ['Locations', route('locations')],
        'about' => ['About', route('about')],
        'contact' => ['Contact', route('contact')],
    ];
    $organisation = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $site['business_name'],
        'url' => route('home'),
        'email' => $site['email'],
        'telephone' => $site['phone'],
        'address' => $site['address'],
        'logo' => asset('favicon.svg'),
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $site['business_name'] }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="theme-color" content="#0c2340">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <script>document.documentElement.classList.add('js'); try { var t = localStorage.getItem('ng-theme'); if (t === 'light' || t === 'dark') { document.documentElement.dataset.theme = t; } } catch (e) {}</script>
        @fonts('plus-jakarta-sans')
        @vite(['resources/css/site.css', 'resources/js/site.js'])
        @livewireStyles
        <script type="application/ld+json">{!! json_encode($jsonLd ?? $organisation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </head>
    <body>
        <a class="skip" href="#main">Skip to content</a>

        <header class="site-header">
            <div class="container header-row">
                <a class="brand" href="{{ route('home') }}" wire:navigate.hover aria-label="{{ $site['business_name'] }} home">
                    <x-site-logo />
                    <span>{{ $site['business_name'] }}</span>
                </a>
                <nav class="site-nav" aria-label="Main">
                    @foreach ($links as $key => [$label, $href])
                        <a href="{{ $href }}" wire:navigate.hover @if ($current === $key) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="header-tools">
                    <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch light or dark mode">
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    @auth
                        <a class="account-link" href="{{ route('dashboard') }}" title="My account"><x-icon name="user" /><span class="hide-sm">My account</span></a>
                    @else
                        <a class="account-link" href="{{ route('login') }}"><x-icon name="user" /><span class="hide-sm">Log in</span></a>
                    @endauth
                    <a class="btn btn--primary btn--sm hide-sm" href="{{ route('ship') }}" wire:navigate.hover>Book a pickup</a>
                    <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu"><span></span><span></span></button>
                </div>
            </div>
        </header>

        <nav class="mobile-menu" id="mobile-menu" aria-label="Main (mobile)">
            <a href="{{ route('home') }}" wire:navigate.hover>Home</a>
            @foreach ($links as [$label, $href])
                <a href="{{ $href }}" wire:navigate.hover>{{ $label }}</a>
            @endforeach
            @auth
                <a href="{{ route('account.shipments') }}">My shipments</a>
            @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}">Create an account</a>
            @endauth
            <a class="btn btn--primary" href="{{ route('ship') }}" wire:navigate.hover>Book a pickup</a>
        </nav>

        <main id="main">
            {{ $slot }}
        </main>

        <footer class="site-footer">
            <div class="container footer-grid">
                <div class="footer-brand">
                    <a class="brand" href="{{ route('home') }}" wire:navigate.hover><x-site-logo /><span>{{ $site['business_name'] }}</span></a>
                    <p>{{ $site['tagline'] }}</p>
                </div>
                <div>
                    <h4>Ship</h4>
                    <ul>
                        <li><a href="{{ route('track') }}" wire:navigate.hover>Track a shipment</a></li>
                        <li><a href="{{ route('quote') }}" wire:navigate.hover>Shipping quote</a></li>
                        <li><a href="{{ route('ship') }}" wire:navigate.hover>Book a pickup</a></li>
                        <li><a href="{{ route('locations') }}" wire:navigate.hover>Branches & hubs</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Company</h4>
                    <ul>
                        <li><a href="{{ route('services.index') }}" wire:navigate.hover>Services</a></li>
                        <li><a href="{{ route('about') }}" wire:navigate.hover>About us</a></li>
                        <li><a href="{{ route('contact') }}" wire:navigate.hover>Contact</a></li>
                        <li><a href="{{ route('privacy') }}" wire:navigate.hover>Privacy policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Talk to us</h4>
                    <ul>
                        <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['phone']) }}">{{ $site['phone'] }}</a></li>
                        <li><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></li>
                        <li>{{ $site['address'] }}</li>
                    </ul>
                </div>
            </div>
            <div class="container footer-bottom">
                <span>© {{ now()->year }} {{ $site['business_name'] }}. All rights reserved.</span>
                <span>Prices in US dollars.</span>
            </div>
        </footer>
        @livewireScripts
    </body>
</html>
