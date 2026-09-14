@extends('layouts.app')

@php
    use App\Models\SystemConfig;
    use App\Support\CashDenomination;
    use App\Support\Terbilang;

    $bankName = SystemConfig::value('bank_name', 'Bank Mini Sekolah');
    $typeLabel = match ($transaction->type) {
        'deposit' => 'Setoran Tunai',
        'withdrawal' => 'Penarikan Tunai',
        'transfer' => 'Transfer Antar Rekening',
        'reversal' => 'Koreksi (Jurnal Pembalik)',
        default => ucfirst($transaction->type),
    };
    $isCredit = $transaction->type === 'deposit'
        || ($transaction->type === 'reversal' && $transaction->reversalOf?->type === 'withdrawal');
    $total = (int) $transaction->amount + (int) $transaction->admin_fee;
@endphp

@section('title', 'Struk Transaksi — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Hasil Transaksi</h1>
            <p class="text-sm text-gray-500 mt-1">Transaksi berhasil diproses dan tercatat final.</p>
        </div>
        <div class="flex gap-2 print:hidden">
            <button onclick="window.print()" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Cetak Struk
            </button>
            <a href="{{ route('teller.customers.show', $transaction->account_id) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                Transaksi Baru
            </a>
            <a href="{{ route('teller.customers.search') }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                Pencarian
            </a>
        </div>
    </div>

    @if ($transaction->isReversed())
        <div class="max-w-md mx-auto mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 print:hidden">
            Transaksi ini telah <strong>dikoreksi</strong> oleh {{ $transaction->reversedBy->full_name ?? 'Supervisor' }}
            {{ $transaction->reversed_at?->format('d/m/Y H:i') }}. Alasan: {{ $transaction->reversal_reason }}
        </div>
    @endif

    <div class="print-sheet max-w-md mx-auto bg-white rounded-xl border border-gray-200 p-6" id="receipt">
        <div class="text-center border-b border-dashed border-gray-300 pb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                </svg>
            </div>
            <p class="font-semibold text-gray-900">{{ $bankName }}</p>
            <p class="text-xs text-gray-500">Struk Transaksi Loket</p>
            @if ($transaction->isReversal())
                <p class="text-xs font-semibold text-red-600 mt-1">JURNAL PEMBALIK</p>
            @endif
        </div>

        <div class="py-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nomor Struk</span>
                <span class="font-mono font-medium">{{ $transaction->receipt_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">No. Referensi Jurnal</span>
                <span class="font-mono">{{ $transaction->journalEntries->first()?->journal_number ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal &amp; Waktu</span>
                <span>{{ $transaction->transaction_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Jenis Transaksi</span>
                <span>{{ $typeLabel }}</span>
            </div>
            @if ($transaction->reversalOf)
                <div class="flex justify-between">
                    <span class="text-gray-500">Koreksi dari</span>
                    <span class="font-mono">{{ $transaction->reversalOf->receipt_number }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500">No. Rekening</span>
                <span class="font-mono">{{ $transaction->account->account_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Nasabah</span>
                <span>{{ $transaction->account->customer->full_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">No. Siswa</span>
                <span class="font-mono">{{ $transaction->account->customer->student_number }}</span>
            </div>
            @if ($transaction->recipientAccount)
                <div class="flex justify-between">
                    <span class="text-gray-500">Rekening Tujuan</span>
                    <span class="font-mono">{{ $transaction->recipientAccount->account_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Penerima</span>
                    <span>{{ $transaction->recipientAccount->customer?->full_name ?? '—' }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500">No. Slip</span>
                <span class="font-mono">{{ $transaction->slip_number ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Teller</span>
                <span>{{ $transaction->teller->full_name }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 pt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nominal</span>
                <span class="font-semibold {{ $isCredit ? 'text-green-600' : 'text-red-600' }}">
                    {{ $isCredit ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Biaya Administrasi</span>
                <span class="font-mono">Rp {{ number_format($transaction->admin_fee, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total</span>
                <span class="font-mono font-medium">Rp {{ number_format($total, 0, ',', '.') }}</span>
            </div>
            <p class="text-xs text-gray-500 italic">Terbilang: {{ Terbilang::rupiah($total) }}</p>
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Sebelum</span>
                <span class="font-mono">Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Sesudah</span>
                <span class="font-mono font-semibold text-gray-900">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Status Transaksi</span>
                <span class="font-medium uppercase">{{ $transaction->status }}</span>
            </div>
        </div>

        @if ($transaction->denomination_breakdown)
            <div class="border-t border-dashed border-gray-300 pt-4 mt-4">
                <p class="text-xs font-medium text-gray-700 mb-1">Rincian Uang Tunai</p>
                <div class="text-xs text-gray-600 space-y-0.5">
                    @foreach ($transaction->denomination_breakdown as $key => $count)
                        @php $value = CashDenomination::valueOf((string) $key); @endphp
                        @if ($value !== null)
                            <div class="flex justify-between">
                                <span>{{ CashDenomination::label($value) }} × {{ $count }}</span>
                                <span class="font-mono">Rp {{ number_format($value * $count, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <div class="border-t border-dashed border-gray-300 mt-4 pt-4">
            <div class="flex justify-between text-xs text-gray-500">
                <span>Dilayani oleh</span>
                <span>{{ $transaction->teller->full_name }}</span>
            </div>
            <p class="text-center text-xs text-gray-400 mt-4">Simpan struk ini sebagai bukti transaksi.</p>
        </div>
    </div>

    @unless ($transaction->isReversed())
        <div class="max-w-md mx-auto mt-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3 print:hidden">
            Transaksi berstatus <strong>{{ $transaction->status }}</strong> dan telah terkunci. Transaksi tidak dapat diubah atau dihapus.
        </div>
    @endunless
@endsection
