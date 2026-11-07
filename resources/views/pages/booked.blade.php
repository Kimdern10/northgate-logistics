<x-layouts::site current="ship" title="Pickup booked">
    <section class="section">
        <div class="container booked-wrap">
            <div class="card booked-card" data-reveal>
                <span class="success-icon success-icon--lg"><x-icon name="check" /></span>
                <h1>Your pickup is booked!</h1>
                <p class="lead">Save your tracking number. We've also emailed it{{ $shipment->sender_email ? ' to '.$shipment->sender_email : '' }} with your waybill.</p>

                <div class="tracking-ticket">
                    <span class="muted">Tracking number</span>
                    <strong data-copy="{{ $shipment->tracking_number }}">{{ $shipment->tracking_number }}</strong>
                    <button type="button" class="btn btn--ghost btn--sm" data-copy-button="{{ $shipment->tracking_number }}">Copy</button>
                </div>

                @if (session('paid'))
                    <p class="alert alert--success">Payment received, thank you! Your receipt is ready below.</p>
                @elseif (session('payment_failed'))
                    <p class="alert alert--error">The card payment did not go through. You can try again, or pay the courier at pickup.</p>
                @endif

                @if (! $shipment->isPaid() && $payUrl)
                    <div class="pay-box">
                        <span><strong>{{ \App\Support\Money::format($shipment->price_cents) }}</strong> to pay. Pay now by card, or pay the courier at pickup.</span>
                        <a class="btn btn--primary btn--sm" href="{{ $payUrl }}">Pay now by card</a>
                    </div>
                @endif

                <dl class="facts">
                    <div><dt>From</dt><dd>{{ $shipment->origin() }}</dd></div>
                    <div><dt>To</dt><dd>{{ $shipment->destination() }}</dd></div>
                    <div><dt>Service</dt><dd>{{ $shipment->service_level->label() }}</dd></div>
                    <div><dt>Pickup</dt><dd>{{ $shipment->pickup_date?->format('D j M Y') }}</dd></div>
                    <div><dt>{{ $shipment->isPaid() ? 'Paid' : 'Estimated price' }}</dt><dd>{{ \App\Support\Money::format($shipment->price_cents) }}</dd></div>
                    <div><dt>Expected by</dt><dd>{{ $shipment->estimated_delivery_date?->format('D j M Y') }}</dd></div>
                </dl>

                <div class="btn-row btn-row--center">
                    <a class="btn btn--primary" href="{{ $waybillUrl }}"><x-icon name="download" /> Download waybill (PDF)</a>
                    @if ($invoiceUrl)
                        <a class="btn btn--ghost" href="{{ $invoiceUrl }}"><x-icon name="download" /> Commercial invoice (PDF)</a>
                    @endif
                    <a class="btn btn--ghost" href="{{ $receiptUrl }}"><x-icon name="download" /> {{ $shipment->isPaid() ? 'Receipt' : 'Invoice' }} (PDF)</a>
                    <a class="btn btn--ghost" href="{{ route('track.show', $shipment->tracking_number) }}" wire:navigate.hover>Track shipment</a>
                </div>
                <p class="small-print">Print the waybill and stick it on the parcel, or show it on your phone when the courier arrives.@if ($invoiceUrl) Sign the commercial invoice and put it in a clear pouch on the box: customs need it.@endif</p>
            </div>
        </div>
    </section>
</x-layouts::site>
