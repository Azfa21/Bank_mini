@php
    $customer = $customer ?? $account->saver;
    $qrValue = $customer->qr_code_value ?? ('BMS:'.$account->account_number);
    $qrUrl = route('qr.show', ['data' => $qrValue, 'size' => 160]);
    $qrDownloadUrl = route('qr.download', ['data' => $qrValue, 'size' => 400]);
    $cardPrintUrl = $cardPrintUrl ?? null;
    $bankName = \Illuminate\Support\Str::upper(\App\Models\SystemConfig::value('bank_name', 'Bank Mini Sekolah'));
@endphp

<div class="bg-brand-900 rounded-2xl p-6 text-white max-w-md" style="box-shadow: 0 1px 2px rgb(28 25 23 / 0.2);">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-white/15 rounded-lg flex items-center justify-center" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                    </svg>
                </div>
                <span class="text-sm font-bold">{{ $bankName }}</span>
            </div>
            <p class="text-xs text-brand-100 mt-1">Kartu tabungan nasabah</p>
        </div>
        <img src="{{ $qrUrl }}" alt="QR Code {{ $customer->full_name }}" class="w-24 h-24 bg-white rounded-xl p-1">
    </div>

    <div class="mt-6">
        <p class="text-xs font-medium text-brand-100">Pemegang rekening</p>
        <p class="text-lg font-bold">{{ $customer->full_name }}</p>
    </div>

    <div class="mt-4 flex items-end justify-between gap-4">
        <div class="space-y-2">
            <div>
                <p class="text-xs font-medium text-brand-100">No. rekening</p>
                <p class="tnum text-sm font-semibold">{{ $account->account_number }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-brand-100">Kelas dan no. siswa</p>
                <p class="tnum text-sm">{{ $customer->class_name }} / {{ $customer->student_number }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs font-medium text-brand-100">Saldo</p>
            <p class="tnum text-sm font-bold">Rp {{ number_format($account->balance, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<div class="mt-3 flex flex-wrap items-center gap-2">
    <a href="{{ $qrDownloadUrl }}"
        class="inline-flex items-center justify-center gap-1.5 min-h-[44px] text-[13px] font-semibold bg-white border border-stone-300 rounded-xl px-4 text-stone-700 hover:bg-stone-100 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 12V4m0 0l-4 4m4-4l4 4"/>
        </svg>
        Unduh QR
    </a>
    @if ($cardPrintUrl)
        <a href="{{ $cardPrintUrl }}"
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] text-[13px] font-semibold bg-white border border-stone-300 rounded-xl px-4 text-stone-700 hover:bg-stone-100 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-12-4h.01M18 14h.01M6 21h12v-6H6v6z"/>
            </svg>
            Cetak Kartu
        </a>
    @else
        <button type="button" data-print
            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] text-[13px] font-semibold bg-white border border-stone-300 rounded-xl px-4 text-stone-700 hover:bg-stone-100 transition-colors print:hidden">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-12-4h.01M18 14h.01M6 21h12v-6H6v6z"/>
            </svg>
            Cetak Kartu
        </button>
    @endif
</div>