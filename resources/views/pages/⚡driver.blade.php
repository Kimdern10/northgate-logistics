<?php

use App\Actions\RecordTrackingUpdate;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Driver')] class extends Component {
    use WithFileUploads;

    /** pickups or deliveries */
    #[Url(except: 'pickups')]
    public string $tab = 'pickups';

    /** A tracking number typed or scanned from a waybill barcode. */
    public string $search = '';

    /** The shipment whose "Delivered" form is open. */
    public ?int $deliveringId = null;

    public string $receivedBy = '';

    public $deliveryPhoto = null;

    /** The shipment whose "Couldn't deliver" choices are open. */
    public ?int $failingId = null;

    /**
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function pickups(): Collection
    {
        return auth()->user()->assignedShipments()->where('status', ShipmentStatus::Booked)->orderBy('pickup_date')->get();
    }

    /**
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function deliveries(): Collection
    {
        return auth()->user()->assignedShipments()->where('status', ShipmentStatus::OutForDelivery)->orderBy('receiver_city')->get();
    }

    /**
     * A parcel found by scanning or typing its tracking number (any parcel, so drivers can work at a hub).
     */
    #[Computed]
    public function found(): ?Shipment
    {
        return strlen(Shipment::normaliseTrackingNumber($this->search)) >= 12 ? Shipment::findByTrackingNumber($this->search) : null;
    }

    public function pickedUp(int $id, RecordTrackingUpdate $recordUpdate): void
    {
        $shipment = $this->shipmentForAction($id, [ShipmentStatus::Booked]);
        $recordUpdate->handle($shipment, ShipmentStatus::PickedUp, $shipment->origin(), 'Collected by our courier '.auth()->user()->name.'.');

        $this->done("{$shipment->tracking_number} picked up.");
    }

    public function outForDelivery(int $id, RecordTrackingUpdate $recordUpdate): void
    {
        $shipment = $this->shipmentForAction($id, [ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::AtHub, ShipmentStatus::CustomsClearance, ShipmentStatus::OnHold]);
        $recordUpdate->handle($shipment, ShipmentStatus::OutForDelivery, $shipment->destination(), 'With our delivery driver. Expect a call before arrival.');

        $this->tab = 'deliveries';
        $this->done("{$shipment->tracking_number} is in your deliveries.");
    }

    public function openDelivered(int $id): void
    {
        $this->reset(['receivedBy', 'deliveryPhoto', 'failingId']);
        $this->resetValidation();
        $this->deliveringId = $id;
    }

    public function delivered(RecordTrackingUpdate $recordUpdate): void
    {
        $shipment = $this->shipmentForAction((int) $this->deliveringId, [ShipmentStatus::OutForDelivery]);

        $this->validate([
            'receivedBy' => ['required', 'string', 'max:120'],
            'deliveryPhoto' => ['nullable', 'image', 'max:10240'],
        ], [], ['receivedBy' => 'who received it', 'deliveryPhoto' => 'photo']);

        $recordUpdate->handle(
            $shipment,
            ShipmentStatus::Delivered,
            $shipment->destination(),
            'Delivered by '.auth()->user()->name.'.',
            receivedBy: $this->receivedBy,
            deliveryPhoto: $this->deliveryPhoto,
        );

        $this->reset(['deliveringId', 'receivedBy', 'deliveryPhoto']);
        $this->done("{$shipment->tracking_number} delivered.");
    }

    public function openFailed(int $id): void
    {
        $this->reset(['deliveringId']);
        $this->failingId = $id;
    }

    public function failed(string $reason, RecordTrackingUpdate $recordUpdate): void
    {
        abort_unless(array_key_exists($reason, Shipment::FAILED_DELIVERY_REASONS), 422);
        $shipment = $this->shipmentForAction((int) $this->failingId, [ShipmentStatus::OutForDelivery]);

        $recordUpdate->handle($shipment, ShipmentStatus::OnHold, $shipment->destination(), 'Delivery attempt failed: '.Shipment::FAILED_DELIVERY_REASONS[$reason].'. We will contact you to arrange a new delivery.');

        $this->reset(['failingId']);
        $this->done("{$shipment->tracking_number} marked as not delivered.");
    }

    public function cancel(): void
    {
        $this->reset(['deliveringId', 'failingId', 'receivedBy', 'deliveryPhoto']);
        $this->resetValidation();
    }

    /**
     * Drivers act on their own jobs, or on a parcel they scanned (which then becomes theirs).
     *
     * @param  list<ShipmentStatus>  $allowed
     */
    private function shipmentForAction(int $id, array $allowed): Shipment
    {
        $shipment = Shipment::query()->findOrFail($id);
        $user = auth()->user();

        abort_unless($user->is_admin || $shipment->driver_id === $user->id || $shipment->driver_id === null || $this->found?->is($shipment), 403);
        abort_unless(in_array($shipment->status, $allowed, true), 422, 'This parcel is not at that step any more.');

        if ($shipment->driver_id !== $user->id) {
            $shipment->forceFill(['driver_id' => $user->id])->save();
        }

        return $shipment;
    }

    private function done(string $message): void
    {
        unset($this->pickups, $this->deliveries, $this->found);
        $this->search = '';
        Flux::toast(variant: 'success', text: $message);
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
    <div>
        <flux:heading size="xl">Hi {{ str(auth()->user()->name)->before(' ') }}</flux:heading>
        <flux:text>{{ now()->format('l j F') }} · {{ $this->pickups->count() }} {{ str('pickup')->plural($this->pickups->count()) }}, {{ $this->deliveries->count() }} {{ str('delivery')->plural($this->deliveries->count()) }}</flux:text>
    </div>

    {{-- Find a parcel: scan the waybill barcode with the camera, or type the number --}}
    <flux:card class="flex flex-col gap-3"
        x-data="{
            supported: 'BarcodeDetector' in window && !! navigator.mediaDevices,
            scanning: false,
            stream: null,
            async start() {
                try {
                    const detector = new BarcodeDetector({ formats: ['code_128'] });
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    this.$refs.video.srcObject = this.stream;
                    await this.$refs.video.play();
                    this.scanning = true;
                    const look = async () => {
                        if (! this.scanning) return;
                        const codes = await detector.detect(this.$refs.video).catch(() => []);
                        if (codes.length) { this.stop(); $wire.set('search', codes[0].rawValue); return; }
                        requestAnimationFrame(look);
                    };
                    look();
                } catch (error) {
                    this.stop();
                    alert('The camera could not start. Type the tracking number instead.');
                }
            },
            stop() {
                this.scanning = false;
                this.stream?.getTracks().forEach((track) => track.stop());
                this.stream = null;
            },
        }"
        x-on:livewire:navigating.window="stop()">
        <div class="flex gap-2">
            <div class="flex-1"><flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Tracking number, e.g. NT4827301956" clearable /></div>
            <flux:button x-show="supported && ! scanning" x-on:click="start()" icon="camera" variant="primary">Scan</flux:button>
            <flux:button x-show="scanning" x-on:click="stop()" icon="x-mark">Stop</flux:button>
        </div>
        <video x-ref="video" x-show="scanning" x-cloak playsinline muted class="aspect-video w-full rounded-lg bg-black object-cover"></video>

        @if ($found = $this->found)
            @include('partials.driver-job', ['shipment' => $found, 'mode' => 'found'])
        @elseif (strlen(\App\Models\Shipment::normaliseTrackingNumber($search)) >= 12)
            <flux:text>No parcel with that tracking number.</flux:text>
        @endif
    </flux:card>

    <flux:radio.group wire:model.live="tab" variant="segmented">
        <flux:radio value="pickups" label="Pickups ({{ $this->pickups->count() }})" />
        <flux:radio value="deliveries" label="Deliveries ({{ $this->deliveries->count() }})" />
    </flux:radio.group>

    <div class="flex flex-col gap-4">
        @forelse ($tab === 'deliveries' ? $this->deliveries : $this->pickups as $shipment)
            @include('partials.driver-job', ['shipment' => $shipment, 'mode' => $tab])
        @empty
            <flux:card><flux:text>No {{ $tab }} assigned to you right now. Scan a waybill above to take a parcel.</flux:text></flux:card>
        @endforelse
    </div>
</div>
