@props([
    'model',
    'saveModel',
    'addresses',
])

<div class="address-picker">
    @if ($addresses->isNotEmpty())
        <label class="field">
            <span>From my address book</span>
            <select wire:model.live="{{ $model }}">
                <option value="">Type a new address below…</option>
                @foreach ($addresses as $saved)
                    <option value="{{ $saved->id }}">{{ $saved->summary() }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label class="check check--inline">
        <input type="checkbox" wire:model="{{ $saveModel }}">
        <span>Save this address to my address book</span>
    </label>
</div>
