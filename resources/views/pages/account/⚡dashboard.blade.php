<?php

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My account')] class extends Component {
    public string $trackingNumber = '';

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $user = auth()->user();

        return [
            'incoming' => $user->visibleShipments('receiving')->whereNotIn('status', [ShipmentStatus::Delivered, ShipmentStatus::Cancelled])->count(),
            'outgoing' => $user->visibleShipments('sending')->whereNotIn('status', [ShipmentStatus::Delivered, ShipmentStatus::Cancelled])->count(),
            'delivered' => $user->visibleShipments()->where('status', ShipmentStatus::Delivered)->where('delivered_at', '>=', now()->subDays(30))->count(),
            'exceptions' => $user->visibleShipments()->where('status', ShipmentStatus::OnHold)->count(),
        ];
    }

    #[Computed]
    public function recent()
    {
        return auth()->user()->visibleShipments()->latest('updated_at')->limit(6)->get();
    }

    /**
     * Add someone else's shipment to this account by its tracking number.
     */
    public function watch(): void
    {
        $this->validate(['trackingNumber' => ['required', 'string', 'max:30']], [], ['trackingNumber' => 'tracking number']);

        $shipment = Shipment::findByTrackingNumber($this->trackingNumber);

        if (! $shipment) {
            $this->addError('trackingNumber', 'We could not find that tracking number.');

            return;
        }

        auth()->user()->watchedShipments()->syncWithoutDetaching([$shipment->id]);

        $this->trackingNumber = '';
        unset($this->recent, $this->counts);

        Flux::toast(variant: 'success', text: "{$shipment->tracking_number} is now in your shipments. We'll email you each update.");
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Hello, {{ str(auth()->user()->name)->before(' ') }}</flux:heading>
            <flux:text>Everything you send and receive with us, in one place.</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button variant="primary" icon="truck" :href="route('ship')">Ship a parcel</flux:button>
            <flux:button icon="calculator" :href="route('quote')">Get a quote</flux:button>
        </div>
    </div>

    @if (auth()->user()->is_admin)
        <flux:callout icon="shield-check" color="orange" heading="You are staff.">
            <flux:callout.text>Open the <flux:link :href="route('admin.dashboard')" wire:navigate>admin dashboard</flux:link> to manage shipments.</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Coming to you', $this->counts['incoming'], 'inbox-arrow-down', route('account.shipments', ['view' => 'receiving'])],
            ['On the way from you', $this->counts['outgoing'], 'paper-airplane', route('account.shipments', ['view' => 'sending'])],
            ['Delivered (30 days)', $this->counts['delivered'], 'check-circle', route('account.shipments', ['status' => 'delivered'])],
            ['Need attention', $this->counts['exceptions'], 'exclamation-triangle', route('account.shipments', ['status' => 'on_hold'])],
        ] as [$label, $value, $icon, $href])
            <a href="{{ $href }}" wire:navigate>
                <flux:card class="flex items-center gap-4 transition hover:border-orange-400">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        <flux:icon :name="$icon" />
                    </div>
                    <div>
                        <flux:text>{{ $label }}</flux:text>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                </flux:card>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <flux:card class="flex flex-col gap-3 xl:col-span-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Latest activity</flux:heading>
                <flux:link :href="route('account.shipments')" wire:navigate>See all</flux:link>
            </div>
            @forelse ($this->recent as $shipment)
                <a href="{{ route('account.shipments.show', $shipment) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $shipment->tracking_number }} <span class="font-normal text-zinc-500">· {{ $shipment->contents }}</span></div>
                        <div class="truncate text-sm text-zinc-500">{{ $shipment->sender_city }} → {{ $shipment->receiver_city }} · updated {{ $shipment->updated_at->diffForHumans() }}</div>
                    </div>
                    <flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
                </a>
            @empty
                <flux:text>No shipments yet. Book a pickup, or add a tracking number on the right to follow a parcel someone is sending you.</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Add a shipment</flux:heading>
            <flux:text>Expecting a parcel? Enter its tracking number to keep it in your account and get an email at every update.</flux:text>
            <form wire:submit="watch" class="flex flex-col gap-3">
                <flux:input wire:model="trackingNumber" placeholder="e.g. NG4827301956" />
                <flux:button type="submit" variant="primary" icon="plus">Add to my shipments</flux:button>
            </form>
            <flux:separator />
            <flux:text>Shipments sent from or to <strong>{{ auth()->user()->email }}</strong> show up here by themselves.</flux:text>
        </flux:card>
    </div>
</div>
