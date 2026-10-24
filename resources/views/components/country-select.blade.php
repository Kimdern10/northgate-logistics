@props([
    'label',
    'model',
    'live' => false,
])

<label class="field @error($model) field--invalid @enderror">
    <span>{{ $label }}</span>
    <select @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif {{ $attributes }}>
        @foreach (\App\Support\Countries::options() as $code => $name)
            <option value="{{ $code }}">{{ $name }}</option>
        @endforeach
    </select>
    @error($model) <small class="error">{{ $message }}</small> @enderror
</label>
