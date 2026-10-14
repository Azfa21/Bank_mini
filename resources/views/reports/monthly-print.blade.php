@extends('layouts.app')

@php
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $periodLabel = $months[(int) $start->format('n')].' '.$start->format('Y');
    $bankName = \App\Models\SystemConfig::value('bank_name', 'Bank Mini Sekolah');
    $backRoute = $isSupervisor ? route('supervisor.reports.monthly') : route('admin.reports.monthly');
@endphp

@section('title', 'Cetak Laporan Bulanan — Bank Mini')
@section('section', $isSupervisor ? 'Supervisor' : 'Administrator')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between print:hidden">
        <div>
            <p class="text-xs font-semibold text-brand-900">REKAP BULANAN</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Laporan bulanan {{ $periodLabel }}</h1>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <button type="button" data-print class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900">
                Cetak laporan
            </button>
            <a href="{{ $backRoute }}?month={{ $month }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100">
                Kembali
            </a>
        </div>
    </div>

    <div class="print-sheet max-w-3xl mx-auto bg-white rounded-2xl border border-stone-200 p-6" id="monthly-report">
        <div class="text-center border-b border-dashed border-stone-300 pb-4">
            <p class="font-bold text-stone-900">{{ $bankName }}</p>
            <p class="text-[13px] text-stone-600">Laporan rekapitulasi bulanan</p>
            <p class="text-[13px] text-stone-600">Periode {{ $periodLabel }}</p>
        </div>

        <dl class="py-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Total transaksi</dt>
                <dd class="tnum font-semibold">{{ number_format($totals['total']) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Total setoran</dt>
                <dd class="tnum">Rp {{ number_format($totals['deposit'], 0, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Total penarikan</dt>
                <dd class="tnum">Rp {{ number_format($totals['withdrawal'], 0, ',', '.') }}</dd>
            </div>
            <div class="flex justify-between gap-3 border-t border-dashed border-stone-300 pt-2">
                <dt class="font-semibold text-stone-900">Arus kas bersih</dt>
                <dd class="tnum font-bold">Rp {{ number_format($totals['deposit'] - $totals['withdrawal'], 0, ',', '.') }}</dd>
            </div>
        </dl>

        <div class="border-t border-dashed border-stone-300 pt-4">
            <p class="text-sm font-semibold text-stone-900 mb-2">Rincian per Teller</p>
            <table class="w-full text-sm">
                <thead class="text-left border-b border-stone-200">
                    <tr>
                        <th class="py-2">Teller</th>
                        <th class="py-2 text-right">Transaksi</th>
                        <th class="py-2 text-right">Setoran</th>
                        <th class="py-2 text-right">Penarikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($perTeller as $row)
                        <tr>
                            <td class="py-2">{{ $tellers[$row->teller_id]->full_name ?? '—' }}</td>
                            <td class="py-2 text-right tnum">{{ number_format($row->total) }}</td>
                            <td class="py-2 text-right tnum">Rp {{ number_format($row->deposit, 0, ',', '.') }}</td>
                            <td class="py-2 text-right tnum">Rp {{ number_format($row->withdrawal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-stone-600">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-dashed border-stone-300 mt-4 pt-4">
            <p class="text-sm font-semibold text-stone-900 mb-2">Laporan harian</p>
            <p class="text-sm text-stone-600">
                Total {{ $reportSummary['total'] }} laporan —
                disetujui {{ $reportSummary['approved'] }},
                menunggu {{ $reportSummary['submitted'] }},
                ditolak {{ $reportSummary['rejected'] }}.
            </p>
            <p class="text-sm text-stone-600 mt-1">
                Total uang fisik Rp {{ number_format($reportSummary['physical'], 0, ',', '.') }},
                total selisih Rp {{ number_format($reportSummary['difference'], 0, ',', '.') }}.
            </p>
        </div>

        <div class="border-t border-dashed border-stone-300 mt-6 pt-4 grid grid-cols-2 gap-8 text-sm text-center">
            <div>
                <p class="text-stone-600">Dibuat oleh</p>
                <div class="h-16"></div>
                <p class="border-t border-stone-300 pt-1">Administrator</p>
            </div>
            <div>
                <p class="text-stone-600">Disetujui oleh</p>
                <div class="h-16"></div>
                <p class="border-t border-stone-300 pt-1">Supervisor</p>
            </div>
        </div>
    </div>
@endsection
