@extends('layouts.app')

@section('title', 'Laporan Harian Teller — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Laporan Harian Teller</h1>
            <p class="text-sm text-gray-500 mt-1">Draf laporan penutupan kas untuk validasi Supervisor.</p>
        </div>
        <div class="flex gap-2 print:hidden">
            <button onclick="window.print()" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Cetak Laporan
            </button>
            <a href="{{ route('teller.closing.index', ['report_date' => $report->report_date->toDateString()]) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                Kembali
            </a>
        </div>
    </div>

    <div class="print-sheet max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-6" id="report">
        <div class="text-center border-b border-dashed border-gray-300 pb-4">
            <div class="w-10 h-10 bg-blue-600 rounded-lg mx-auto flex items-center justify-center mb-2">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                </svg>
            </div>
            <p class="font-semibold text-gray-900">Bank Mini Sekolah</p>
            <p class="text-xs text-gray-500">Laporan Penutupan Kas Harian Teller</p>
        </div>

        <div class="py-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Nomor Laporan</span>
                <span class="font-mono font-medium">{{ $report->report_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal Kerja</span>
                <span>{{ $report->report_date->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Teller</span>
                <span>{{ $report->teller->full_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span class="font-medium">{{ $report->status === 'approved' ? 'Disetujui' : ($report->status === 'rejected' ? 'Ditolak' : 'Menunggu Validasi') }}</span>
            </div>
        </div>

        <div class="border-t border-dashed border-gray-300 pt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Awal</span>
                <span class="font-mono">Rp {{ number_format($report->opening_balance, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Setoran</span>
                <span class="font-mono text-green-600">+ Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Penarikan</span>
                <span class="font-mono text-red-600">− Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-semibold">
                <span class="text-gray-900">Saldo Akhir Sistem</span>
                <span class="font-mono text-gray-900">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Uang Fisik di Laci</span>
                <span class="font-mono">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Selisih Kas</span>
                <span class="font-mono {{ $report->cash_difference === 0 ? 'text-green-600' : 'text-red-600' }}">
                    Rp {{ number_format($report->cash_difference ?? 0, 0, ',', '.') }}
                </span>
            </div>
        </div>

        @if ($report->status === 'approved' && $report->approver)
            <div class="border-t border-dashed border-gray-300 mt-4 pt-4 text-xs text-gray-500 space-y-1">
                <div class="flex justify-between">
                    <span>Disetujui oleh</span>
                    <span>{{ $report->approver->full_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu</span>
                    <span>{{ $report->approved_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if ($report->approval_notes)
                    <div class="flex justify-between">
                        <span>Catatan</span>
                        <span>{{ $report->approval_notes }}</span>
                    </div>
                @endif
            </div>
        @endif

        <p class="text-center text-xs text-gray-400 mt-4">Dokumen ini sah sebagai bukti rekonsiliasi kas harian.</p>
    </div>
@endsection
