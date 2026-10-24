<x-layouts::site current="locations" title="Branches & hubs" description="Find a branch, hub or warehouse near you. Drop off parcels or book a pickup.">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>Locations</p>
            <h1 data-reveal>Branches, hubs and warehouses.</h1>
            <p class="lead" data-reveal>Drop off at any branch, or book a pickup and we'll come to you.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="map-wrap map-wrap--wide" data-reveal>
                <div class="map" data-map='@json($branches->map->toMapPin()->values())' role="img" aria-label="Map of our branches and hubs"></div>
            </div>

            <div class="branch-grid">
                @foreach ($branches as $branch)
                    <article class="card branch-card" data-reveal style="--i: {{ $loop->index % 3 }}">
                        <span class="badge @if ($branch->is_hub) badge--orange @endif">{{ $branch->kind() }}</span>
                        <h2>{{ $branch->name }}</h2>
                        <p><x-icon name="pin" /> {{ $branch->address }}, {{ $branch->city }}, {{ $branch->countryName() }}</p>
                        @if ($branch->phone)
                            <p><x-icon name="phone" /> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch->phone) }}">{{ $branch->phone }}</a></p>
                        @endif
                        @if ($branch->email)
                            <p><x-icon name="mail" /> <a href="mailto:{{ $branch->email }}">{{ $branch->email }}</a></p>
                        @endif
                        @if ($branch->opening_hours)
                            <p><x-icon name="clock" /> {{ $branch->opening_hours }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts::site>
