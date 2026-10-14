@props(['for' => null, 'size' => 'sm', 'flush' => false])

@php
    $sizes = [
        'xs' => 'block text-[13px] font-semibold text-stone-700 mb-1.5',
        'sm' => 'block text-sm font-semibold text-stone-800 mb-1.5',
    ];

    $base = $sizes[$size] ?? $sizes['sm'];

    if ($flush) {
        $base = str_replace(' mb-1.5', '', $base);
    }
@endphp

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $base]) }}>
    {{ $slot }}
</label>
