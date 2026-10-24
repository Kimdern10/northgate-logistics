<?php

use App\Enums\ShipmentStatus;
use App\Models\Enquiry;
use App\Models\Shipment;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'pickups' => Shipment::query()->where('status', ShipmentStatus::Booked)->count(),
            'moving' => Shipment::query()->whereIn('status', ShipmentStatus::active())->count(),
            'deliveredThisMonth' => Shipment::query()->where('status', ShipmentStatus::Delivered)->where('delivered_at', '>=', now()->startOfMonth())->count(),
            'onHold' => Shipment::query()->where('status', ShipmentStatus::OnHold)->count(),
            'newEnquiries' => Enquiry::query()->where('status', 'new')->count(),
            'revenueThisMonth' => (int) Shipment::query()->whereNot('status', ShipmentStatus::Cancelled)->where('created_at', '>=', now()->startOfMonth())->sum('price_cents'),
        ];
    }

    #[Computed]
    public function pickups()
    {
        return Shipment::query()->where('status', ShipmentStatus::Booked)->orderBy('pickup_date')->limit(8)->get();
    }

    #[Computed]
    public function recent()
    {
        return Shipment::query()->latest('updated_at')->limit(8)->get();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text>What needs doing today.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.shipments.create')" wire:navigate>New shipment</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['Pickups to collect', $this->counts['pickups'], 'truck', route('admin.shipments.index', ['status' => 'booked'])],
            ['Shipments on the move', $this->counts['moving'], 'paper-airplane', route('admin.shipments.index', ['status' => 'active'])],
            ['On hold', $this->counts['onHold'], 'exclamation-triangle', route('admin.shipments.index', ['status' => 'on_hold'])],
            ['Delivered this month', $this->counts['deliveredThisMonth'], 'check-circle', route('admin.shipments.index', ['status' => 'delivered'])],
            ['New quote requests', $this->counts['newEnquiries'], 'inbox', route('admin.enquiries')],
            ['Booked value this month', Money::format($this->counts['revenueThisMonth']), 'banknotes', null],
        ] as [$label, $value, $icon, $href])
            <flux:card class="flex items-center gap-4">
                <div class="flex size-11 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                    <flux:icon :name="$icon" />
                </div>
                <div class="min-w-0">
                    <flux:text>{{ $label }}</flux:text>
                    <div class="text-2xl font-semibold">
                        @if ($href)
                            <a href="{{ $href }}" wire:navigate class="hover:underline">{{ $value }}</a>
                        @else
                            {{ $value }}
                        @endif
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Pickups to collect</flux:heading>
            @forelse ($this->pickups as $shipment)
                <a href="{{ route('admin.shipments.show', $shipment) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $shipment->tracking_number }} · {{ $shipment->sender_name }}</div>
                        <div class="truncate text-sm text-zinc-500">{{ $shipment->sender_address }}, {{ $shipment->sender_city }}</div>
                    </div>
                    <flux:badge size="sm" :color="$shipment->pickup_date?->isPast() ? 'red' : 'zinc'">{{ $shipment->pickup_date?->format('D j M') ?? 'No date' }}</flux:badge>
                </a>
            @empty
                <flux:text>No pickups waiting.</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Recently updated</flux:heading>
            @foreach ($this->recent as $shipment)
                <a href="{{ route('admin.shipments.show', $shipment) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $shipment->tracking_number }}</div>
                        <div class="truncate text-sm text-zinc-500">{{ $shipment->sender_city }} → {{ $shipment->receiver_city }}</div>
                    </div>
                    <flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
                </a>
            @endforeach
        </flux:card>
    </div>
</div>
