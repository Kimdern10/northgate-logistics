@php
    $site = \App\Models\Setting::values();
    $countries = \App\Support\Countries::options();
    $faqLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
        ])->values()->all(),
    ];
@endphp

<x-layouts::site current="home">
    <section class="hero">
        <div class="hero-bg" aria-hidden="true">
            <svg class="hero-routes" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <radialGradient id="glow" cx="50%" cy="50%" r="50%">
                        <stop offset="0" stop-color="#ff6b1a" stop-opacity=".9"/>
                        <stop offset="1" stop-color="#ff6b1a" stop-opacity="0"/>
                    </radialGradient>
                    <pattern id="dots" width="22" height="22" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1.3" fill="#ffffff" fill-opacity=".12"/>
                    </pattern>
                </defs>
                <rect width="1200" height="600" fill="url(#dots)"/>
                <path id="route-a" class="route" d="M140 420 C 360 140, 640 120, 860 260"/>
                <path id="route-b" class="route" d="M860 260 C 960 330, 1040 360, 1120 300"/>
                <path id="route-c" class="route" d="M300 500 C 520 360, 760 420, 1020 180"/>
                <path id="route-d" class="route" d="M80 220 C 300 260, 480 330, 620 470"/>
                @foreach ([[140, 420], [860, 260], [1120, 300], [300, 500], [1020, 180], [80, 220], [620, 470]] as [$x, $y])
                    <g class="hub" style="--delay: {{ $loop->index * 0.4 }}s">
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="16" fill="url(#glow)" class="hub-pulse"/>
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#ff6b1a"/>
                    </g>
                @endforeach
                @foreach (['route-a' => 9, 'route-b' => 5, 'route-c' => 11, 'route-d' => 8] as $route => $seconds)
                    <circle r="4.5" class="parcel">
                        <animateMotion dur="{{ $seconds }}s" repeatCount="indefinite" rotate="auto"><mpath href="#{{ $route }}"/></animateMotion>
                    </circle>
                @endforeach
            </svg>
        </div>

        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow eyebrow--light" data-reveal>International shipping · Warehousing · Fulfilment</p>
                <h1 data-reveal>{{ $site['hero_title'] }}</h1>
                <p class="lead" data-reveal>{{ $site['hero_text'] }}</p>
                <div class="btn-row" data-reveal>
                    <a class="btn btn--primary btn--lg" href="{{ route('ship') }}" wire:navigate.hover>Book a pickup <x-icon name="arrow-right" /></a>
                    <a class="btn btn--light btn--lg" href="{{ route('services.index') }}" wire:navigate.hover>Our services</a>
                </div>
                <ul class="hero-points" data-reveal>
                    <li><x-icon name="check" /> Door-to-door tracking</li>
                    <li><x-icon name="check" /> Customs handled for you</li>
                    <li><x-icon name="check" /> Pay at pickup</li>
                </ul>
            </div>

            <div class="track-box" data-reveal data-tabs>
                <div class="tabs" role="tablist">
                    <button type="button" role="tab" aria-selected="true" aria-controls="tab-track" id="tab-track-btn" data-tab="tab-track"><x-icon name="search" /> Track</button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="tab-quote" id="tab-quote-btn" data-tab="tab-quote" tabindex="-1"><x-icon name="chart" /> Quick quote</button>
                </div>

                <form id="tab-track" role="tabpanel" aria-labelledby="tab-track-btn" class="tab-panel" action="{{ route('track') }}" method="get">
                    <label class="field">
                        <span>Tracking number(s)</span>
                        <input name="number" placeholder="e.g. NG4827301956" autocomplete="off" required>
                    </label>
                    <button class="btn btn--primary btn--block" type="submit">Track shipment</button>
                    <p class="hint">On your waybill or booking email. Track up to 10 at once, separated by commas.</p>
                </form>

                <form id="tab-quote" role="tabpanel" aria-labelledby="tab-quote-btn" class="tab-panel" action="{{ route('quote') }}" method="get" hidden>
                    <div class="grid-2">
                        <label class="field">
                            <span>From</span>
                            <select name="from">
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected($code === 'NG')>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field">
                            <span>To</span>
                            <select name="to">
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected($code === 'GB')>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="field">
                        <span>Weight (kg)</span>
                        <input name="weight" type="number" step="0.1" min="0.1" value="2" inputmode="decimal">
                    </label>
                    <button class="btn btn--primary btn--block" type="submit">See prices</button>
                </form>
            </div>
        </div>
    </section>

    <div class="ticker" aria-hidden="true">
        <div class="ticker-track">
            @foreach (range(1, 2) as $copy)
                @foreach (['Air express', 'Sea freight', 'Customs clearance', 'Bonded warehousing', 'Pick, pack & ship', 'Returns handling', 'Door-to-door tracking', 'Cargo insurance'] as $item)
                    <span>{{ $item }}</span>
                @endforeach
            @endforeach
        </div>
    </div>

    <section class="section" id="services">
        <div class="container">
            <div class="section-head" data-reveal>
                <p class="eyebrow">What we do</p>
                <h2>Everything your goods need, under one roof.</h2>
                <p>From a single parcel to full containers, and from storage to your customer's door.</p>
            </div>
            <div class="service-grid">
                @foreach ($services as $service)
                    <a class="service-card" href="{{ route('services.show', $service) }}" wire:navigate.hover data-reveal style="--i: {{ $loop->index }}">
                        <span class="service-icon"><x-icon :name="$service->icon" /></span>
                        <h3>{{ $service->name }}</h3>
                        <p>{{ $service->summary }}</p>
                        <span class="link-arrow">Learn more <x-icon name="arrow-right" /></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section--alt">
        <div class="container">
            <div class="section-head" data-reveal>
                <p class="eyebrow">How it works</p>
                <h2>Shipping in four simple steps.</h2>
            </div>
            <ol class="steps">
                @foreach ([
                    ['chart', 'Get a quote', 'Enter the route and weight to see prices for every service in seconds.'],
                    ['calendar', 'Book a pickup', 'Choose a date. You get a tracking number and waybill right away.'],
                    ['truck', 'We collect & ship', 'Our courier picks up from your door and we handle customs.'],
                    ['check', 'Delivered', 'Follow every step online and get an email at each update.'],
                ] as [$icon, $heading, $text])
                    <li class="step" data-reveal style="--i: {{ $loop->index }}">
                        <span class="step-icon"><x-icon :name="$icon" /><b>{{ $loop->iteration }}</b></span>
                        <h3>{{ $heading }}</h3>
                        <p>{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="stats-band">
        <div class="container stats-grid">
            @foreach ([
                [$site['stat_years'], '', 'years moving cargo'],
                [$site['stat_countries'], '', 'countries served'],
                [$site['stat_deliveries'], '+', 'parcels delivered'],
                [$site['stat_warehouse_sqm'], ' m²', 'warehouse space'],
            ] as [$number, $suffix, $label])
                <div class="stat" data-reveal>
                    <strong><span data-count="{{ (int) $number }}">{{ number_format((int) $number) }}</span>{{ $suffix }}</strong>
                    <span>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </section>

    @if ($branches->isNotEmpty())
        <section class="section" id="coverage">
            <div class="container coverage-grid">
                <div data-reveal>
                    <p class="eyebrow">Coverage</p>
                    <h2>Hubs on four continents.</h2>
                    <p>Our own hubs and trusted partners cover {{ $site['stat_countries'] }} countries. Drop off at any branch or let us collect.</p>
                    <ul class="hub-list">
                        @foreach ($branches->take(6) as $branch)
                            <li>
                                <span class="hub-dot @if ($branch->is_hub) hub-dot--hub @endif"></span>
                                <span><strong>{{ $branch->city }}</strong>, {{ $branch->countryName() }}</span>
                                <small>{{ $branch->kind() }}</small>
                            </li>
                        @endforeach
                    </ul>
                    <a class="btn btn--ghost" href="{{ route('locations') }}" wire:navigate.hover>All locations <x-icon name="arrow-right" /></a>
                </div>
                <div class="map-wrap" data-reveal>
                    <div class="map" data-map='@json($branches->map->toMapPin()->values())' role="img" aria-label="Map of our branches and hubs"></div>
                </div>
            </div>
        </section>
    @endif

    @if ($testimonials->isNotEmpty())
        <section class="section section--alt testimonials">
            <div class="container">
                <div class="section-head" data-reveal>
                    <p class="eyebrow">Customers</p>
                    <h2>Trusted by shippers big and small.</h2>
                </div>
            </div>
            <div class="marquee" data-reveal>
                <div class="marquee-track">
                    @foreach (range(1, 2) as $copy)
                        @foreach ($testimonials as $testimonial)
                            <figure class="quote-card-t" @if ($copy === 2) aria-hidden="true" @endif>
                                <div class="stars" aria-label="{{ $testimonial->rating }} out of 5 stars">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <x-icon name="star" :class="$star > $testimonial->rating ? 'star star--off' : 'star'" />
                                    @endfor
                                </div>
                                <blockquote>“{{ $testimonial->quote }}”</blockquote>
                                <figcaption>
                                    <span class="avatar">{{ \Illuminate\Support\Str::initials($testimonial->name) }}</span>
                                    <span><strong>{{ $testimonial->name }}</strong>@if ($testimonial->role)<small>{{ $testimonial->role }}</small>@endif</span>
                                </figcaption>
                            </figure>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="section" id="faq">
            <div class="container faq-grid">
                <div data-reveal>
                    <p class="eyebrow">FAQ</p>
                    <h2>Questions, answered.</h2>
                    <p>Can't find what you need? <a href="{{ route('contact') }}" wire:navigate.hover>Send us a message</a>.</p>
                </div>
                <div class="faq-list">
                    @foreach ($faqs as $faq)
                        <details class="faq" data-reveal @if ($loop->first) open @endif>
                            <summary>{{ $faq->question }}</summary>
                            <p>{{ $faq->answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
        <script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif

    <x-cta-band />
</x-layouts::site>
