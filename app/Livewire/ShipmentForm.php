<?php

namespace App\Livewire;

use App\Models\Shipment;
use App\Support\Countries;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Base for the Livewire forms that book a shipment (the public pickup form and the admin form):
 * the route's two countries and the customs lines needed when a parcel crosses a border.
 */
abstract class ShipmentForm extends Component
{
    public string $sender_country = 'US';

    public string $receiver_country = 'GB';

    /**
     * @var list<array{description: string, quantity: string, unit_value: string, origin_country: string, hs_code: string}>
     */
    public array $items = [];

    public string $export_reason = 'sale';

    public function crossesBorder(): bool
    {
        return strtoupper($this->sender_country) !== strtoupper($this->receiver_country);
    }

    public function addItem(): void
    {
        if (count($this->items) < 20) {
            $this->items[] = ['description' => '', 'quantity' => '1', 'unit_value' => '', 'origin_country' => strtoupper($this->sender_country), 'hs_code' => ''];
        }
    }

    public function removeItem(int $index): void
    {
        $this->items = array_values(array_filter($this->items, fn (int $key): bool => $key !== $index, ARRAY_FILTER_USE_KEY));
    }

    /**
     * Start with one empty line when the parcel crosses a border, so the section never looks empty.
     */
    protected function ensureCustomsLine(): void
    {
        if ($this->crossesBorder() && $this->items === []) {
            $this->addItem();
        }
    }

    public function customsTotalCents(): int
    {
        return (int) collect($this->items)->sum(fn (array $item): int => is_numeric($item['quantity']) && is_numeric($item['unit_value'])
            ? (int) $item['quantity'] * Money::toCents($item['unit_value'])
            : 0);
    }

    /**
     * @return array<string, mixed>
     */
    protected function customsRules(): array
    {
        if (! $this->crossesBorder()) {
            return [];
        }

        return [
            'export_reason' => ['required', Rule::in(array_keys(Shipment::EXPORT_REASONS))],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.description' => ['required', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.unit_value' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'items.*.origin_country' => ['required', Rule::in(Countries::codes())],
            'items.*.hs_code' => ['nullable', 'regex:/^[0-9][0-9.]{3,13}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function customsAttributes(): array
    {
        return [
            'items' => 'customs items',
            'items.*.description' => 'item description',
            'items.*.quantity' => 'quantity',
            'items.*.unit_value' => 'value',
            'items.*.origin_country' => 'country of origin',
            'items.*.hs_code' => 'HS code',
            'export_reason' => 'reason for export',
        ];
    }
}
