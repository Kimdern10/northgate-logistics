<?php

use App\Actions\RecordTrackingUpdate;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Support\Countries;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Shipment $shipment;

    /** Proof of delivery photo, sent with a "Delivered" update. */
    public $deliveryPhoto = null;

    public string $driverId = '';

    public string $paymentMethod = 'cash';

    /**
     * Assign (or unassign) the courier straight away when the select changes.
     */
    public function updatedDriverId(string $value): void
    {
        $driver = $value === '' ? null : \App\Models\User::query()->where('is_driver', true)->find($value);
        $this->shipment->forceFill(['driver_id' => $driver?->id])->save();

        Flux::toast(variant: 'success', text: $driver ? "Assigned to {$driver->name}." : 'Driver removed.');
    }

    public string $paymentReference = '';

    public function markPaid(): void
    {
        $validated = $this->validate([
            'paymentMethod' => ['required', Rule::in(array_keys(Shipment::PAYMENT_METHODS))],
            'paymentReference' => ['nullable', 'string', 'max:120'],
        ]);

        $this->shipment->markPaid($validated['paymentMethod'], $validated['paymentReference'] ?: null);
        $this->reset('paymentReference');

        Flux::toast(variant: 'success', text: 'Payment recorded. The customer can now download a receipt.');
    }

    public string $status = '';

    public string $location = '';

    public string $description = '';

    public string $happened_at = '';

    public string $received_by = '';

    public bool $notify = true;

    public function mount(Shipment $shipment): void
    {
        $this->shipment = $shipment;
        $this->driverId = (string) $shipment->driver_id;
        $this->resetUpdateForm();
    }

    private function resetUpdateForm(): void
    {
        $next = match ($this->shipment->status) {
            ShipmentStatus::Booked => ShipmentStatus::PickedUp,
            ShipmentStatus::PickedUp => ShipmentStatus::InTransit,
            ShipmentStatus::InTransit => ShipmentStatus::AtHub,
            ShipmentStatus::AtHub, ShipmentStatus::CustomsClearance => ShipmentStatus::OutForDelivery,
            ShipmentStatus::OutForDelivery => ShipmentStatus::Delivered,
            default => $this->shipment->status,
        };

        $this->status = $next->value;
        $this->location = $this->shipment->events()->first()?->location ?? $this->shipment->origin();
        $this->description = '';
        $this->received_by = '';
        $this->deliveryPhoto = null;
        $this->happened_at = now()->format('Y-m-d\TH:i');
        $this->notify = true;
    }

    public function addUpdate(RecordTrackingUpdate $recordUpdate): void
    {
        $validated = $this->validate([
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
            'location' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:255'],
            'happened_at' => ['required', 'date', 'before_or_equal:'.now()->addHour()->toDateTimeString()],
            'received_by' => ['nullable', 'string', 'max:120'],
            'deliveryPhoto' => ['nullable', 'image', 'max:10240'],
            'notify' => ['boolean'],
        ], [
            'happened_at.before_or_equal' => 'The date and time cannot be in the future.',
        ], ['deliveryPhoto' => 'delivery photo']);

        $recordUpdate->handle(
            $this->shipment,
            ShipmentStatus::from($validated['status']),
            $validated['location'],
            $validated['description'] ?: null,
            CarbonImmutable::parse($validated['happened_at']),
            $validated['notify'],
            $validated['received_by'] ?: null,
            $this->deliveryPhoto,
        );

        $this->shipment->refresh();
        $this->resetUpdateForm();

        Flux::toast(variant: 'success', text: $recordUpdate->emailed > 0
            ? 'Update added. '.$recordUpdate->emailed.' '.str('person')->plural($recordUpdate->emailed).' emailed.'
            : 'Update added.');
    }

    public function deleteEvent(int $eventId): void
    {
        $event = $this->shipment->events()->whereKey($eventId)->firstOrFail();
        $event->delete();

        // The shipment goes back to the status of its newest remaining update.
        if ($latest = $this->shipment->events()->first()) {
            $this->shipment->update([
                'status' => $latest->status,
                'delivered_at' => $latest->status === ShipmentStatus::Delivered ? $latest->happened_at : null,
            ]);
        }

        $this->shipment->refresh();
        Flux::toast(text: 'Update removed.');
    }

    public function deleteShipment()
    {
        $number = $this->shipment->tracking_number;
        $this->shipment->delete();

        Flux::toast(text: "Shipment {$number} deleted.");

        return $this->redirectRoute('admin.shipments.index', navigate: true);
    }

    public function render()
    {
        return $this->view()->title($this->shipment->tracking_number);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:text><flux:link :href="route('admin.shipments.index')" wire:navigate>Shipments</flux:link></flux:text>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl">{{ $shipment->tracking_number }}</flux:heading>
                <flux:badge :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
                @if ($shipment->isFromWebsite())<flux:badge color="orange" size="sm">Booked online</flux:badge>@endif
            </div>
            <flux:text>{{ $shipment->origin() }} → {{ $shipment->destination() }} · {{ $shipment->service_level->label() }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button icon="printer" :href="route('admin.shipments.waybill', $shipment)">Waybill PDF</flux:button>
            @if (\App\Support\CommercialInvoice::isNeeded($shipment))
                <flux:button icon="document-text" :href="route('admin.shipments.invoice', $shipment)">Commercial invoice</flux:button>
            @endif
            <flux:button icon="receipt-percent" :href="route('admin.shipments.receipt', $shipment)">{{ $shipment->isPaid() ? 'Receipt' : 'Invoice' }}</flux:button>
            <flux:button icon="eye" :href="route('track.show', $shipment->tracking_number)" target="_blank">Public page</flux:button>
            <flux:button icon="pencil-square" :href="route('admin.shipments.edit', $shipment)" wire:navigate>Edit</flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-5">
        <div class="flex flex-col gap-6 xl:col-span-3">
            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Add a tracking update</flux:heading>
                <form wire:submit="addUpdate" class="flex flex-col gap-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:select wire:model.live="status" label="Status">
                            @foreach (ShipmentStatus::cases() as $case)
                                <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="happened_at" type="datetime-local" label="Date and time" />
                    </div>
                    <flux:input wire:model="location" label="Location" placeholder="e.g. JFK Airport Hub, New York" />
                    <flux:input wire:model="description" label="Note (optional, shown to the customer)" placeholder="e.g. Departed on flight to London" />
                    @if ($status === ShipmentStatus::Delivered->value)
                        <flux:input wire:model="received_by" label="Received by (optional)" />
                        <div>
                            <flux:input type="file" wire:model="deliveryPhoto" accept="image/*" capture="environment" label="Proof of delivery photo (optional)" description="A photo of the parcel at the door. Only staff, the sender and the receiver can see it." />
                            <div wire:loading wire:target="deliveryPhoto" class="mt-1 text-sm text-zinc-500">Uploading photo…</div>
                            @if ($deliveryPhoto && method_exists($deliveryPhoto, 'temporaryUrl') && str_starts_with((string) $deliveryPhoto->getMimeType(), 'image/'))
                                <img src="{{ $deliveryPhoto->temporaryUrl() }}" alt="Photo preview" class="mt-2 h-28 rounded-lg object-cover">
                            @endif
                        </div>
                    @endif
                    <flux:checkbox wire:model="notify" label="Email the sender and receiver if the status changes" description="{{ implode(', ', $shipment->notifiableEmails()) ?: 'No email addresses on this shipment.' }}" />
                    <div><flux:button type="submit" variant="primary">Add update</flux:button></div>
                </form>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Timeline</flux:heading>
                <ol class="flex flex-col gap-4 border-s border-zinc-200 ps-5 dark:border-zinc-700">
                    @foreach ($shipment->events as $event)
                        <li class="relative" wire:key="event-{{ $event->id }}">
                            <span @class(['absolute -start-[1.65rem] top-1 size-3 rounded-full ring-4 ring-white dark:ring-zinc-900', 'bg-orange-500' => $loop->first, 'bg-zinc-300 dark:bg-zinc-600' => ! $loop->first])></span>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="font-medium">{{ $event->status->label() }} <span class="font-normal text-zinc-500">· {{ $event->location }}</span></div>
                                    @if ($event->description)<flux:text>{{ $event->description }}</flux:text>@endif
                                    <div class="text-xs text-zinc-500">{{ $event->happened_at->format('D j M Y, g:i a') }}</div>
                                </div>
                                @if ($shipment->events->count() > 1)
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteEvent({{ $event->id }})" wire:confirm="Remove this update?" aria-label="Remove update" />
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </flux:card>
        </div>

        <div class="flex flex-col gap-6 xl:col-span-2">
            @foreach (['sender' => 'Sender', 'receiver' => 'Receiver'] as $prefix => $heading)
                <flux:card class="flex flex-col gap-1">
                    <flux:heading>{{ $heading }}</flux:heading>
                    <div class="font-medium">{{ $shipment->{$prefix.'_name'} }}</div>
                    @if ($shipment->{$prefix.'_company'})<flux:text>{{ $shipment->{$prefix.'_company'} }}</flux:text>@endif
                    <flux:text>{{ $shipment->{$prefix.'_address'} }}, {{ $shipment->{$prefix.'_city'} }}, {{ Countries::name($shipment->{$prefix.'_country'}) }}</flux:text>
                    <flux:text><a href="tel:{{ $shipment->{$prefix.'_phone'} }}" class="hover:underline">{{ $shipment->{$prefix.'_phone'} }}</a>@if ($shipment->{$prefix.'_email'}) · <a href="mailto:{{ $shipment->{$prefix.'_email'} }}" class="hover:underline">{{ $shipment->{$prefix.'_email'} }}</a>@endif</flux:text>
                </flux:card>
            @endforeach

            <flux:card>
                <flux:heading class="mb-3">Parcel</flux:heading>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-zinc-500">Contents</dt><dd>{{ $shipment->contents }}</dd>
                    <dt class="text-zinc-500">Pieces / weight</dt><dd>{{ $shipment->pieces }} · {{ $shipment->weight_lb }} lb</dd>
                    <dt class="text-zinc-500">Size</dt><dd>{{ $shipment->dimensions() ?? '—' }}</dd>
                    <dt class="text-zinc-500">Declared value</dt><dd>{{ Money::format($shipment->declared_value_cents) }}</dd>
                    <dt class="text-zinc-500">Price</dt><dd class="font-medium">{{ Money::format($shipment->price_cents) }}</dd>
                    <dt class="text-zinc-500">Payment</dt>
                    <dd>
                        <flux:badge size="sm" :color="$shipment->payment_status->color()">{{ $shipment->payment_status->label() }}</flux:badge>
                        @if ($shipment->isPaid())
                            <div class="mt-1 text-xs text-zinc-500">{{ $shipment->paymentMethodLabel() }} · {{ $shipment->paid_at?->format('j M Y') }}{{ $shipment->payment_reference ? ' · '.$shipment->payment_reference : '' }}</div>
                        @endif
                    </dd>
                    <dt class="text-zinc-500">Pickup date</dt><dd>{{ $shipment->pickup_date?->format('D j M Y') ?? '—' }}</dd>
                    <dt class="text-zinc-500">Expected by</dt><dd>{{ $shipment->estimated_delivery_date?->format('D j M Y') ?? '—' }}</dd>
                    @if ($shipment->delivered_at)
                        <dt class="text-zinc-500">Delivered</dt><dd>{{ $shipment->delivered_at->format('D j M Y, g:i a') }}{{ $shipment->received_by ? ' · '.$shipment->received_by : '' }}</dd>
                    @endif
                </dl>
                @if ($shipment->delivery_photo_path)
                    <flux:separator class="my-3" />
                    <flux:heading size="sm" class="mb-2">Proof of delivery</flux:heading>
                    <a href="{{ route('shipments.proof', $shipment->tracking_number) }}" target="_blank">
                        <img src="{{ route('shipments.proof', $shipment->tracking_number) }}" alt="Proof of delivery photo for {{ $shipment->tracking_number }}" class="max-h-56 w-full rounded-lg object-cover">
                    </a>
                @endif
                @if ($shipment->notes)
                    <flux:separator class="my-3" />
                    <flux:text>{{ $shipment->notes }}</flux:text>
                @endif
            </flux:card>

            <flux:card class="flex flex-col gap-2">
                <flux:select wire:model.live="driverId" label="Driver" description="The courier who collects or delivers it sees it on their phone.">
                    <flux:select.option value="">Not assigned</flux:select.option>
                    @foreach (\App\Models\User::query()->where('is_driver', true)->orderBy('name')->get() as $driver)
                        <flux:select.option :value="$driver->id">{{ $driver->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if (! \App\Models\User::query()->where('is_driver', true)->exists())
                    <flux:text class="text-xs">No drivers yet. <flux:link :href="route('admin.drivers')" wire:navigate>Add one</flux:link>.</flux:text>
                @endif
            </flux:card>

            @unless ($shipment->isPaid())
                <flux:card class="flex flex-col gap-3">
                    <flux:heading>Record a payment</flux:heading>
                    <flux:text>For money taken by the courier or by transfer. Card payments made online are recorded automatically.</flux:text>
                    <form wire:submit="markPaid" class="flex flex-col gap-3">
                        <flux:select wire:model="paymentMethod" label="How was it paid?">
                            @foreach (Shipment::PAYMENT_METHODS as $value => $label)
                                @continue($value === 'card')
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="paymentReference" label="Reference (optional)" placeholder="Receipt or transfer number" />
                        <div><flux:button type="submit" size="sm" variant="primary" icon="check">Mark {{ Money::format($shipment->price_cents) }} as paid</flux:button></div>
                    </form>
                </flux:card>
            @endunless

            @if ($shipment->items->isNotEmpty())
                <flux:card>
                    <flux:heading class="mb-3">Customs items · {{ $shipment->exportReasonLabel() }}</flux:heading>
                    <ul class="flex flex-col gap-2 text-sm">
                        @foreach ($shipment->items as $item)
                            <li class="flex justify-between gap-3">
                                <span>{{ $item->quantity }} × {{ $item->description }} <span class="text-zinc-500">({{ $item->originName() }}{{ $item->hs_code ? ', HS '.$item->hs_code : '' }})</span></span>
                                <span>{{ Money::format($item->totalValueCents()) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif

            <div>
                <flux:button variant="danger" size="sm" icon="trash" wire:click="deleteShipment" wire:confirm="Delete shipment {{ $shipment->tracking_number }} and its whole timeline? This cannot be undone.">Delete shipment</flux:button>
            </div>
        </div>
    </div>
</div>
