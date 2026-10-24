@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1">
    <flux:heading size="xl" level="1" class="!text-2xl !font-bold">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
