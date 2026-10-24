<x-mail::message>
# Your pickup is booked

Hi {{ $shipment->sender_name }},

Thank you for shipping with {{ $site['business_name'] }}. Our courier will collect your parcel{{ $shipment->pickup_date ? ' on '.$shipment->pickup_date->format('l, j F Y') : '' }}.

<x-mail::panel>
**Tracking number:** {{ $shipment->tracking_number }}<br>
**From:** {{ $shipment->origin() }}<br>
**To:** {{ $shipment->destination() }}<br>
**Service:** {{ $shipment->service_level->label() }}<br>
**Estimated price:** {{ \App\Support\Money::format($shipment->price_cents) }}
</x-mail::panel>

Your waybill is attached. Please print it and stick it on the parcel, or show it to the courier on your phone.

<x-mail::button :url="$trackUrl">
Track your shipment
</x-mail::button>

Lost the attachment? [Download the waybill again]({{ $waybillUrl }}).

Questions? Reply to this email or call {{ $site['phone'] }}.

Thanks,<br>
{{ $site['business_name'] }}
</x-mail::message>
