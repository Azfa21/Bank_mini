@extends('layouts.app')

@section('title', 'Detail Laporan — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">PERIKSA SEBELUM SETUJUI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Laporan {{ $report->report_number }}</h1>
            <p class="text-sm text-stone-600 mt-1">Tanggal {{ $report->report_date->format('d/m/Y') }} · Teller {{ $report->teller->full_name }}</p>
        </div>
        <a href="{{ route('supervisor.reports.index') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke daftar</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <div class="lg:col-span-1 space-y-4">
            <section aria-label="Status laporan" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5">
                <dl class="text-sm space-y-2.5">
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Status</dt>
                        <dd>
                            <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $report->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : ($report->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900') }}">
                                {{ ['draft' => 'Draf', 'submitted' => 'Menunggu validasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$report->status] ?? ucfirst($report->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Dikirim</dt>
                        <dd class="tnum">{{ $report->submitted_at?->format('d/m/Y H:i') ?? 'Belum dikirim' }}</dd>
                    </div>
                    @if ($report->approved_at)
                        <div class="flex justify-between gap-3">
                            <dt class="text-stone-600">Disetujui</dt>
                            <dd class="tnum">{{ $report->approved_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-stone-600">Oleh</dt>
                            <dd>{{ $report->approver->full_name ?? 'Belum ada' }}</dd>
                        </div>
                    @endif
                    @if ($report->approval_notes)
                        <div class="pt-2.5 border-t border-stone-100">
                            <dt class="text-[13px] font-medium text-stone-600">Catatan</dt>
                            <dd class="text-sm text-stone-900 mt-1">{{ $report->approval_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section aria-label="Perbandingan kas" class="bg-white rounded-2xl border border-stone-200 p-5">
                <h2 class="text-[13px] font-semibold text-stone-700 mb-2.5">Sistem vs fisik</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Saldo awal</dt>
                        <dd class="tnum">Rp {{ number_format($report->opening_balance, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Total setoran</dt>
                        <dd class="tnum font-semibold text-brand-900">+ Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Total penarikan</dt>
                        <dd class="tnum font-semibold text-red-800">− Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 pt-2 border-t border-stone-100 font-bold">
                        <dt class="text-stone-900">Saldo sistem</dt>
                        <dd class="tnum text-stone-900">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Uang fisik</dt>
                        <dd class="tnum">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-stone-600">Selisih</dt>
                        <dd class="tnum font-bold {{ $report->cash_difference === 0 ? 'text-brand-900' : 'text-red-800' }}">
                            Rp {{ number_format($report->cash_difference ?? 0, 0, ',', '.') }}
                        </dd>
                    </div>
                </dl>

                @if ($report->cashTransfer)
                    <div class="mt-4 pt-4 border-t border-stone-100 text-sm space-y-1.5">
                        <h3 class="text-[13px] font-semibold text-stone-700">Kas pindah ke brankas</h3>
                        <div class="flex justify-between gap-3">
                            <span class="text-stone-600">Nominal</span>
                            <span class="tnum">Rp {{ number_format($report->cashTransfer->amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-stone-600">Rute</span>
                            <span>{{ $report->cashTransfer->source_location }} ke {{ $report->cashTransfer->destination_location }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-stone-600">Waktu</span>
                            <span class="tnum">{{ $report->cashTransfer->transferred_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @endif
            </section>

            @if ($report->closing_breakdown)
                <section aria-label="Rincian pecahan" class="bg-white rounded-2xl border border-stone-200 p-5">
                    <h2 class="text-[13px] font-semibold text-stone-700 mb-2.5">Rincian kertas dan logam</h2>
                    <dl class="text-sm space-y-1.5">
                        @foreach (\App\Support\CashDenomination::GROUPS as $group => $info)
                            @foreach ($info['values'] as $value)
                                @php $key = $group.'_'.$value; @endphp
                                @if (isset($report->closing_breakdown[$key]))
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-stone-600">{{ \App\Support\CashDenomination::label($value) }}</dt>
                                        <dd class="tnum">{{ $report->closing_breakdown[$key] }} × = Rp {{ number_format($value * $report->closing_breakdown[$key], 0, ',', '.') }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    </dl>
                </section>
            @endif

            @if ($report->status === 'submitted')
                <section aria-label="Keputusan validasi" class="bg-white rounded-2xl border border-stone-200 p-5 space-y-4">
                    <h2 class="text-[15px] font-bold text-stone-900">Keputusan validasi</h2>
                    <form method="POST" action="{{ route('supervisor.reports.approve', $report) }}" class="space-y-3" id="approve-form" data-confirm="Setujui laporan ini? Kas laci pindah ke brankas dan tidak bisa dibatalkan.">
                        @csrf
                        <div>
                            <x-input-label for="approval_notes">Catatan pemeriksaan wajib</x-input-label>
                            <x-textarea id="approval_notes" name="approval_notes" rows="3"
                                placeholder="Contoh: Uang fisik cocok, slip lengkap" required />
                            @error('approval_notes')
                                <p role="alert" class="text-[13px] font-medium text-red-800 mt-1.5">Periksa: {{ $message }}</p>
                            @enderror
                        </div>
                        <x-primary-button id="approve-submit" data-loading-text="Menyetujui..." class="w-full">
                            Setujui dan pindahkan kas
                        </x-primary-button>
                    </form>
                    <form method="POST" action="{{ route('supervisor.reports.reject', $report) }}" class="space-y-3 border-t border-stone-100 pt-4" id="reject-form" data-confirm="Tolak laporan ini? Teller harus perbaiki dan kirim ulang.">
                        @csrf
                        <div>
                            <x-input-label for="rejection_notes">Alasan penolakan wajib</x-input-label>
                            <x-textarea id="rejection_notes" name="approval_notes" rows="3"
                                placeholder="Contoh: Selisih Rp 10.000, hitung ulang laci" required />
                        </div>
                        <button type="submit" id="reject-submit" data-loading-text="Menolak..." class="inline-flex items-center justify-center w-full min-h-[48px] px-5 text-[15px] font-semibold text-white bg-red-700 rounded-xl hover:bg-red-800 transition-colors disabled:opacity-60">
                            Tolak laporan
                        </button>
                    </form>
                </section>
            @endif
        </div>

        <div class="lg:col-span-2 space-y-4 sm:space-y-5">
            <section aria-label="Daftar transaksi laporan">
                <x-data-table caption="Transaksi pada laporan {{ $report->report_number }}" :headers="[['label' => 'Nomor transaksi'], ['label' => 'Waktu'], ['label' => 'Jenis'], ['label' => 'Nominal', 'align' => 'right'], ['label' => 'Koreksi', 'align' => 'right']]">
                        @forelse ($transactions as $transaction)
                            @php
                                $typeLabel = match ($transaction->type) {
                                    'deposit' => 'Setoran',
                                    'withdrawal' => 'Penarikan',
                                    'transfer' => 'Transfer',
                                    'reversal' => 'Koreksi',
                                    default => ucfirst($transaction->type),
                                };
                                $isCredit = $transaction->type === 'deposit'
                                    || ($transaction->type === 'reversal' && $transaction->reversalOf?->type === 'withdrawal');
                                $canReverse = $report->status !== 'approved'
                                    && $transaction->status === 'success'
                                    && $transaction->type !== 'reversal';
                            @endphp
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-3 tnum">
                                    {{ $transaction->receipt_number }}
                                    @if ($transaction->status === 'reversed')
                                        <span class="ml-1 text-[13px] font-semibold px-1.5 py-0.5 rounded border bg-red-50 border-red-200 text-red-800">Dikoreksi</span>
                                    @elseif ($transaction->type === 'reversal')
                                        <span class="ml-1 text-[13px] font-semibold px-1.5 py-0.5 rounded border bg-amber-50 border-amber-200 text-amber-900">Pembalik</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 tnum">{{ $transaction->transaction_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">{{ $typeLabel }}</td>
                                <td class="px-5 py-3 text-right tnum font-semibold {{ $isCredit ? 'text-brand-900' : 'text-red-800' }}">
                                    {{ $isCredit ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($canReverse)
                                        <form method="POST" action="{{ route('supervisor.transactions.reverse', $transaction) }}"
                                            data-reversal="{{ $transaction->receipt_number }}">
                                            @csrf
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-red-800 hover:underline">Koreksi</button>
                                        </form>
                                    @else
                                        <span class="text-sm text-stone-400">Tidak bisa</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-stone-600">Tidak ada transaksi pada laporan ini.</td>
                            </tr>
                        @endforelse
                </x-data-table>
            </section>

            <section aria-label="Jejak audit laporan">
                <x-data-table caption="Audit log laporan" :headers="[['label' => 'Waktu'], ['label' => 'Aksi'], ['label' => 'Catatan']]">
                        @forelse ($auditLogs as $log)
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-3 tnum">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3">{{ $log->action }}</td>
                                <td class="px-5 py-3 text-stone-600">
                                    @if ($log->metadata)
                                        {{ collect($log->metadata)->map(fn ($v, $k) => "$k: $v")->implode(', ') }}
                                    @else
                                        Tidak ada catatan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada jejak audit.</td>
                            </tr>
                        @endforelse
                </x-data-table>
            </section>
        </div>
    </div>

@endsection
