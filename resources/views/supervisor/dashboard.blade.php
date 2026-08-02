@extends('layouts.app')

@section('title', 'Dashboard Supervisor — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard Supervisor</h1>
        <p class="text-sm text-gray-500 mt-1">Validasi laporan dan approve penutupan kas.</p>
    </div>

    @php
        $waiting = \App\Models\DailyReport::where('status', 'submitted')->count();
        $approved = \App\Models\DailyReport::where('status', 'approved')->count();
        $todayTransactions = \App\Models\Transaction::whereDate('transaction_at', now()->toDateString())->where('status', 'success')->count();
        $recentLogs = \App\Models\AuditLog::latest('id')->limit(3)->get();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Menunggu Approval</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $waiting }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Laporan Disetujui</div>
            <div class="text-2xl font-semibold text-green-600 mt-1">{{ $approved }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Transaksi Hari Ini</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $todayTransactions }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Transaksi</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ \App\Models\Transaction::where('status', 'success')->count() }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('supervisor.reports.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Laporan Harian Teller</div>
            <div class="text-xs text-gray-500 mt-1">Periksa, setujui, atau tolak laporan penutupan kas.</div>
        </a>
    </div>
@endsection
