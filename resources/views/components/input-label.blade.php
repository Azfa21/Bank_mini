@props(['for' => null, 'size' => 'sm'])

@php
    $sizes = [
        'xs' => 'block text-[13px] font-semibold text-stone-700 mb-1.5',
        'sm' => 'block text-sm font-semibold text-stone-800 mb-1.5',
    ];
@endphp

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $sizes[$size] ?? $sizes['sm']]) }}>
    {{ $slot }}
</label>
