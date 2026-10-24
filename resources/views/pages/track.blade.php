@use('App\Enums\ShipmentStatus')

@php
    $steps = ['Booked', 'Picked up', 'In transit', 'Out for delivery', 'Delivered'];
    $currentStep = $shipment?->status->step();
@endphp

<x-layouts::site current="track" :title="$shipment ? 'Tracking '.$shipment->tracking_number : 'Track a shipment'" description="Track your parcel by tracking number and see every update from pickup to delivery.">
    <section class="page-hero page-hero--track">
        <div class="container">
            <p class="eyebrow" data-reveal>Tracking</p>
            <h1 data-reveal>Where's my shipment?</h1>
            <form class="track-inline" action="{{ route('track') }}" method="get" data-reveal>
                <label class="sr-only" for="track-number">Tracking number</label>
                <input id="track-number" name="number" value="{{ $number }}" placeholder="Tracking number, e.g. NG4827301956" autocomplete="off" required>
                <button class="btn btn--primary" type="submit"><x-icon name="search" /> Track</button>
            </form>
            <p class="hint" data-reveal>Track up to {{ \App\Http\Controllers\TrackingController::MAX_AT_ONCE }} at once: separate the numbers with commas or spaces.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($number !== '' && ! $shipment)
                <div class="card empty-state" data-reveal>
                    <x-icon name="alert" />
                    <h2>We couldn't find {{ $number }}</h2>
                    <p>Check the number on your waybill or booking email and try again. New bookings can take a few minutes to appear.</p>
                    <a class="btn btn--ghost" href="{{ route('contact') }}" wire:navigate.hover>Ask our team</a>
                </div>
            @elseif ($shipment)
                <div class="track-result">
                    <div class="card track-summary" data-reveal>
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
                            <div><dt>Pieces</dt><dd>{{ $shipment->pieces }} · {{ rtrim(rtrim(number_format($shipment->weight_kg, 2), '0'), '.') }} kg</dd></div>
                            @if ($shipment->status === ShipmentStatus::Delivered && $shipment->delivered_at)
                                <div><dt>Delivered</dt><dd>{{ $shipment->delivered_at->format('D j M Y, g:i a') }}@if ($shipment->received_by)<br><small>Signed by {{ $shipment->received_by }}</small>@endif</dd></div>
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

                    <div class="card timeline-card" data-reveal>
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
            @else
                <div class="track-help" data-reveal>
                    <div class="card"><x-icon name="search" /><h2>Your tracking number</h2><p>It's on your waybill and in your booking email. It starts with NG and has 12 characters.</p></div>
                    <div class="card"><x-icon name="mail" /><h2>Email updates</h2><p>We email the sender (and the receiver, if we have their email) every time the status changes.</p></div>
                    <div class="card"><x-icon name="phone" /><h2>Need help?</h2><p>Call {{ \App\Models\Setting::get('phone') }} or <a href="{{ route('contact') }}" wire:navigate.hover>send us a message</a>.</p></div>
                </div>
            @endif
        </div>
    </section>
</x-layouts::site>
