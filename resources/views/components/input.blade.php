@props(['name' => null, 'type' => 'text', 'value' => null, 'block' => true])

@php
    $id = $attributes->get('id', $name);
    $resolvedValue = $type === 'password'
        ? null
        : ($name !== null ? old($name, $value) : $value);
    $describedBy = $attributes->get('aria-describedby');
@endphp

<input type="{{ $type }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    @isset($resolvedValue) value="{{ $resolvedValue }}" @endisset
    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    {{ $attributes->except(['aria-describedby'])->merge(['class' => ($block ? 'w-full ' : '').'min-h-[48px] px-4 text-[15px] text-stone-900 placeholder:text-stone-500 bg-white border border-stone-300 rounded-xl tnum focus:outline-none focus:ring-2 focus:ring-brand-800 focus:border-brand-800 disabled:bg-stone-100 disabled:text-stone-500 read-only:bg-stone-100']) }}>
