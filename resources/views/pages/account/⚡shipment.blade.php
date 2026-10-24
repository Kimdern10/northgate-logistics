<?php

use App\Models\Shipment;
use App\Support\Countries;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public Shipment $shipment;

    public function mount(Shipment $shipment): void
    {
        Gate::authorize('view', $shipment);

        $this->shipment = $shipment->load('events');
    }

    /**
     * Sender or receiver (not someone who only added the number) sees full names and addresses.
     */
    public function isParty(): bool
    {
        $user = auth()->user();

        return $user->is_admin
            || $user->sentShipment($this->shipment)
            || ($user->hasVerifiedEmail() && strtolower((string) $this->shipment->receiver_email) === strtolower($user->email));
    }

    public function isWatching(): bool
    {
        return auth()->user()->watchedShipments()->whereKey($this->shipment->id)->exists();
    }

    public function unwatch()
    {
        auth()->user()->watchedShipments()->detach($this->shipment->id);

        Flux::toast(text: "{$this->shipment->tracking_number} was removed from your shipments.");

        return $this->redirectRoute('account.shipments', navigate: true);
    }

    public function render()
    {
        return $this->view()->title($this->shipment->tracking_number);
    }
}; ?>

<div class="flex flex-col gap-6">
    @php($steps = ['Booked', 'Picked up', 'In transit', 'Out for delivery', 'Delivered'])
    @php($currentStep = $shipment->status->step())

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:text><flux:link :href="route('account.shipments')" wire:navigate>My shipments</flux:link></flux:text>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ $shipment->tracking_number }}</flux:heading>
                <flux:badge :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
            </div>
            <flux:text>{{ $shipment->origin() }} → {{ $shipment->destination() }} · {{ $shipment->service_level->label() }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('downloadWaybill', $shipment)
                <flux:button icon="printer" :href="route('account.shipments.waybill', $shipment)">Waybill PDF</flux:button>
                <flux:button icon="arrow-path" :href="route('ship', ['again' => $shipment->tracking_number])">Ship again</flux:button>
            @endcan
            <flux:button icon="share" :href="route('track.show', $shipment->tracking_number)" target="_blank">Public tracking page</flux:button>
            @if ($this->isWatching())
                <flux:button variant="ghost" icon="eye-slash" wire:click="unwatch">Remove</flux:button>
            @endif
        </div>
    </div>

    @if (session('booked'))
        <flux:callout icon="check-circle" color="green" heading="Your pickup is booked!">
            <flux:callout.text>We've emailed you the waybill. Print it and stick it on the parcel, or download it with the Waybill PDF button. Our courier will come on {{ $shipment->pickup_date?->format('l j F') }}.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($currentStep !== null)
        <flux:card>
            <div class="grid grid-cols-5 gap-2 text-center text-xs font-semibold">
                @foreach ($steps as $index => $label)
                    <div class="flex flex-col items-center gap-2">
                        <span @class(['h-2 w-full rounded-full', 'bg-orange-500' => $index <= $currentStep, 'bg-zinc-200 dark:bg-zinc-700' => $index > $currentStep])></span>
                        <span @class(['text-zinc-900 dark:text-white' => $index <= $currentStep, 'text-zinc-500' => $index > $currentStep])>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <flux:text class="mt-4">
                @if ($shipment->delivered_at)
                    Delivered {{ $shipment->delivered_at->format('l j F Y, g:i a') }}{{ $shipment->received_by ? ', signed by '.$shipment->received_by : '' }}.
                @elseif ($shipment->estimated_delivery_date)
                    Expected by <strong>{{ $shipment->estimated_delivery_date->format('l j F Y') }}</strong>.
                @endif
            </flux:text>
        </flux:card>
    @else
        <flux:callout icon="exclamation-triangle" color="red" heading="{{ $shipment->status->label() }}">
            <flux:callout.text>See the latest update below, or contact us for help.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-5">
        <flux:card class="flex flex-col gap-4 xl:col-span-3">
            <flux:heading size="lg">Shipment history</flux:heading>
            <ol class="flex flex-col gap-4 border-s border-zinc-200 ps-5 dark:border-zinc-700">
                @foreach ($shipment->events as $event)
                    <li class="relative" wire:key="event-{{ $event->id }}">
                        <span @class(['absolute -start-[1.65rem] top-1 size-3 rounded-full ring-4 ring-white dark:ring-zinc-900', 'bg-orange-500' => $loop->first, 'bg-zinc-300 dark:bg-zinc-600' => ! $loop->first])></span>
                        <div class="font-medium">{{ $event->status->label() }} <span class="font-normal text-zinc-500">· {{ $event->location }}</span></div>
                        @if ($event->description)<flux:text>{{ $event->description }}</flux:text>@endif
                        <div class="text-xs text-zinc-500">{{ $event->happened_at->format('D j M Y, g:i a') }}</div>
                    </li>
                @endforeach
            </ol>
        </flux:card>

        <div class="flex flex-col gap-6 xl:col-span-2">
            @if ($this->isParty())
                @foreach (['sender' => 'From', 'receiver' => 'To'] as $prefix => $heading)
                    <flux:card class="flex flex-col gap-1">
                        <flux:heading>{{ $heading }}</flux:heading>
                        <div class="font-medium">{{ $shipment->{$prefix.'_name'} }}</div>
                        @if ($shipment->{$prefix.'_company'})<flux:text>{{ $shipment->{$prefix.'_company'} }}</flux:text>@endif
                        <flux:text>{{ $shipment->{$prefix.'_address'} }}, {{ $shipment->{$prefix.'_city'} }}, {{ Countries::name($shipment->{$prefix.'_country'}) }}</flux:text>
                        <flux:text>{{ $shipment->{$prefix.'_phone'} }}</flux:text>
                    </flux:card>
                @endforeach
            @endif

            <flux:card>
                <flux:heading class="mb-3">Parcel</flux:heading>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-zinc-500">Contents</dt><dd>{{ $shipment->contents }}</dd>
                    <dt class="text-zinc-500">Pieces / weight</dt><dd>{{ $shipment->pieces }} · {{ $shipment->weight_kg }} kg</dd>
                    @if ($this->isParty())
                        <dt class="text-zinc-500">Price</dt><dd>{{ Money::format($shipment->price_cents) }}</dd>
                    @endif
                    <dt class="text-zinc-500">Pickup date</dt><dd>{{ $shipment->pickup_date?->format('D j M Y') ?? '—' }}</dd>
                </dl>
            </flux:card>
        </div>
    </div>
</div>
