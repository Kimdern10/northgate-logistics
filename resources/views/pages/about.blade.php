@php($site = \App\Models\Setting::values())

<x-layouts::site current="about" title="About us" description="{{ $site['business_name'] }} moves cargo between Africa and the world with air and sea freight, warehousing and fulfilment.">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>About us</p>
            <h1 data-reveal>Logistics people who pick up the phone.</h1>
            <p class="lead" data-reveal>For {{ $site['stat_years'] }} years we've helped families, traders and online brands move goods between Africa and the rest of the world.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container detail-grid">
            <div class="prose" data-reveal>
                <p>{{ $site['business_name'] }} started with one van and a small office near the airport. Today we run our own hubs and warehouses, work with trusted airline and shipping partners, and handle customs paperwork so our customers don't have to.</p>
                <p>Every shipment gets a tracking number, a printed waybill and a real person who knows where it is. We believe shipping should be simple to price, easy to follow and honest when something goes wrong.</p>
                <h2>What we stand for</h2>
                <ul class="tick-list tick-list--grid">
                    <li><x-icon name="check" /> Clear prices before you book</li>
                    <li><x-icon name="check" /> Updates at every step</li>
                    <li><x-icon name="check" /> Careful handling and insurance options</li>
                    <li><x-icon name="check" /> Help from real people, seven days a week</li>
                </ul>
            </div>
            <aside class="card side-card about-stats" data-reveal>
                <div><strong data-count="{{ (int) $site['stat_deliveries'] }}">{{ number_format((int) $site['stat_deliveries']) }}</strong><span>parcels delivered</span></div>
                <div><strong data-count="{{ (int) $site['stat_countries'] }}">{{ $site['stat_countries'] }}</strong><span>countries served</span></div>
                <div><strong data-count="{{ (int) $site['stat_warehouse_sqm'] }}">{{ number_format((int) $site['stat_warehouse_sqm']) }}</strong><span>m² of warehouse space</span></div>
            </aside>
        </div>
    </section>

    <x-cta-band />
</x-layouts::site>
