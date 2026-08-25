@props(['message' => null])

@if ($message)
    <p {{ $attributes->merge(['class' => 'text-xs text-red-600 mt-1']) }}>{{ $message }}</p>
@endif
