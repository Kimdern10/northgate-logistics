<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0c2340; font-size: 11px; }
        .top { width: 100%; border-bottom: 3px solid #ff6b1a; padding-bottom: 10px; }
        .top td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; }
        .brand span { color: #ff6b1a; }
        .muted { color: #5b6b80; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #5b6b80; }
        .tracking { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .barcode { text-align: center; margin: 14px 0 4px; }
        .parties { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .parties td { width: 50%; border: 1px solid #c9d3df; padding: 10px 12px; vertical-align: top; }
        .parties h3 { margin: 0 0 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #ff6b1a; }
        .name { font-size: 14px; font-weight: bold; }
        .details { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .details td { border: 1px solid #c9d3df; padding: 8px 10px; vertical-align: top; width: 25%; }
        .service { background: #0c2340; color: #fff; padding: 8px 12px; font-size: 14px; font-weight: bold; margin-top: 12px; }
        .sign { width: 100%; margin-top: 26px; }
        .sign td { width: 50%; padding-top: 30px; border-top: 1px solid #0c2340; }
        .cut { border-top: 1px dashed #9aa7b6; margin: 22px 0 6px; text-align: center; color: #9aa7b6; font-size: 9px; }
        .foot { margin-top: 18px; font-size: 9px; color: #5b6b80; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">{{ $site['business_name'] }}<span>.</span></div>
                <div class="muted">{{ $site['address'] }}<br>{{ $site['phone'] }} · {{ $site['email'] }}</div>
            </td>
            <td style="text-align: right;">
                <div class="label">Waybill / tracking number</div>
                <div class="tracking">{{ $shipment->tracking_number }}</div>
                <div class="muted">Booked {{ $shipment->created_at?->format('j M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="barcode">
        <img src="{{ $barcode }}" style="height: 64px;" alt="">
        <div style="letter-spacing: 3px;">{{ $shipment->tracking_number }}</div>
    </div>

    <div class="service">{{ $shipment->service_level->label() }} &nbsp;·&nbsp; {{ $shipment->origin() }} &rarr; {{ $shipment->destination() }}</div>

    <table class="parties">
        <tr>
            <td>
                <h3>From (sender)</h3>
                <div class="name">{{ $shipment->sender_name }}</div>
                @if ($shipment->sender_company)<div>{{ $shipment->sender_company }}</div>@endif
                <div>{{ $shipment->sender_address }}</div>
                <div>{{ $shipment->sender_city }}, {{ \App\Support\Countries::name($shipment->sender_country) }}</div>
                <div>Tel: {{ $shipment->sender_phone }}</div>
            </td>
            <td>
                <h3>To (receiver)</h3>
                <div class="name">{{ $shipment->receiver_name }}</div>
                @if ($shipment->receiver_company)<div>{{ $shipment->receiver_company }}</div>@endif
                <div>{{ $shipment->receiver_address }}</div>
                <div>{{ $shipment->receiver_city }}, {{ \App\Support\Countries::name($shipment->receiver_country) }}</div>
                <div>Tel: {{ $shipment->receiver_phone }}</div>
            </td>
        </tr>
    </table>

    <table class="details">
        <tr>
            <td><div class="label">Contents</div>{{ $shipment->contents }}</td>
            <td><div class="label">Pieces</div>{{ $shipment->pieces }}</td>
            <td><div class="label">Weight</div>{{ rtrim(rtrim(number_format($shipment->weight_kg, 2), '0'), '.') }} kg</td>
            <td><div class="label">Dimensions</div>{{ $shipment->dimensions() ?? '—' }}</td>
        </tr>
        <tr>
            <td><div class="label">Declared value</div>{{ \App\Support\Money::format($shipment->declared_value_cents) }}</td>
            <td><div class="label">Shipping charge</div>{{ \App\Support\Money::format($shipment->price_cents) }}</td>
            <td><div class="label">Pickup date</div>{{ $shipment->pickup_date?->format('j M Y') ?? '—' }}</td>
            <td><div class="label">Expected by</div>{{ $shipment->estimated_delivery_date?->format('j M Y') ?? '—' }}</td>
        </tr>
        @if ($shipment->notes)
            <tr><td colspan="4"><div class="label">Notes</div>{{ $shipment->notes }}</td></tr>
        @endif
    </table>

    <table class="sign">
        <tr>
            <td>Collected by (courier) &nbsp; Name / signature / date</td>
            <td style="padding-left: 20px;">Received by &nbsp; Name / signature / date</td>
        </tr>
    </table>

    <div class="cut">✂ - - - - cut here and attach to the parcel - - - -</div>

    <table class="top" style="border: 2px solid #0c2340; padding: 10px;">
        <tr>
            <td>
                <div class="label">Deliver to</div>
                <div class="name" style="font-size: 16px;">{{ $shipment->receiver_name }}</div>
                <div>{{ $shipment->receiver_address }}, {{ $shipment->receiver_city }}</div>
                <div><strong>{{ strtoupper(\App\Support\Countries::name($shipment->receiver_country)) }}</strong> · Tel: {{ $shipment->receiver_phone }}</div>
            </td>
            <td style="text-align: right;">
                <img src="{{ $barcode }}" style="height: 48px;" alt=""><br>
                <strong style="letter-spacing: 2px;">{{ $shipment->tracking_number }}</strong><br>
                {{ $shipment->pieces }} pc · {{ rtrim(rtrim(number_format($shipment->weight_kg, 2), '0'), '.') }} kg · {{ $shipment->service_level->label() }}
            </td>
        </tr>
    </table>

    <p class="foot">Track this shipment at {{ route('track.show', $shipment->tracking_number) }}. Carriage is subject to {{ $site['business_name'] }}'s terms of carriage.</p>
</body>
</html>
