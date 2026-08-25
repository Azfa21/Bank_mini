@props(['for' => null, 'size' => 'sm'])

@php
    $sizes = [
        'xs' => 'block text-xs font-medium text-gray-600 mb-1',
        'sm' => 'block text-sm font-medium text-gray-700 mb-1',
    ];
@endphp

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $sizes[$size] ?? $sizes['sm']]) }}>
    {{ $slot }}
</label>
