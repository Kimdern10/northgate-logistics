{{-- One job card on the driver page. $mode is pickups, deliveries or found (scanned). --}}
@use('App\Enums\ShipmentStatus')

@php
    $collecting = $shipment->status === ShipmentStatus::Booked;
    $prefix = $collecting ? 'sender' : 'receiver';
    $address = $shipment->{$prefix.'_address'}.', '.$shipment->{$prefix.'_city'}.', '.\App\Support\Countries::name($shipment->{$prefix.'_country'});
    $phone = $shipment->{$prefix.'_phone'};
@endphp

<flux:card class="flex flex-col gap-3" wire:key="job-{{ $mode }}-{{ $shipment->id }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="font-mono text-lg font-semibold tracking-wide">{{ $shipment->tracking_number }}</div>
            <flux:text>{{ $collecting ? 'Collect from' : 'Deliver to' }} <strong>{{ $shipment->{$prefix.'_name'} }}</strong>{{ $shipment->{$prefix.'_company'} ? ' · '.$shipment->{$prefix.'_company'} : '' }}</flux:text>
        </div>
        <flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
    </div>

    <flux:text>{{ $address }}</flux:text>
    <flux:text class="text-xs">{{ $shipment->pieces }} pc · {{ rtrim(rtrim(number_format($shipment->weight_lb, 2), '0'), '.') }} lb · {{ $shipment->contents }}@if ($shipment->notes) · <em>{{ $shipment->notes }}</em>@endif</flux:text>
    @if (! $shipment->isPaid() && $collecting)
        <flux:text class="text-xs font-semibold text-amber-600">Collect payment: {{ \App\Support\Money::format($shipment->price_cents) }}</flux:text>
    @endif

    <div class="grid grid-cols-2 gap-2">
        <flux:button icon="phone" :href="'tel:'.preg_replace('/[^0-9+]/', '', $phone)">Call</flux:button>
        <flux:button icon="map" :href="'https://www.google.com/maps/dir/?api=1&destination='.urlencode($address)" target="_blank">Directions</flux:button>
    </div>

    @if ($deliveringId === $shipment->id)
        <form wire:submit="delivered" class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
            <flux:input wire:model="receivedBy" label="Who received it?" placeholder="Name on the signature" />
            <flux:input type="file" wire:model="deliveryPhoto" accept="image/*" capture="environment" label="Photo of the parcel at the door (optional)" />
            <div wire:loading wire:target="deliveryPhoto" class="text-sm text-zinc-500">Uploading photo…</div>
            <div class="grid grid-cols-2 gap-2">
                <flux:button wire:click="cancel">Back</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Confirm delivered</flux:button>
            </div>
        </form>
    @elseif ($failingId === $shipment->id)
        <div class="flex flex-col gap-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
            <flux:text class="font-medium">Why couldn't you deliver it?</flux:text>
            @foreach (\App\Models\Shipment::FAILED_DELIVERY_REASONS as $key => $label)
                <flux:button wire:click="failed('{{ $key }}')" wire:confirm="{{ $label }}: mark {{ $shipment->tracking_number }} as not delivered?">{{ $label }}</flux:button>
            @endforeach
            <flux:button variant="ghost" wire:click="cancel">Back</flux:button>
        </div>
    @else
        @switch($shipment->status)
            @case(ShipmentStatus::Booked)
                <flux:button variant="primary" icon="check" class="w-full" wire:click="pickedUp({{ $shipment->id }})">Picked up</flux:button>
                @break
            @case(ShipmentStatus::OutForDelivery)
                <div class="grid grid-cols-2 gap-2">
                    <flux:button variant="primary" icon="check" wire:click="openDelivered({{ $shipment->id }})">Delivered</flux:button>
                    <flux:button icon="x-mark" wire:click="openFailed({{ $shipment->id }})">Couldn't deliver</flux:button>
                </div>
                @break
            @case(ShipmentStatus::Delivered)
            @case(ShipmentStatus::Cancelled)
                <flux:text>Nothing to do: this parcel is {{ strtolower($shipment->status->label()) }}.</flux:text>
                @break
            @default
                <flux:button variant="primary" icon="truck" class="w-full" wire:click="outForDelivery({{ $shipment->id }})">Take it out for delivery</flux:button>
        @endswitch
    @endif
</flux:card>
