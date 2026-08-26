@php
    $customer = $customer ?? $account->customer;
    $qrValue = $customer->qr_code_value ?? ('BMS:'.$account->account_number);
    $qrUrl = route('qr.show', ['data' => $qrValue, 'size' => 160]);
    $qrDownloadUrl = route('qr.download', ['data' => $qrValue, 'size' => 400]);
    $cardPrintUrl = $cardPrintUrl ?? null;
@endphp

<div class="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg max-w-md">
    <div class="flex items-start justify-between">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold tracking-wide">BANK MINI SEKOLAH</span>
            </div>
            <p class="text-[10px] text-blue-200 mt-1">Kartu Nasabah Virtual — ID Tabungan</p>
        </div>
        <img src="{{ $qrUrl }}" alt="QR Code {{ $customer->full_name }}" class="w-24 h-24 bg-white rounded-lg p-1">
    </div>

    <div class="mt-6">
        <p class="text-[10px] uppercase tracking-wider text-blue-200">Pemegang Rekening</p>
        <p class="text-lg font-semibold">{{ $customer->full_name }}</p>
    </div>

    <div class="mt-4 flex items-end justify-between">
        <div class="space-y-2">
            <div>
                <p class="text-[10px] uppercase tracking-wider text-blue-200">No. Rekening</p>
                <p class="font-mono text-sm">{{ $account->account_number }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-wider text-blue-200">Kelas / No. Siswa</p>
                <p class="font-mono text-sm">{{ $customer->class_name }} / {{ $customer->student_number }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-[10px] uppercase tracking-wider text-blue-200">Saldo</p>
            <p class="font-mono text-sm">Rp {{ number_format($account->balance, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<div class="mt-3 flex items-center gap-2">
    <a href="{{ $qrDownloadUrl }}"
        class="inline-flex items-center gap-1.5 text-xs bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 12V4m0 0l-4 4m4-4l4 4"/>
        </svg>
        Unduh QR
    </a>
    @if ($cardPrintUrl)
        <a href="{{ $cardPrintUrl }}"
            class="inline-flex items-center gap-1.5 text-xs bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-12-4h.01M18 14h.01M6 21h12v-6H6v6z"/>
            </svg>
            Cetak Kartu
        </a>
    @else
        <button onclick="window.print()"
            class="inline-flex items-center gap-1.5 text-xs bg-white border border-gray-300 rounded-lg px-3 py-1.5 text-gray-700 hover:bg-gray-50 transition-colors print:hidden">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-12-4h.01M18 14h.01M6 21h12v-6H6v6z"/>
            </svg>
            Cetak Kartu
        </button>
    @endif
</div>