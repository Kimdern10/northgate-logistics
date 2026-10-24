<x-layouts::site current="track" title="Track several shipments">
    <section class="page-hero page-hero--track">
        <div class="container">
            <p class="eyebrow" data-reveal>Tracking</p>
            <h1 data-reveal>{{ $numbers->count() }} shipments</h1>
            <form class="track-inline" action="{{ route('track') }}" method="get" data-reveal>
                <label class="sr-only" for="track-number">Tracking numbers</label>
                <input id="track-number" name="number" value="{{ $numbers->implode(', ') }}" autocomplete="off" required>
                <button class="btn btn--primary" type="submit"><x-icon name="search" /> Track</button>
            </form>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container multi-track">
            @if ($skipped > 0)
                <p class="alert alert--error">You can track up to {{ \App\Http\Controllers\TrackingController::MAX_AT_ONCE }} at once. {{ $skipped }} more {{ str('number')->plural($skipped) }} were left out.</p>
            @endif

            @foreach ($numbers as $number)
                @php($shipment = $shipments[$number] ?? null)
                <a @class(['card', 'multi-row', 'multi-row--missing' => ! $shipment]) href="{{ route('track.show', $number) }}" data-reveal style="--i: {{ $loop->index }}">
                    <div>
                        <strong class="multi-number">{{ $number }}</strong>
                        <span class="muted">{{ $shipment ? $shipment->origin().' → '.$shipment->destination() : 'Not found. Check the number.' }}</span>
                    </div>
                    @if ($shipment)
                        <div class="multi-meta">
                            <span class="status-pill status-pill--{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
                            <small class="muted">
                                @if ($shipment->delivered_at)
                                    Delivered {{ $shipment->delivered_at->format('j M') }}
                                @elseif ($shipment->estimated_delivery_date)
                                    Expected {{ $shipment->estimated_delivery_date->format('j M') }}
                                @endif
                            </small>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
</x-layouts::site>
