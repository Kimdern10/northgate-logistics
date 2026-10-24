<?php

use App\Actions\RecordTrackingUpdate;
use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Services\RateCalculator;
use App\Support\Countries;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public ?Shipment $shipment = null;

    public string $sender_name = '';

    public string $sender_company = '';

    public string $sender_email = '';

    public string $sender_phone = '';

    public string $sender_address = '';

    public string $sender_city = '';

    public string $sender_country = 'NG';

    public string $receiver_name = '';

    public string $receiver_company = '';

    public string $receiver_email = '';

    public string $receiver_phone = '';

    public string $receiver_address = '';

    public string $receiver_city = '';

    public string $receiver_country = 'GB';

    public string $contents = '';

    public string $pieces = '1';

    public string $weight_kg = '';

    public string $length_cm = '';

    public string $width_cm = '';

    public string $height_cm = '';

    public string $declared_value = '0';

    public string $price = '';

    public string $service_level = 'standard';

    public string $pickup_date = '';

    public string $estimated_delivery_date = '';

    public string $notes = '';

    /** Only asked when creating: where the shipment starts. */
    public string $initial_status = 'booked';

    public function mount(?Shipment $shipment = null): void
    {
        if ($shipment?->exists) {
            $this->shipment = $shipment;

            foreach ($this->fieldNames() as $field) {
                $value = $shipment->{$field};
                $this->{$field} = match (true) {
                    $value instanceof \BackedEnum => $value->value,
                    $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                    default => (string) $value,
                };
            }

            $this->declared_value = Money::toDecimal($shipment->declared_value_cents);
            $this->price = Money::toDecimal($shipment->price_cents);
        } else {
            $this->pickup_date = now()->toDateString();
        }
    }

    /**
     * @return list<string>
     */
    private function fieldNames(): array
    {
        return [
            'sender_name', 'sender_company', 'sender_email', 'sender_phone', 'sender_address', 'sender_city', 'sender_country',
            'receiver_name', 'receiver_company', 'receiver_email', 'receiver_phone', 'receiver_address', 'receiver_city', 'receiver_country',
            'contents', 'pieces', 'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'service_level', 'pickup_date', 'estimated_delivery_date', 'notes',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $country = ['required', Rule::in(Countries::codes())];

        return [
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_company' => ['nullable', 'string', 'max:120'],
            'sender_email' => ['nullable', 'email', 'max:190'],
            'sender_phone' => ['required', 'string', 'max:30'],
            'sender_address' => ['required', 'string', 'max:255'],
            'sender_city' => ['required', 'string', 'max:120'],
            'sender_country' => $country,
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_company' => ['nullable', 'string', 'max:120'],
            'receiver_email' => ['nullable', 'email', 'max:190'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'receiver_address' => ['required', 'string', 'max:255'],
            'receiver_city' => ['required', 'string', 'max:120'],
            'receiver_country' => $country,
            'contents' => ['required', 'string', 'max:255'],
            'pieces' => ['required', 'integer', 'min:1', 'max:999'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'length_cm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'width_cm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'service_level' => ['required', Rule::enum(ServiceLevel::class)],
            'pickup_date' => ['nullable', 'date'],
            'estimated_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'initial_status' => ['required', Rule::enum(ShipmentStatus::class)],
        ];
    }

    /**
     * Fill the price and delivery date from the rates table.
     */
    public function calculate(RateCalculator $calculator): void
    {
        $this->validateOnly('weight_kg');

        $quote = $calculator->quote(
            ServiceLevel::from($this->service_level),
            $this->sender_country,
            $this->receiver_country,
            (float) $this->weight_kg,
            ctype_digit($this->length_cm) ? (int) $this->length_cm : null,
            ctype_digit($this->width_cm) ? (int) $this->width_cm : null,
            ctype_digit($this->height_cm) ? (int) $this->height_cm : null,
        );

        if (! $quote) {
            Flux::toast(variant: 'warning', text: 'There is no active rate for this service on this route. Type the price yourself.');

            return;
        }

        $this->price = Money::toDecimal($quote->totalCents());
        $this->estimated_delivery_date = $quote->estimatedDeliveryDate($this->pickup_date ? \Carbon\CarbonImmutable::parse($this->pickup_date) : null)->toDateString();
    }

    public function save(RecordTrackingUpdate $recordUpdate)
    {
        $validated = $this->validate();

        $attributes = collect($validated)
            ->only($this->fieldNames())
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
        $attributes['declared_value_cents'] = Money::toCents($validated['declared_value'] ?? 0);
        $attributes['price_cents'] = Money::toCents($validated['price']);

        if ($this->shipment) {
            $this->shipment->update($attributes);
            Flux::toast(variant: 'success', text: 'Shipment saved.');

            return $this->redirectRoute('admin.shipments.show', $this->shipment, navigate: true);
        }

        $shipment = Shipment::query()->create([...$attributes, 'status' => ShipmentStatus::Booked]);
        $status = ShipmentStatus::from($validated['initial_status']);

        $recordUpdate->handle(
            $shipment,
            $status,
            $shipment->origin(),
            $status === ShipmentStatus::Booked ? 'Shipment created.' : 'Received at our '.$shipment->sender_city.' branch.',
            notify: false,
        );

        Flux::toast(variant: 'success', text: "Shipment {$shipment->tracking_number} created.");

        return $this->redirectRoute('admin.shipments.show', $shipment, navigate: true);
    }

    public function render()
    {
        return $this->view()->title($this->shipment ? 'Edit '.$this->shipment->tracking_number : 'New shipment');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $shipment ? 'Edit '.$shipment->tracking_number : 'New shipment' }}</flux:heading>
        <flux:text>{{ $shipment ? 'Change the details. Add tracking updates on the shipment page.' : 'A tracking number is made for you when you save.' }}</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach (['sender' => 'Sender (pickup)', 'receiver' => 'Receiver (delivery)'] as $prefix => $heading)
                <flux:card class="flex flex-col gap-4">
                    <flux:heading size="lg">{{ $heading }}</flux:heading>
                    <flux:input wire:model="{{ $prefix }}_name" label="Name" />
                    <flux:input wire:model="{{ $prefix }}_company" label="Company (optional)" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="{{ $prefix }}_email" type="email" label="Email (for status emails)" />
                        <flux:input wire:model="{{ $prefix }}_phone" label="Phone" />
                    </div>
                    <flux:input wire:model="{{ $prefix }}_address" label="Address" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="{{ $prefix }}_city" label="City" />
                        <flux:select wire:model="{{ $prefix }}_country" label="Country">
                            @foreach (Countries::options() as $code => $name)
                                <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Parcel & price</flux:heading>
            <flux:input wire:model="contents" label="Contents" />
            <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <flux:input wire:model="pieces" type="number" label="Pieces" />
                <flux:input wire:model="weight_kg" type="number" step="0.01" label="Weight (kg)" />
                <flux:input wire:model="length_cm" type="number" label="Length (cm)" />
                <flux:input wire:model="width_cm" type="number" label="Width (cm)" />
                <flux:input wire:model="height_cm" type="number" label="Height (cm)" />
                <flux:input wire:model="declared_value" type="number" step="0.01" label="Value (USD)" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <flux:select wire:model="service_level" label="Service">
                    @foreach (ServiceLevel::cases() as $level)
                        <flux:select.option :value="$level->value">{{ $level->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="pickup_date" type="date" label="Pickup date" />
                <flux:input wire:model="estimated_delivery_date" type="date" label="Expected delivery" />
                <flux:input wire:model="price" type="number" step="0.01" label="Price (USD)" icon="currency-dollar" />
            </div>
            <div><flux:button size="sm" icon="calculator" wire:click="calculate">Work out price from rates</flux:button></div>
            <flux:textarea wire:model="notes" rows="3" label="Notes (printed on the waybill)" />
            @unless ($shipment)
                <flux:select wire:model="initial_status" label="Start as" description="Pick 'At hub' or 'Picked up' for a parcel dropped off at a branch.">
                    @foreach ([ShipmentStatus::Booked, ShipmentStatus::PickedUp, ShipmentStatus::AtHub] as $status)
                        <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endunless
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">{{ $shipment ? 'Save changes' : 'Create shipment' }}</flux:button>
            <flux:button :href="$shipment ? route('admin.shipments.show', $shipment) : route('admin.shipments.index')" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
