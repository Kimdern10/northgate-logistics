<x-layouts::site current="services" title="Services" description="International air and sea freight, customs clearance, warehousing and e-commerce fulfillment.">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>Services</p>
            <h1 data-reveal>Built for importers, exporters and online brands.</h1>
            <p class="lead" data-reveal>Pick one service or let us run your whole supply chain, from the factory to your customer's door.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container service-rows">
            @foreach ($services as $service)
                <article class="service-row" data-reveal>
                    <span class="service-icon service-icon--lg"><x-icon :name="$service->icon" /></span>
                    <div>
                        <h2><a href="{{ route('services.show', $service) }}" wire:navigate.hover>{{ $service->name }}</a></h2>
                        <p>{{ $service->summary }}</p>
                        @if ($service->features)
                            <ul class="tick-list">
                                @foreach (array_slice($service->features, 0, 4) as $feature)
                                    <li><x-icon name="check" /> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <a class="btn btn--ghost" href="{{ route('services.show', $service) }}" wire:navigate.hover>Details <x-icon name="arrow-right" /></a>
                </article>
            @endforeach
        </div>
    </section>

    <x-cta-band />
</x-layouts::site>
