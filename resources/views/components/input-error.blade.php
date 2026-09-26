@props(['message' => null])

@if ($message)
    <p role="alert" {{ $attributes->merge(['class' => 'text-[13px] font-medium text-red-800 mt-1.5']) }}>Periksa: {{ $message }}</p>
@endif
