<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Commercial invoice {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 30px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0c2340; font-size: 10.5px; }
        h1 { font-size: 20px; margin: 0; letter-spacing: 1px; }
        .muted { color: #5b6b80; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #5b6b80; margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .top td { vertical-align: top; }
        .box td { width: 50%; border: 1px solid #c9d3df; padding: 9px 11px; vertical-align: top; }
        .box h3 { margin: 0 0 5px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #ff6b1a; }
        .facts td { border: 1px solid #c9d3df; padding: 7px 9px; width: 25%; vertical-align: top; }
        .items th { background: #0c2340; color: #fff; text-align: left; padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .items td { border-bottom: 1px solid #dfe5ee; padding: 7px 8px; }
        .num { text-align: right; }
        .total td { font-weight: bold; font-size: 12px; border-bottom: 0; padding-top: 10px; }
        .declare { margin-top: 18px; padding: 10px 12px; border: 1px solid #c9d3df; font-size: 9.5px; }
        .sign td { width: 50%; padding-top: 34px; border-top: 1px solid #0c2340; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <h1>COMMERCIAL INVOICE</h1>
                <div class="muted">For customs clearance</div>
            </td>
            <td style="text-align: right;">
                <div class="label">Invoice / waybill number</div>
                <div style="font-size: 16px; font-weight: bold; letter-spacing: 1px;">{{ $shipment->tracking_number }}</div>
                <div class="muted">Date: {{ ($shipment->pickup_date ?? $shipment->created_at)?->format('j M Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="box" style="margin-top: 16px;">
        <tr>
            <td>
                <h3>Exporter / sender</h3>
                <strong>{{ $shipment->sender_name }}</strong><br>
                @if ($shipment->sender_company){{ $shipment->sender_company }}<br>@endif
                {{ $shipment->sender_address }}<br>
                {{ $shipment->sender_city }}, {{ \App\Support\Countries::name($shipment->sender_country) }}<br>
                Tel: {{ $shipment->sender_phone }}@if ($shipment->sender_email) · {{ $shipment->sender_email }}@endif
            </td>
            <td>
                <h3>Consignee / receiver</h3>
                <strong>{{ $shipment->receiver_name }}</strong><br>
                @if ($shipment->receiver_company){{ $shipment->receiver_company }}<br>@endif
                {{ $shipment->receiver_address }}<br>
                {{ $shipment->receiver_city }}, {{ \App\Support\Countries::name($shipment->receiver_country) }}<br>
                Tel: {{ $shipment->receiver_phone }}@if ($shipment->receiver_email) · {{ $shipment->receiver_email }}@endif
            </td>
        </tr>
    </table>

    <table class="facts" style="margin-top: 12px;">
        <tr>
            <td><div class="label">Reason for export</div>{{ $shipment->exportReasonLabel() ?? '—' }}</td>
            <td><div class="label">Carrier / service</div>{{ $site['business_name'] }}<br>{{ $shipment->service_level->label() }}</td>
            <td><div class="label">Pieces / weight</div>{{ $shipment->pieces }} pc · {{ rtrim(rtrim(number_format($shipment->weight_lb, 2), '0'), '.') }} lb</td>
            <td><div class="label">Currency</div>US dollars (USD)</td>
        </tr>
    </table>

    <table class="items" style="margin-top: 16px;">
        <thead>
            <tr>
                <th>Description of goods</th>
                <th>HS code</th>
                <th>Made in</th>
                <th class="num">Qty</th>
                <th class="num">Unit value</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shipment->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->hs_code ?? '—' }}</td>
                    <td>{{ $item->originName() }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ \App\Support\Money::format($item->unit_value_cents) }}</td>
                    <td class="num">{{ \App\Support\Money::format($item->totalValueCents()) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="5" class="num">Total declared value</td>
                <td class="num">{{ \App\Support\Money::format($shipment->declared_value_cents) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="declare">
        I declare that the information on this invoice is true and correct, and that the contents of this shipment are as stated above.
    </div>

    <table class="sign" style="margin-top: 10px;">
        <tr>
            <td>Signature of exporter</td>
            <td style="padding-left: 20px;">Name and date</td>
        </tr>
    </table>
</body>
</html>
