@props(['name' => null, 'block' => true])

<select @if ($name) name="{{ $name }}" @endif {{ $attributes->merge(['class' => ($block ? 'w-full ' : '').'min-h-[48px] px-4 text-[15px] text-stone-900 bg-white border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-800 focus:border-brand-800']) }}>
    {{ $slot }}
</select>
