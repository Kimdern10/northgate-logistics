@props(['items', 'total'])

{{-- Customs lines for a parcel crossing a border. Used inside Livewire forms that use HasCustomsItems. --}}
<div class="customs">
    <p class="hint">Customs officers need to know what is inside and what it is worth. This is printed on the commercial invoice.</p>

    <label class="field @error('export_reason') field--invalid @enderror">
        <span>Reason for sending</span>
        <select wire:model="export_reason">
            @foreach (\App\Models\Shipment::EXPORT_REASONS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('export_reason') <small class="error">{{ $message }}</small> @enderror
    </label>

    <div class="customs-lines">
        @foreach ($items as $index => $item)
            <div class="customs-line" wire:key="item-{{ $index }}">
                <label class="field customs-line__desc @error("items.$index.description") field--invalid @enderror">
                    <span>Item {{ $index + 1 }}</span>
                    <input wire:model.blur="items.{{ $index }}.description" placeholder="e.g. Cotton T-shirts">
                    @error("items.$index.description") <small class="error">{{ $message }}</small> @enderror
                </label>
                <label class="field @error("items.$index.quantity") field--invalid @enderror">
                    <span>Qty</span>
                    <input type="number" min="1" inputmode="numeric" wire:model.live.debounce.400ms="items.{{ $index }}.quantity">
                    @error("items.$index.quantity") <small class="error">{{ $message }}</small> @enderror
                </label>
                <label class="field @error("items.$index.unit_value") field--invalid @enderror">
                    <span>Value each ($)</span>
                    <input type="number" min="0" step="0.01" inputmode="decimal" wire:model.live.debounce.400ms="items.{{ $index }}.unit_value">
                    @error("items.$index.unit_value") <small class="error">{{ $message }}</small> @enderror
                </label>
                <label class="field @error("items.$index.origin_country") field--invalid @enderror">
                    <span>Made in</span>
                    <select wire:model="items.{{ $index }}.origin_country">
                        @foreach (\App\Support\Countries::options() as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field @error("items.$index.hs_code") field--invalid @enderror">
                    <span>HS code <em>(optional)</em></span>
                    <input wire:model.blur="items.{{ $index }}.hs_code" placeholder="6109.10" inputmode="decimal">
                    @error("items.$index.hs_code") <small class="error">{{ $message }}</small> @enderror
                </label>
                @if (count($items) > 1)
                    <button type="button" class="customs-remove" wire:click="removeItem({{ $index }})" aria-label="Remove item {{ $index + 1 }}">×</button>
                @endif
            </div>
        @endforeach
    </div>
    @error('items') <small class="error">{{ $message }}</small> @enderror

    <div class="customs-footer">
        @if (count($items) < 20)
            <button type="button" class="btn btn--ghost btn--sm" wire:click="addItem">+ Add another item</button>
        @endif
        <span>Total value for customs: <strong>{{ \App\Support\Money::format($total) }}</strong></span>
    </div>
</div>
