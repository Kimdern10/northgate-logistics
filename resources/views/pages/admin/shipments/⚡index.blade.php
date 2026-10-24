<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Shipments')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    /** A status value, "active" for everything still moving, or empty for all. */
    #[Url]
    public string $status = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function shipments()
    {
        return Shipment::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('tracking_number', 'like', '%'.Shipment::normaliseTrackingNumber($this->search).'%')
                    ->orWhere('sender_name', 'like', $term)
                    ->orWhere('receiver_name', 'like', $term)
                    ->orWhere('sender_email', 'like', $term)
                    ->orWhere('sender_phone', 'like', $term)
                    ->orWhere('receiver_city', 'like', $term));
            })
            ->when($this->status === 'active', fn ($query) => $query->whereIn('status', ShipmentStatus::active()))
            ->when(ShipmentStatus::tryFrom($this->status), fn ($query, ShipmentStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Shipments & pickups</flux:heading>
            <flux:text>Online pickup bookings show as "Pickup booked" until the courier collects them.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.shipments.create')" wire:navigate>New shipment</flux:button>
    </div>

    <div class="flex flex-wrap gap-3">
        <div class="w-full sm:w-72"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tracking number, name, phone, city" /></div>
        <div class="w-full sm:w-56">
            <flux:select wire:model.live="status">
                <flux:select.option value="">All shipments</flux:select.option>
                <flux:select.option value="active">Still moving</flux:select.option>
                @foreach (ShipmentStatus::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->shipments">
        <flux:table.columns>
            <flux:table.column>Tracking no.</flux:table.column>
            <flux:table.column>Sender</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Service</flux:table.column>
            <flux:table.column>Price</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->shipments as $shipment)
                <flux:table.row :key="$shipment->id">
                    <flux:table.cell>
                        <flux:link :href="route('admin.shipments.show', $shipment)" wire:navigate>{{ $shipment->tracking_number }}</flux:link>
                        @if ($shipment->isFromWebsite())<div class="text-xs text-zinc-500">Booked online</div>@endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $shipment->sender_name }}<div class="text-xs text-zinc-500">{{ $shipment->sender_phone }}</div></flux:table.cell>
                    <flux:table.cell>{{ $shipment->sender_city }} → {{ $shipment->receiver_city }}<div class="text-xs text-zinc-500">{{ $shipment->sender_country }} → {{ $shipment->receiver_country }}</div></flux:table.cell>
                    <flux:table.cell>{{ $shipment->service_level->label() }}</flux:table.cell>
                    <flux:table.cell>{{ Money::format($shipment->price_cents) }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $shipment->created_at->format('j M Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="7">No shipments match.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
