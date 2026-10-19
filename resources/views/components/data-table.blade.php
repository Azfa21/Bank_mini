@props(['headers' => [], 'caption' => null])

{{-- Alasan: header dengan 'sticky' => true ditempel di kanan (mis. kolom Aksi pada tabel
     lebar) supaya tombolnya tetap terlihat saat tabel digeser ke samping. Sel di <tbody>
     memakai kelas yang sama dengan latar barisnya. --}}
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-stone-200 overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            @if ($caption)
                <caption class="sr-only">{{ $caption }}</caption>
            @endif
            <thead class="bg-stone-100 text-left">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="px-5 py-3 text-[13px] font-semibold text-stone-700 {{ (($header['align'] ?? 'left') === 'right') ? 'text-right tnum' : '' }} {{ ($header['sticky'] ?? false) ? 'sticky right-0 bg-stone-100 border-l border-stone-200' : '' }}">{{ $header['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                {{ $slot }}
            </tbody>
            @if (isset($footer) && $footer->isNotEmpty())
                <tfoot class="bg-stone-50 border-t border-stone-200">
                    {{ $footer }}
                </tfoot>
            @endif
        </table>
    </div>
</div>
