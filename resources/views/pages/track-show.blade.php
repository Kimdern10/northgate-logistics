<x-layouts::site current="track" :title="$shipment ? 'Tracking '.$shipment->tracking_number : 'Track a shipment'" description="Track your parcel by tracking number and see every update from pickup to delivery.">
    <section class="page-hero page-hero--track">
        <div class="container">
            <p class="eyebrow" data-reveal>Tracking</p>
            <h1 data-reveal>Where's my shipment?</h1>
            <form class="track-inline" action="{{ route('track') }}" method="get" data-reveal>
                <label class="sr-only" for="track-number">Tracking number</label>
                <input id="track-number" name="number" value="{{ $number }}" placeholder="Tracking number, e.g. NT4827301956" autocomplete="off" required>
                <button class="btn btn--primary" type="submit"><x-icon name="search" /> Track</button>
            </form>
            <p class="hint" data-reveal>Track up to {{ \App\Models\Shipment::MAX_TRACKED_AT_ONCE }} at once: separate the numbers with commas or spaces.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($shipment)
                @include('partials.tracking-result', ['shipment' => $shipment])
            @else
                <div class="card empty-state">
                    <x-icon name="alert" />
                    <h2>We couldn't find {{ $number }}</h2>
                    <p>Check the number on your waybill or booking email and try again. New bookings can take a few minutes to appear.</p>
                    <a class="btn btn--ghost" href="{{ route('contact') }}" wire:navigate.hover>Ask our team</a>
                </div>
            @endif
        </div>
    </section>
</x-layouts::site>
