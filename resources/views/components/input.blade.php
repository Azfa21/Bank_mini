@props(['name' => null, 'type' => 'text', 'value' => null, 'block' => true])

@php
    $id = $attributes->get('id', $name);
    $resolvedValue = $type === 'password'
        ? null
        : ($name !== null ? old($name, $value) : $value);
@endphp

<input type="{{ $type }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($name) name="{{ $name }}" @endif
    @isset($resolvedValue) value="{{ $resolvedValue }}" @endisset
    {{ $attributes->merge(['class' => ($block ? 'w-full ' : '').'px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500']) }}>
