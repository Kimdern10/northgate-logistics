<x-mail::message>
# {{ $event->status->label() }}

Shipment **{{ $shipment->tracking_number }}** from {{ $shipment->origin() }} to {{ $shipment->destination() }} has a new update.

<x-mail::panel>
**{{ $event->status->label() }}** · {{ $event->location }}<br>
{{ $event->happened_at->format('D j M Y, g:i a') }}
@if ($event->description)
<br>{{ $event->description }}
@endif
</x-mail::panel>

@if ($shipment->estimated_delivery_date && $event->status !== \App\Enums\ShipmentStatus::Delivered)
Expected delivery: **{{ $shipment->estimated_delivery_date->format('l, j F Y') }}**
@endif

<x-mail::button :url="$trackUrl">
See the full timeline
</x-mail::button>

Thanks,<br>
{{ $site['business_name'] }}
</x-mail::message>
