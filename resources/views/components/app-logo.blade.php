@props([
    'sidebar' => false,
])

@php($name = \App\Models\Setting::get('business_name'))

@if($sidebar)
    <flux:sidebar.brand :name="$name" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-site-logo class="size-8" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$name" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-site-logo class="size-8" />
        </x-slot>
    </flux:brand>
@endif
