@extends('layouts.app')

@php
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $periodLabel = $months[(int) $start->format('n')].' '.$start->format('Y');
    $indexRoute = route('admin.reports.monthly');
    $printRoute = route('admin.reports.monthly.print');
@endphp

@section('title', 'Laporan Bulanan | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between print:hidden">
        <div>
            <p class="text-xs font-semibold text-brand-900">REKAP BULANAN</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Laporan bulanan {{ $periodLabel }}</h1>
            <p class="text-sm text-stone-600 mt-1">Rekap setoran, penarikan, dan status laporan harian.</p>
        </div>
        <a href="{{ $printRoute }}?month={{ $month }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900">
            Cetak laporan
        </a>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-5 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end print:hidden">
        <div class="sm:w-56">
            <x-input-label for="month">Bulan</x-input-label>
            <x-input id="month" name="month" type="month" :value="$month" />
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
            <p class="text-[13px] font-medium text-stone-600">Total setoran</p>
            <p class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totals['deposit'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
            <p class="text-[13px] font-medium text-stone-600">Total penarikan</p>
            <p class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totals['withdrawal'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
            <p class="text-[13px] font-medium text-stone-600">Arus kas bersih</p>
            <p class="text-xl font-bold text-brand-900 mt-1 tnum">Rp {{ number_format($totals['deposit'] - $totals['withdrawal'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-stone-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-5 py-3">Teller</th>
                        <th class="px-5 py-3 text-right">Transaksi</th>
                        <th class="px-5 py-3 text-right">Setoran</th>
                        <th class="px-5 py-3 text-right">Penarikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($perTeller as $row)
                        <tr class="hover:bg-stone-50">
                            <td class="px-5 py-3">{{ $tellers[$row->teller_id]->full_name ?? '-' }}</td>
                            <td class="px-5 py-3 text-right tnum">{{ number_format($row->total) }}</td>
                            <td class="px-5 py-3 text-right tnum">Rp {{ number_format($row->deposit, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right tnum">Rp {{ number_format($row->withdrawal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada transaksi pada bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 p-5">
            <h2 class="text-sm font-bold text-stone-900">Status laporan harian</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-stone-600">Total laporan</dt><dd class="font-semibold">{{ $reportSummary['total'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Disetujui</dt><dd class="font-semibold text-brand-900">{{ $reportSummary['approved'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Menunggu validasi</dt><dd class="font-semibold text-amber-800">{{ $reportSummary['submitted'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Ditolak</dt><dd class="font-semibold text-red-800">{{ $reportSummary['rejected'] }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-100 text-left">
                <tr>
                    <th class="px-5 py-3">Nomor laporan</th>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Teller</th>
                    <th class="px-5 py-3 text-right">Saldo sistem</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($reports as $report)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 tnum font-medium">{{ $report->report_number }}</td>
                        <td class="px-5 py-3 tnum">{{ $report->report_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">{{ $report->teller->full_name ?? '-' }}</td>
                        <td class="px-5 py-3 text-right tnum">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">{{ $report->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada laporan harian pada bulan ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
