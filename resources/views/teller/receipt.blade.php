@extends('layouts.app')

@php
    use App\Models\SystemConfig;
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
@endphp

@section('title', 'Struk Transaksi | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">TRANSAKSI TERSIMPAN</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Struk {{ $transaction->receipt_number }}</h1>
            <p class="text-sm text-stone-600 mt-1">Status final dan terkunci. Serahkan struk ke penabung.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 print:hidden">
            <button type="button" data-print class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                Cetak struk
            </button>
            <a href="{{ route('teller.members.show', $transaction->account_id) }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100">
                Transaksi baru
            </a>
            <a href="{{ route('teller.members.search') }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-semibold text-stone-700 rounded-xl hover:bg-stone-100">
                Cari penabung
            </a>
        </div>
    </div>

    @if ($transaction->isReversed())
        <div role="alert" class="max-w-md mx-auto mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-2xl p-4 print:hidden">
            Transaksi ini sudah <strong>dikoreksi</strong> oleh {{ $transaction->reversedBy->full_name ?? 'Administrator' }}
            {{ $transaction->reversed_at?->format('d/m/Y H:i') }}. Alasan: {{ $transaction->reversal_reason }}
        </div>
    @endif

    <div class="print-sheet max-w-md mx-auto bg-white rounded-2xl border border-stone-200 p-6" id="receipt">
        <div class="text-center border-b border-dashed border-stone-300 pb-4">
            <div class="w-11 h-11 bg-brand-800 rounded-xl mx-auto flex items-center justify-center mb-2" aria-hidden="true">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                </svg>
            </div>
            <p class="font-bold text-stone-900">{{ $bankName }}</p>
            <p class="text-[13px] text-stone-600">Struk transaksi loket</p>
            @if ($transaction->isReversal())
                <p class="text-[13px] font-bold text-red-800 mt-1">JURNAL PEMBALIK</p>
            @endif
        </div>

        <dl class="py-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Nomor struk</dt>
                <dd class="tnum font-semibold text-right">{{ $transaction->receipt_number }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Referensi jurnal</dt>
                <dd class="tnum text-right">{{ $transaction->journalEntries->first()?->journal_number ?? 'Belum ada' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Tanggal</dt>
                <dd class="tnum text-right">{{ $transaction->transaction_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Jenis</dt>
                <dd class="text-right font-medium">{{ $typeLabel }}</dd>
            </div>
            @if ($transaction->reversalOf)
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Koreksi dari</dt>
                    <dd class="tnum text-right">{{ $transaction->reversalOf->receipt_number }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">No. rekening</dt>
                <dd class="tnum text-right">{{ $transaction->account->account_number }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Penabung</dt>
                <dd class="text-right font-medium">{{ $transaction->account->saver->full_name }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">No. siswa</dt>
                <dd class="tnum text-right">{{ $transaction->account->saver->student_number }}</dd>
            </div>
            @if ($transaction->recipientAccount)
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Rekening tujuan</dt>
                    <dd class="tnum text-right">{{ $transaction->recipientAccount->account_number }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Penerima</dt>
                    <dd class="text-right">{{ $transaction->recipientAccount->saver?->full_name ?? 'Belum ada' }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">No. slip</dt>
                <dd class="tnum text-right">{{ $transaction->slip_number ?? 'Belum ada' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Teller</dt>
                <dd class="text-right">{{ $transaction->teller->full_name }}</dd>
            </div>
        </dl>

        <div class="border-t border-dashed border-stone-300 pt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Nominal</span>
                <span class="tnum font-bold {{ $isCredit ? 'text-brand-900' : 'text-red-800' }}">
                    {{ $isCredit ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                </span>
            </div>
            <p class="text-[13px] text-stone-600 italic">Terbilang: {{ Terbilang::rupiah((int) $transaction->amount) }}</p>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Saldo sebelum</span>
                <span class="tnum">Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Saldo sesudah</span>
                <span class="tnum font-bold text-stone-900">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Status</span>
                <span class="font-semibold">{{ $transaction->statusLabel() }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-stone-300 mt-4 pt-4">
            <div class="flex justify-between text-[13px] text-stone-600">
                <span>Dilayani oleh</span>
                <span>{{ $transaction->teller->full_name }}</span>
            </div>
            <p class="text-center text-[13px] text-stone-500 mt-4">Simpan struk ini sebagai bukti transaksi.</p>
        </div>
    </div>

    @unless ($transaction->isReversed())
        <div role="status" class="max-w-md mx-auto mt-4 bg-brand-50 border border-brand-200 text-brand-900 text-sm rounded-2xl p-4 print:hidden">
            Transaksi <strong>{{ $transaction->statusLabel() }}</strong> dan terkunci. Tidak bisa diubah atau dihapus Teller.
        </div>
    @endunless
@endsection
