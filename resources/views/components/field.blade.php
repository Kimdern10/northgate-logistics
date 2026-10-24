@props([
    'label',
    'model',
    'type' => 'text',
    'hint' => null,
    'live' => false,
])

<label class="field @error($model) field--invalid @enderror">
    <span>{{ $label }}</span>
    <input
        type="{{ $type }}"
        @if ($type === 'number') inputmode="decimal" @endif
        @if ($live) wire:model.live.debounce.400ms="{{ $model }}" @else wire:model.blur="{{ $model }}" @endif
        {{ $attributes }}
        @error($model) aria-invalid="true" @enderror
    >
    @error($model)
        <small class="error">{{ $message }}</small>
    @else
        @if ($hint)
            <small class="hint">{{ $hint }}</small>
        @endif
    @enderror
</label>
