@props(['href'])

<a {{ $attributes->merge(['href' => $href, 'class' => 'text-sm text-gray-600 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50']) }}>
    {{ $slot }}
</a>
