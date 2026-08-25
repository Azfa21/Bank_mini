@props(['variant' => 'blue'])

@php
    $colors = [
        'blue' => 'bg-blue-600 hover:bg-blue-700',
        'green' => 'bg-green-600 hover:bg-green-700',
        'red' => 'bg-red-600 hover:bg-red-700',
    ];
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors '.($colors[$variant] ?? $colors['blue'])]) }}>
    {{ $slot }}
</button>
