@extends('layouts.app')

@section('title', 'Detail Laporan — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Detail Laporan Harian</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $report->report_number }} — {{ $report->report_date->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('supervisor.reports.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Kembali ke daftar</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-500">Teller</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $report->teller->full_name }}</p>
                <div class="mt-3 pt-3 border-t border-gray-100 text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $report->status === 'approved' ? 'bg-green-100 text-green-700' : ($report->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($report->status) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Dikirim</span>
                        <span class="font-mono">{{ $report->submitted_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    @if ($report->approved_at)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Disetujui</span>
                            <span class="font-mono">{{ $report->approved_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Oleh</span>
                            <span>{{ $report->approver->full_name ?? '—' }}</span>
                        </div>
                    @endif
                    @if ($report->approval_notes)
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-500">Catatan</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $report->approval_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-500 mb-3">Rekonsiliasi Kas</p>
                <div class="space-y-2 text-sm">
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
                    <div class="flex justify-between pt-2 border-t border-gray-100 font-semibold">
                        <span class="text-gray-900">Saldo Sistem</span>
                        <span class="font-mono text-gray-900">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Uang Fisik</span>
                        <span class="font-mono">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Selisih Kas</span>
                        <span class="font-mono {{ $report->cash_difference === 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format($report->cash_difference ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if ($report->cashTransfer)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-2">Pemindahan Kas ke Brankas</p>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Nominal</span>
                                <span class="font-mono">Rp {{ number_format($report->cashTransfer->amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Lokasi</span>
                                <span>{{ $report->cashTransfer->source_location }} → {{ $report->cashTransfer->destination_location }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Waktu</span>
                                <span class="font-mono">{{ $report->cashTransfer->transferred_at?->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($report->status === 'submitted')
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <h2 class="text-sm font-medium text-gray-900">Validasi Laporan</h2>
                    <form method="POST" action="{{ route('supervisor.reports.approve', $report) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="approval_notes" class="block text-xs font-medium text-gray-600 mb-1">Catatan Persetujuan *</label>
                            <textarea id="approval_notes" name="approval_notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Catatan pemeriksaan fisik dan transaksi" required></textarea>
                            @error('approval_notes')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                            class="w-full bg-green-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-green-700 transition-colors"
                            onclick="this.disabled = true; this.form.submit();">
                            Setujui & Pindahkan Kas
                        </button>
                    </form>
                    <form method="POST" action="{{ route('supervisor.reports.reject', $report) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="rejection_notes" class="block text-xs font-medium text-gray-600 mb-1">Catatan Penolakan *</label>
                            <textarea id="rejection_notes" name="approval_notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="Alasan penolakan untuk perbaikan Teller" required></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-red-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-red-700 transition-colors">
                            Tolak Laporan
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <div class="border-b border-gray-200 px-5 py-3">
                    <h2 class="text-sm font-medium text-gray-900">Transaksi ({{ $transactions->count() }})</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3">No. Transaksi</th>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Jenis</th>
                            <th class="px-5 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="px-5 py-3 font-mono">{{ $transaction->receipt_number }}</td>
                                <td class="px-5 py-3 font-mono">{{ $transaction->transaction_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td>
                                <td class="px-5 py-3 text-right font-mono {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->type === 'deposit' ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-sm text-gray-500">Tidak ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <div class="border-b border-gray-200 px-5 py-3">
                    <h2 class="text-sm font-medium text-gray-900">Audit Log</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Aksi</th>
                            <th class="px-5 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="px-5 py-3 font-mono">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">{{ $log->action }}</td>
                                <td class="px-5 py-3 text-gray-500">
                                    @if ($log->metadata)
                                        {{ collect($log->metadata)->map(fn ($v, $k) => "$k: $v")->implode(', ') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-6 text-center text-sm text-gray-500">Tidak ada audit log.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
