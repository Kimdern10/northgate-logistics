@props([
    'target',
    'title',
    'text' => null,
])

{{-- Covers the page while a slow action (such as sending emails) runs, so the visitor knows it is working. --}}
<div class="busy-overlay" wire:loading.flex wire:target="{{ $target }}" role="status" aria-live="polite">
    <div class="busy-card">
        <span class="busy-spinner" aria-hidden="true"><x-icon name="truck" /></span>
        <strong>{{ $title }}</strong>
        @if ($text)
            <span>{{ $text }}</span>
        @endif
    </div>
</div>
