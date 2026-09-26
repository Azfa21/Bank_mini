@props(['variant' => 'primary'])

@php
    // Alasan: satu warna utama hijau tabungan, varian lain hanya untuk makna (bahaya) atau netral.
    $colors = [
        'primary' => 'bg-brand-800 hover:bg-brand-900 text-white',
        'blue' => 'bg-brand-800 hover:bg-brand-900 text-white',
        'green' => 'bg-brand-800 hover:bg-brand-900 text-white',
        'danger' => 'bg-red-700 hover:bg-red-800 text-white',
        'red' => 'bg-red-700 hover:bg-red-800 text-white',
        'dark' => 'bg-stone-900 hover:bg-stone-800 text-white',
    ];
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 min-h-[48px] px-5 text-[15px] font-semibold rounded-xl transition-colors disabled:opacity-60 disabled:cursor-not-allowed '.($colors[$variant] ?? $colors['primary'])]) }}>
    {{ $slot }}
</button>
