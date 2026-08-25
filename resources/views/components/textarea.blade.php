@props(['name' => null])

<textarea @if ($name) name="{{ $name }}" @endif {{ $attributes->merge(['class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500']) }}>{{ old($name) }}</textarea>
