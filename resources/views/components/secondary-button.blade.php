@props(['href' => '#'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors']) }}>
    {{ $slot }}
</a>
