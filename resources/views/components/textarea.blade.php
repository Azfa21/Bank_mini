@props(['name' => null])

<textarea @if ($name) name="{{ $name }}" @endif {{ $attributes->merge(['class' => 'w-full min-h-[96px] px-4 py-3 border border-stone-300 rounded-xl text-[15px] text-stone-900 placeholder:text-stone-500 focus:outline-none focus:ring-2 focus:ring-brand-800 focus:border-brand-800']) }}>{{ old($name) }}</textarea>
