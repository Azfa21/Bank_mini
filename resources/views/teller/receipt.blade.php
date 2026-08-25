@extends('layouts.app')

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

    <div class="print-sheet max-w-md mx-auto bg-white rounded-xl border border-gray-200 p-6" id="receipt">
        <div class="text-center border-b border-dashed border-gray-300 pb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                </svg>
            </div>
            <p class="font-semibold text-gray-900">Bank Mini Sekolah</p>
            <p class="text-xs text-gray-500">Struk Transaksi Loket</p>
        </div>

        <div class="py-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nomor Transaksi</span>
                <span class="font-mono font-medium">{{ $transaction->receipt_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal</span>
                <span>{{ $transaction->transaction_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Jenis Transaksi</span>
                <span>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">No. Rekening</span>
                <span class="font-mono">{{ $transaction->account->account_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Nasabah</span>
                <span>{{ $transaction->account->customer->full_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">No. Slip</span>
                <span class="font-mono">{{ $transaction->slip_number ?? '—' }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 pt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nominal</span>
                <span class="font-semibold {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $transaction->type === 'deposit' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Sebelum</span>
                <span class="font-mono">Rp {{ number_format($transaction->balance_before, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Akhir</span>
                <span class="font-mono font-semibold text-gray-900">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 mt-4 pt-4">
            <div class="flex justify-between text-xs text-gray-500">
                <span>Dilayani oleh</span>
                <span>{{ $transaction->teller->full_name }}</span>
            </div>
            <p class="text-center text-xs text-gray-400 mt-4">Simpan struk ini sebagai bukti transaksi.</p>
        </div>
    </div>

    <div class="max-w-md mx-auto mt-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3 print:hidden">
        Transaksi berstatus <strong>success</strong> dan telah terkunci. Transaksi tidak dapat diubah atau dihapus.
    </div>
@endsection
