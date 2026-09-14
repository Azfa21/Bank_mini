@extends('layouts.app')

@section('title', 'Dashboard Teller — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard Teller</h1>
        <p class="text-sm text-gray-500 mt-1">Layani transaksi setoran dan penarikan nasabah.</p>
        <p class="text-xs text-gray-400 mt-1">
            Jam operasional: {{ \App\Models\SystemConfig::value('operational_hours', '07.30 - 14.00 WIB') }}
            @php $maxWithoutOrder = (int) \App\Models\SystemConfig::value('max_withdrawal_without_order', 0); @endphp
            @if ($maxWithoutOrder > 0)
                · Batas penarikan tanpa pesanan: Rp {{ number_format($maxWithoutOrder, 0, ',', '.') }}
            @endif
        </p>
    </div>

    @php
        $today = now()->toDateString();
        $todayTransactions = \App\Models\Transaction::whereDate('transaction_at', $today);
        $totalDeposit = (clone $todayTransactions)->where('type', 'deposit')->where('status', 'success')->sum('amount');
        $totalWithdrawal = (clone $todayTransactions)->where('type', 'withdrawal')->where('status', 'success')->sum('amount');
        $todayCount = (clone $todayTransactions)->where('status', 'success')->count();
        $report = \App\Models\DailyReport::where('teller_id', Auth::id())->where('report_date', $today)->first();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Transaksi Hari Ini</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $todayCount }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Setoran</div>
            <div class="text-2xl font-semibold text-green-600 mt-1">Rp {{ number_format($totalDeposit, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Penarikan</div>
            <div class="text-2xl font-semibold text-red-600 mt-1">Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Status Laporan</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $report ? ucfirst($report->status) : 'Belum ada' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('teller.customers.search') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Pencarian Nasabah</div>
            <div class="text-xs text-gray-500 mt-1">Cari nasabah melalui nomor rekening atau pindai QR Code.</div>
        </a>
        <a href="{{ route('teller.customers.search') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Transaksi Loket</div>
            <div class="text-xs text-gray-500 mt-1">Mulai setoran atau penarikan tunai setelah menemukan nasabah.</div>
        </a>
        <a href="{{ route('teller.customers.register') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Registrasi Nasabah</div>
            <div class="text-xs text-gray-500 mt-1">Daftarkan nasabah baru didampingi Teller, nomor rekening otomatis.</div>
        </a>
        <a href="{{ route('teller.closing.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Penutupan Kas</div>
            <div class="text-xs text-gray-500 mt-1">Rekonsiliasi rincian uang fisik dan buat laporan harian.</div>
        </a>
    </div>
@endsection
