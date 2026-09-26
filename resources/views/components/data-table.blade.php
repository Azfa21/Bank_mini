@props(['headers' => [], 'caption' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-stone-200 overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            @if ($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif
            <thead class="bg-stone-100 text-left">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="px-5 py-3 text-[13px] font-semibold text-stone-700 {{ (($header['align'] ?? 'left') === 'right') ? 'text-right tnum' : '' }}">{{ $header['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
