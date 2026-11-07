{{-- One shipment's public tracking card and history. Used by the live tracking search and /track/{number}. --}}
@use('App\Enums\ShipmentStatus')

@php
    $steps = ['Booked', 'Picked up', 'In transit', 'Out for delivery', 'Delivered'];
    $currentStep = $shipment->status->step();
@endphp

<div class="track-result" wire:key="result-{{ $shipment->tracking_number }}">
    <div class="card track-summary">
        <div class="track-head">
            <div>
                <span class="muted">Tracking number</span>
                <strong class="track-number">{{ $shipment->tracking_number }}</strong>
            </div>
            <span class="status-pill status-pill--{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
        </div>

        <div class="track-route">
            <div><span class="muted">From</span><strong>{{ $shipment->origin() }}</strong></div>
            <span class="route-line route-line--animated"><x-icon name="{{ $shipment->service_level === \App\Enums\ServiceLevel::Economy ? 'ship' : 'plane' }}" /></span>
            <div class="text-end"><span class="muted">To</span><strong>{{ $shipment->destination() }}</strong></div>
        </div>

        @if ($currentStep !== null)
            <ol class="progress" style="--step: {{ $currentStep }}">
                @foreach ($steps as $index => $label)
                    <li @class(['done' => $index <= $currentStep, 'current' => $index === $currentStep])><span></span>{{ $label }}</li>
                @endforeach
            </ol>
        @else
            <p class="alert alert--error">
                @if ($shipment->status === ShipmentStatus::OnHold)
                    This shipment is on hold. See the latest update below, or contact us for help.
                @else
                    This shipment was cancelled.
                @endif
            </p>
        @endif

        <dl class="facts">
            <div><dt>Service</dt><dd>{{ $shipment->service_level->label() }}</dd></div>
            <div><dt>For</dt><dd>{{ $shipment->receiverShortName() }}</dd></div>
            <div><dt>Pieces</dt><dd>{{ $shipment->pieces }} · {{ rtrim(rtrim(number_format($shipment->weight_lb, 2), '0'), '.') }} lb</dd></div>
            @if ($shipment->status === ShipmentStatus::Delivered && $shipment->delivered_at)
                <div><dt>Delivered</dt><dd>{{ $shipment->delivered_at->format('D j M Y, g:i a') }}@if ($shipment->received_by)<br><small>Signed by {{ $shipment->received_by }}</small>@endif</dd></div>
                @if ($shipment->delivery_photo_path)
                    <div><dt>Proof of delivery</dt><dd>
                        @can('viewProofOfDelivery', $shipment)
                            <a href="{{ route('shipments.proof', $shipment->tracking_number) }}" target="_blank">See the delivery photo</a>
                        @else
                            Photo on file. <small><a href="{{ route('login') }}">Log in</a> as the sender or receiver to see it.</small>
                        @endcan
                    </dd></div>
                @endif
            @elseif ($shipment->estimated_delivery_date)
                <div><dt>Expected by</dt><dd>{{ $shipment->estimated_delivery_date->format('D j M Y') }}</dd></div>
            @endif
        </dl>

        <div class="track-actions">
            @auth
                <form method="post" action="{{ route('track.watch', $shipment->tracking_number) }}">
                    @csrf
                    <button class="btn btn--ghost btn--sm" type="submit"><x-icon name="eye" /> Add to my shipments</button>
                </form>
                <span>Keep it in your account and get an email at every update.</span>
            @else
                <a class="btn btn--ghost btn--sm" href="{{ route('login') }}"><x-icon name="user" /> Log in to follow this shipment</a>
                <span>Get an email at every update. <a href="{{ route('register') }}">Create a free account</a>.</span>
            @endauth
        </div>
    </div>

    <div class="card timeline-card">
        <h2>Shipment history</h2>
        <ol class="timeline">
            @foreach ($shipment->events as $event)
                <li @class(['timeline-item', 'is-latest' => $loop->first]) style="--i: {{ $loop->index }}">
                    <span class="timeline-dot"></span>
                    <div>
                        <strong>{{ $event->status->label() }}</strong>
                        <span class="muted">{{ $event->location }}</span>
                        @if ($event->description)
                            <p>{{ $event->description }}</p>
                        @endif
                    </div>
                    <time datetime="{{ $event->happened_at->toAtomString() }}">{{ $event->happened_at->format('j M Y') }}<br>{{ $event->happened_at->format('g:i a') }}</time>
                </li>
            @endforeach
        </ol>
    </div>
</div>
