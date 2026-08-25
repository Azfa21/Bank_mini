@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 overflow-x-auto']) }}>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-5 py-3 {{ (($header['align'] ?? 'left') === 'right') ? 'text-right' : '' }}">{{ $header['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
