@extends('layouts.app')

@section('title', 'Laporan Harian Teller — Bank Mini')
@section('section', 'Piket Teller')

@php
    $bankName = \App\Models\SystemConfig::value('bank_name', 'Bank Mini Sekolah');
@endphp

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">DRAF UNTUK SUPERVISOR</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Laporan {{ $report->report_number }}</h1>
            <p class="text-sm text-stone-600 mt-1">Cetak dan serahkan fisik ke Supervisor untuk validasi.</p>
        </div>
        <div class="flex flex-wrap gap-2.5 print:hidden">
            <button type="button" data-print class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                Cetak laporan
            </button>
            <a href="{{ route('teller.closing.index', ['report_date' => $report->report_date->toDateString()]) }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100">
                Kembali ke penutupan
            </a>
        </div>
    </div>

    @if (($report->cash_difference ?? 0) !== 0)
        <div role="alert" class="mb-4 max-w-2xl mx-auto p-4 rounded-2xl border border-amber-300 bg-amber-50 text-amber-900 text-sm">
            <p class="font-bold">Draf berselisih {{ 'Rp '.number_format(abs($report->cash_difference ?? 0), 0, ',', '.') }} — bukan laporan final.</p>
            <p class="mt-1">Serahkan ke Supervisor untuk pemeriksaan. Cetak final terkunci sampai selisih diselesaikan.</p>
            @if ($report->approval_notes)
                <p class="mt-2 text-[13px]">{{ $report->approval_notes }}</p>
            @endif
        </div>
    @endif

    <div class="print-sheet max-w-2xl mx-auto bg-white rounded-2xl border border-stone-200 p-6" id="report">
        <div class="text-center border-b border-dashed border-stone-300 pb-4">
            <div class="w-11 h-11 bg-brand-800 rounded-xl mx-auto flex items-center justify-center mb-2" aria-hidden="true">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                </svg>
            </div>
            <p class="font-bold text-stone-900">{{ $bankName }}</p>
            <p class="text-[13px] text-stone-600">Laporan penutupan kas harian Teller</p>
        </div>

        <dl class="py-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Nomor laporan</dt>
                <dd class="tnum font-semibold text-right">{{ $report->report_number }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Tanggal kerja</dt>
                <dd class="tnum text-right">{{ $report->report_date->format('d/m/Y') }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Teller</dt>
                <dd class="text-right">{{ $report->teller->full_name }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Status</dt>
                <dd class="font-semibold text-right">{{ $report->status === 'approved' ? 'Disetujui' : ($report->status === 'rejected' ? 'Ditolak' : 'Menunggu validasi') }}</dd>
            </div>
        </dl>

        <div class="border-t border-dashed border-stone-300 pt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Saldo awal</span>
                <span class="tnum">Rp {{ number_format($report->opening_balance, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Total setoran</span>
                <span class="tnum font-semibold text-brand-900">+ Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Total penarikan</span>
                <span class="tnum font-semibold text-red-800">− Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Biaya admin</span>
                <span class="tnum">Rp {{ number_format($report->total_admin_fee, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3 font-bold">
                <span class="text-stone-900">Saldo akhir sistem</span>
                <span class="tnum text-stone-900">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Uang fisik di laci</span>
                <span class="tnum">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between gap-3">
                <span class="text-stone-600">Selisih kas</span>
                <span class="tnum font-bold {{ $report->cash_difference === 0 ? 'text-brand-900' : 'text-red-800' }}">
                    Rp {{ number_format($report->cash_difference ?? 0, 0, ',', '.') }}
                </span>
            </div>
        </div>

        @if ($report->closing_breakdown)
            <div class="border-t border-dashed border-stone-300 pt-4">
                <p class="text-[13px] font-semibold text-stone-700 mb-2">Rincian kertas dan logam</p>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[13px] text-stone-600">
                            <th scope="col" class="py-1 font-semibold">Pecahan</th>
                            <th scope="col" class="py-1 text-center font-semibold">Jumlah</th>
                            <th scope="col" class="py-1 text-right font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach (\App\Support\CashDenomination::GROUPS as $group => $info)
                            @php $hasGroup = collect($info['values'])->contains(fn ($v) => isset($report->closing_breakdown[$group.'_'.$v])); @endphp
                            @if ($hasGroup)
                                <tr>
                                    <td colspan="3" class="py-1.5 text-[13px] font-semibold text-stone-700">{{ $info['label'] }}</td>
                                </tr>
                                @foreach ($info['values'] as $value)
                                    @php $key = $group.'_'.$value; @endphp
                                    @if (isset($report->closing_breakdown[$key]))
                                        <tr>
                                            <td class="py-1">{{ \App\Support\CashDenomination::label($value) }}</td>
                                            <td class="py-1 text-center tnum">{{ $report->closing_breakdown[$key] }}</td>
                                            <td class="py-1 text-right tnum">Rp {{ number_format($value * $report->closing_breakdown[$key], 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                        <tr class="font-bold">
                            <td class="py-1.5" colspan="2">Total fisik</td>
                            <td class="py-1.5 text-right tnum">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if ($report->status === 'approved' && $report->approver)
            <div class="border-t border-dashed border-stone-300 mt-4 pt-4 text-[13px] text-stone-600 space-y-1">
                <div class="flex justify-between gap-3">
                    <span>Disetujui oleh</span>
                    <span class="text-stone-900 font-medium">{{ $report->approver->full_name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span>Waktu</span>
                    <span class="tnum">{{ $report->approved_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if ($report->approval_notes)
                    <div class="flex justify-between gap-3">
                        <span>Catatan</span>
                        <span class="text-stone-900">{{ $report->approval_notes }}</span>
                    </div>
                @endif
            </div>
        @endif

        <p class="text-center text-[13px] text-stone-500 mt-4">Dokumen sah sebagai bukti rekonsiliasi kas harian.</p>
    </div>
@endsection
