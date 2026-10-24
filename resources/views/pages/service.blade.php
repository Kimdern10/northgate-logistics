@php
    $enquiryType = match (true) {
        str_contains($service->slug, 'warehous') => 'warehousing',
        str_contains($service->slug, 'fulfil') => 'fulfillment',
        default => 'quote',
    };
    $serviceLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service->name,
        'description' => $service->summary,
        'provider' => ['@type' => 'Organization', 'name' => \App\Models\Setting::get('business_name')],
        'areaServed' => 'Worldwide',
    ];
@endphp

<x-layouts::site current="services" :title="$service->name" :description="$service->summary" :json-ld="$serviceLd">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal><a href="{{ route('services.index') }}" wire:navigate.hover>Services</a></p>
            <h1 data-reveal>{{ $service->name }}</h1>
            <p class="lead" data-reveal>{{ $service->summary }}</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container detail-grid">
            <div class="prose" data-reveal>
                @foreach (preg_split('/\R{2,}/', trim($service->description)) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach

                @if ($service->features)
                    <h2>What's included</h2>
                    <ul class="tick-list tick-list--grid">
                        @foreach ($service->features as $feature)
                            <li><x-icon name="check" /> {{ $feature }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <aside class="card side-card" data-reveal>
                <span class="service-icon service-icon--lg"><x-icon :name="$service->icon" /></span>
                <h2>Talk to a specialist</h2>
                <p>Tell us what you ship and we'll send a plan and price within one working day.</p>
                <a class="btn btn--primary btn--block" href="{{ route('contact', ['type' => $enquiryType]) }}" wire:navigate.hover>Request a quote</a>
                <a class="btn btn--ghost btn--block" href="{{ route('quote') }}" wire:navigate.hover>Instant parcel prices</a>
            </aside>
        </div>
    </section>

    @if ($others->isNotEmpty())
        <section class="section section--alt">
            <div class="container">
                <div class="section-head" data-reveal><h2>More services</h2></div>
                <div class="service-grid">
                    @foreach ($others as $other)
                        <a class="service-card" href="{{ route('services.show', $other) }}" wire:navigate.hover data-reveal style="--i: {{ $loop->index }}">
                            <span class="service-icon"><x-icon :name="$other->icon" /></span>
                            <h3>{{ $other->name }}</h3>
                            <p>{{ $other->summary }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::site>
