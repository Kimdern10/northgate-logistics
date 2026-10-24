<?php

use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Setting;
use App\Support\Money;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rates')] class extends Component {
    /**
     * One row per service level and zone, keyed "express_domestic".
     *
     * @var array<string, array{base: string, per_kg: string, min_days: string, max_days: string, active: bool}>
     */
    public array $rates = [];

    public string $fuel_surcharge_percent = '';

    public string $volumetric_divisor = '';

    public function mount(): void
    {
        $saved = Rate::query()->get()->keyBy(fn (Rate $rate): string => $rate->service_level->value.'_'.$rate->zone->value);

        foreach (ServiceLevel::cases() as $level) {
            foreach (Zone::cases() as $zone) {
                $rate = $saved[$level->value.'_'.$zone->value] ?? null;
                $this->rates[$level->value.'_'.$zone->value] = [
                    'base' => $rate ? Money::toDecimal($rate->base_fee_cents) : '',
                    'per_kg' => $rate ? Money::toDecimal($rate->per_kg_cents) : '',
                    'min_days' => (string) ($rate->min_days ?? ''),
                    'max_days' => (string) ($rate->max_days ?? ''),
                    'active' => $rate->is_active ?? false,
                ];
            }
        }

        $this->fuel_surcharge_percent = Setting::get('fuel_surcharge_percent');
        $this->volumetric_divisor = Setting::get('volumetric_divisor');
    }

    public function save(): void
    {
        $this->validate([
            'rates.*.base' => ['required_if_accepted:rates.*.active', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'rates.*.per_kg' => ['required_if_accepted:rates.*.active', 'nullable', 'numeric', 'min:0', 'max:10000'],
            'rates.*.min_days' => ['required_if_accepted:rates.*.active', 'nullable', 'integer', 'min:0', 'max:180'],
            'rates.*.max_days' => ['required_if_accepted:rates.*.active', 'nullable', 'integer', 'min:0', 'max:180', 'gte:rates.*.min_days'],
            'fuel_surcharge_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'volumetric_divisor' => ['required', 'integer', 'min:1000', 'max:10000'],
        ], [
            'rates.*.*.required_if_accepted' => 'Fill this in or switch the rate off.',
            'rates.*.max_days.gte' => 'Must be at least the minimum.',
        ]);

        foreach ($this->rates as $key => $row) {
            [$level, $zone] = explode('_', $key);

            if (! $row['active'] && $row['base'] === '') {
                Rate::query()->where(['service_level' => $level, 'zone' => $zone])->delete();

                continue;
            }

            Rate::query()->updateOrCreate(['service_level' => $level, 'zone' => $zone], [
                'base_fee_cents' => Money::toCents($row['base']),
                'per_kg_cents' => Money::toCents($row['per_kg']),
                'min_days' => (int) $row['min_days'],
                'max_days' => (int) $row['max_days'],
                'is_active' => $row['active'],
            ]);
        }

        Setting::put([
            'fuel_surcharge_percent' => $this->fuel_surcharge_percent,
            'volumetric_divisor' => $this->volumetric_divisor,
        ]);

        Flux::toast(variant: 'success', text: 'Rates saved. The quote calculator uses them straight away.');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Rates</flux:heading>
        <flux:text>Price = base fee + (price per kg × chargeable weight) + fuel surcharge. Chargeable weight is the bigger of the real weight and the size weight, rounded up to the next 0.5 kg.</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        @foreach (ServiceLevel::cases() as $level)
            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $level->label() }}</flux:heading>
                    <flux:text>{{ $level->summary() }}</flux:text>
                </div>
                @foreach (Zone::cases() as $zone)
                    @php($key = $level->value.'_'.$zone->value)
                    <div class="grid items-start gap-3 border-t border-zinc-100 pt-4 sm:grid-cols-6 dark:border-zinc-700" wire:key="rate-{{ $key }}">
                        <div class="sm:col-span-2">
                            <flux:switch wire:model="rates.{{ $key }}.active" :label="$zone->label()" />
                        </div>
                        <flux:input wire:model="rates.{{ $key }}.base" type="number" step="0.01" label="Base fee ($)" size="sm" />
                        <flux:input wire:model="rates.{{ $key }}.per_kg" type="number" step="0.01" label="Per kg ($)" size="sm" />
                        <flux:input wire:model="rates.{{ $key }}.min_days" type="number" label="Min days" size="sm" />
                        <flux:input wire:model="rates.{{ $key }}.max_days" type="number" label="Max days" size="sm" />
                    </div>
                @endforeach
            </flux:card>
        @endforeach

        <flux:card class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="fuel_surcharge_percent" type="number" step="0.1" label="Fuel surcharge (%)" description="Added on top of base fee + weight charge." />
            <flux:input wire:model="volumetric_divisor" type="number" label="Size weight divisor" description="Length × width × height (cm) ÷ this number = size weight in kg. 5000 is the airline standard." />
        </flux:card>

        <div><flux:button type="submit" variant="primary">Save rates</flux:button></div>
    </form>
</div>
