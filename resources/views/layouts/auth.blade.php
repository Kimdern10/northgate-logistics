@props(['title' => null, 'side' => 'login'])

<x-layouts::auth.simple :title="$title" :side="$side">
    {{ $slot }}
</x-layouts::auth.simple>
