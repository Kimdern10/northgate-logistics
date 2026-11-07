<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $shipment->isPaid() ? 'Receipt' : 'Invoice' }} {{ $number }}</title>
    <style>
        @page { margin: 34px 38px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0c2340; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        .top td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; }
        .brand span { color: #ff6b1a; }
        .muted { color: #5b6b80; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #5b6b80; margin-bottom: 3px; }
        .doc { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .stamp { display: inline-block; margin-top: 6px; padding: 4px 12px; border-radius: 4px; font-weight: bold; letter-spacing: 1px; }
        .paid { background: #dcfce7; color: #15803d; }
        .unpaid { background: #fef3c7; color: #b45309; }
        .parties td { width: 50%; vertical-align: top; padding-top: 20px; }
        .lines { margin-top: 22px; }
        .lines th { text-align: left; padding: 8px; background: #0c2340; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .lines td { padding: 9px 8px; border-bottom: 1px solid #dfe5ee; }
        .num { text-align: right; }
        .total td { font-size: 14px; font-weight: bold; border-bottom: 0; border-top: 2px solid #0c2340; }
        .note { margin-top: 22px; padding: 12px 14px; background: #f5f7fb; border-radius: 6px; }
        .foot { margin-top: 30px; font-size: 9px; color: #5b6b80; }
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
                <div class="doc">{{ $shipment->isPaid() ? 'RECEIPT' : 'INVOICE' }}</div>
                <div>No. {{ $number }}</div>
                <div class="muted">Date: {{ ($shipment->paid_at ?? $shipment->created_at)?->format('j M Y') }}</div>
                <span class="stamp {{ $shipment->isPaid() ? 'paid' : 'unpaid' }}">{{ $shipment->isPaid() ? 'PAID' : 'AMOUNT DUE' }}</span>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <div class="label">Billed to</div>
                <strong>{{ $shipment->sender_name }}</strong><br>
                @if ($shipment->sender_company){{ $shipment->sender_company }}<br>@endif
                {{ $shipment->sender_address }}, {{ $shipment->sender_city }}<br>
                {{ \App\Support\Countries::name($shipment->sender_country) }}<br>
                @if ($shipment->sender_email){{ $shipment->sender_email }}@endif
            </td>
            <td>
                <div class="label">Shipment</div>
                Tracking number <strong>{{ $shipment->tracking_number }}</strong><br>
                {{ $shipment->service_level->label() }}<br>
                {{ $shipment->origin() }} → {{ $shipment->destination() }}<br>
                {{ $shipment->pieces }} pc · {{ rtrim(rtrim(number_format($shipment->weight_lb, 2), '0'), '.') }} lb
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr><th>Description</th><th class="num">Amount</th></tr>
        </thead>
        <tbody>
            @if ($shipment->base_fee_cents !== null)
                <tr><td>Base fee: {{ $shipment->service_level->label() }}</td><td class="num">{{ \App\Support\Money::format($shipment->base_fee_cents) }}</td></tr>
                <tr><td>Weight charge: {{ rtrim(rtrim(number_format((float) $shipment->chargeable_weight_lb, 2), '0'), '.') }} lb billed</td><td class="num">{{ \App\Support\Money::format((int) $shipment->weight_charge_cents) }}</td></tr>
                <tr><td>Fuel surcharge</td><td class="num">{{ \App\Support\Money::format((int) $shipment->fuel_surcharge_cents) }}</td></tr>
            @else
                <tr><td>Shipping: {{ $shipment->service_level->label() }}, {{ $shipment->origin() }} → {{ $shipment->destination() }}</td><td class="num">{{ \App\Support\Money::format($shipment->price_cents) }}</td></tr>
            @endif
            <tr class="total"><td>Total (USD)</td><td class="num">{{ \App\Support\Money::format($shipment->price_cents) }}</td></tr>
        </tbody>
    </table>

    <div class="note">
        @if ($shipment->isPaid())
            Paid {{ $shipment->paid_at?->format('j M Y, g:i a') }}@if ($shipment->paymentMethodLabel()) by {{ strtolower($shipment->paymentMethodLabel()) }}@endif.@if ($shipment->payment_reference) Reference: {{ $shipment->payment_reference }}.@endif Thank you for shipping with us.
        @else
            Please pay {{ \App\Support\Money::format($shipment->price_cents) }} to the courier at pickup (card, cash or transfer), or online from your account.
        @endif
    </div>

    <p class="foot">Duties and taxes charged by the destination country are not included. Questions about this {{ $shipment->isPaid() ? 'receipt' : 'invoice' }}? Contact {{ $site['email'] }}.</p>
</body>
</html>
