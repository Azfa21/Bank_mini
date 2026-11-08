@extends('layouts.app')

@section('title', 'Dashboard Teller | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 sm:mb-6">
        <p class="text-xs font-semibold text-brand-900">LOKET HARI INI</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Cari penabung, layani transaksi</h1>
    </div>

    @php
        $today = now()->toDateString();
        $todayTransactions = \App\Models\Transaction::whereDate('transaction_at', $today);
        $totalDeposit = (clone $todayTransactions)->where('type', 'deposit')->where('status', 'success')->sum('amount');
        $totalWithdrawal = (clone $todayTransactions)->where('type', 'withdrawal')->where('status', 'success')->sum('amount');
        $todayCount = (clone $todayTransactions)->where('status', 'success')->count();
        $report = \App\Models\DailyReport::where('teller_id', Auth::id())->where('report_date', $today)->first();
    @endphp

    <section aria-label="Aksi utama loket" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 mb-5">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-bold text-stone-900">Mulai dari pencarian penabung</h2>
                <p class="text-sm text-stone-600 mt-1">Cari memakai nomor rekening, NIS, nama, atau pindai QR.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2.5 lg:shrink-0">
                <a href="{{ route('teller.members.search') }}" class="inline-flex items-center justify-center gap-2 min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 110-16 8 8 0 010 16z"/>
                    </svg>
                    Cari penabung
                </a>
                <a href="{{ route('teller.members.register') }}" class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">
                    Daftarkan anggota baru
                </a>
            </div>
        </div>
    </section>

    <section aria-label="Kas laci hari ini" class="mb-5 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div class="shrink-0">
            <p class="text-[13px] font-medium text-stone-600">Kas di laci hari ini</p>
            <p class="tnum text-xl font-bold text-stone-900 mt-0.5">Rp {{ number_format($recap['closing_balance_system'], 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('teller.cash-requests.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors sm:shrink-0">
            Minta kas
        </a>
    </section>

    <section aria-label="Ringkasan transaksi hari ini" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Transaksi berhasil</dt>
            <dd class="text-2xl font-bold text-stone-900 mt-1 tnum">{{ $todayCount }}</dd>
            <dd class="text-xs text-stone-600 mt-1">Hari ini</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Total setoran</dt>
            <dd class="text-lg sm:text-xl font-bold text-brand-900 mt-1 tnum">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</dd>
            <dd class="text-xs text-stone-600 mt-1">Masuk ke tabungan</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Total penarikan</dt>
            <dd class="text-lg sm:text-xl font-bold text-red-800 mt-1 tnum">Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</dd>
            <dd class="text-xs text-stone-600 mt-1">Keluar tunai</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Laporan kas</dt>
            <dd class="text-base sm:text-lg font-bold text-stone-900 mt-1">{{ $report ? $report->statusLabel() : 'Belum dibuat' }}</dd>
            <dd class="text-xs text-stone-600 mt-1">
                @if ($report)
                    {{ $report->report_number }}
                @else
                    Wajib sebelum tutup loket
                @endif
            </dd>
        </dl>
    </section>

    @if (!$report)
        <div role="status" class="mb-5 p-4 bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-2xl flex items-start gap-2.5">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p>Belum ada laporan kas hari ini. Setelah loket tutup, buka <a href="{{ route('teller.closing.index') }}" class="font-semibold underline">Penutupan kas</a> untuk rekonsiliasi dan serahkan ke Administrator.</p>
        </div>
    @endif
@endsection
