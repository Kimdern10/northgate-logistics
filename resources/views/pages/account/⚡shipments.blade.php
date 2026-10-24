<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My shipments')] class extends Component {
    use WithPagination;

    /** all, sending, receiving or watching */
    #[Url]
    public string $view = 'all';

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function shipments()
    {
        $view = in_array($this->view, ['all', 'sending', 'receiving', 'watching'], true) ? $this->view : 'all';

        return auth()->user()->visibleShipments($view)
            ->when(ShipmentStatus::tryFrom($this->status), fn ($query, ShipmentStatus $status) => $query->where('status', $status))
            ->when($this->search !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('tracking_number', 'like', '%'.Shipment::normaliseTrackingNumber($this->search).'%')
                    ->orWhere('receiver_name', 'like', $term)
                    ->orWhere('sender_name', 'like', $term)
                    ->orWhere('contents', 'like', $term)
                    ->orWhere('receiver_city', 'like', $term));
            })
            ->latest()
            ->paginate(15);
    }

    public function direction(Shipment $shipment): string
    {
        return auth()->user()->sentShipment($shipment) ? 'Sending' : 'Receiving';
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">My shipments</flux:heading>
            <flux:text>Parcels you sent, parcels coming to you, and ones you added by tracking number.</flux:text>
        </div>
        <flux:button variant="primary" icon="truck" :href="route('ship')">Ship a parcel</flux:button>
    </div>

    <flux:radio.group wire:model.live="view" variant="segmented" class="max-w-xl">
        <flux:radio value="all" label="All" />
        <flux:radio value="sending" label="Sending" />
        <flux:radio value="receiving" label="Receiving" />
        <flux:radio value="watching" label="Added by me" />
    </flux:radio.group>

    <div class="flex flex-wrap gap-3">
        <div class="w-full sm:w-72"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Tracking number, name, city" /></div>
        <div class="w-full sm:w-52">
            <flux:select wire:model.live="status">
                <flux:select.option value="">Any status</flux:select.option>
                @foreach (ShipmentStatus::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->shipments">
        <flux:table.columns>
            <flux:table.column>Tracking no.</flux:table.column>
            <flux:table.column>Route</flux:table.column>
            <flux:table.column>Contents</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Expected / delivered</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->shipments as $shipment)
                <flux:table.row :key="$shipment->id">
                    <flux:table.cell>
                        <flux:link :href="route('account.shipments.show', $shipment)" wire:navigate>{{ $shipment->tracking_number }}</flux:link>
                        <div class="text-xs text-zinc-500">{{ $this->direction($shipment) }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $shipment->sender_city }} → {{ $shipment->receiver_city }}<div class="text-xs text-zinc-500">{{ $shipment->service_level->label() }}</div></flux:table.cell>
                    <flux:table.cell class="max-w-48 truncate">{{ $shipment->contents }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        @if ($shipment->delivered_at)
                            {{ $shipment->delivered_at->format('j M Y') }}
                        @else
                            {{ $shipment->estimated_delivery_date?->format('j M Y') ?? '—' }}
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5">No shipments here yet.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
