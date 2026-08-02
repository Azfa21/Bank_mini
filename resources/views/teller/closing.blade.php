@extends('layouts.app')

@section('title', 'Penutupan Kas — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Penutupan Kas</h1>
            <p class="text-sm text-gray-500 mt-1">Rekonsiliasi kas harian sebelum laporan diserahkan ke Supervisor.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('teller.closing.index') }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5">
        <label for="report_date" class="block text-xs font-medium text-gray-600 mb-1">Tanggal Kerja</label>
        <div class="flex gap-3">
            <input type="date" id="report_date" name="report_date" value="{{ $date }}"
                class="w-full max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Tampilkan
            </button>
        </div>
    </form>

    @if ($report)
        <div class="mb-6 p-3 rounded-lg border text-sm {{ $report->status === 'approved' ? 'bg-green-50 border-green-200 text-green-700' : ($report->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-yellow-50 border-yellow-200 text-yellow-700') }}">
            @if ($report->status === 'approved')
                Laporan <strong>{{ $report->report_number }}</strong> telah disetujui dan kas dipindahkan ke brankas utama.
            @elseif ($report->status === 'rejected')
                Laporan <strong>{{ $report->report_number }}</strong> ditolak: {{ $report->approval_notes }}. Silakan periksa ulang dan kirim kembali.
            @else
                Laporan <strong>{{ $report->report_number }}</strong> berstatus <strong>menunggu validasi</strong> Supervisor.
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-3">
                    <h2 class="text-sm font-medium text-gray-900">Rekap Transaksi Harian</h2>
                </div>
                <div class="divide-y divide-gray-100 text-sm">
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Saldo Awal (laporan disetujui sebelumnya)</span>
                        <span class="font-mono">Rp {{ number_format($recap['opening_balance'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Total Setoran</span>
                        <span class="font-mono text-green-600">+ Rp {{ number_format($recap['total_deposit'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Total Penarikan</span>
                        <span class="font-mono text-red-600">− Rp {{ number_format($recap['total_withdrawal'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3 bg-gray-50 font-semibold">
                        <span class="text-gray-900">Saldo Akhir Sistem</span>
                        <span class="font-mono text-gray-900">Rp {{ number_format($recap['closing_balance_system'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    <p class="text-xs text-gray-500 mb-3">Hitung uang fisik di laci dan cocokkan dengan saldo akhir sistem. Jika terdapat selisih, laporan final tidak dapat dicetak.</p>
                    <form method="POST" action="{{ route('teller.closing.store') }}" class="flex gap-3 items-end">
                        @csrf
                        <input type="hidden" name="report_date" value="{{ $date }}">
                        <div class="flex-1 max-w-xs">
                            <label for="physical_cash" class="block text-xs font-medium text-gray-600 mb-1">Uang Fisik di Laci *</label>
                            <input type="number" id="physical_cash" name="physical_cash" value="{{ old('physical_cash') }}" min="0" step="1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Contoh: 500000" required>
                            @error('physical_cash')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                            class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                            onclick="this.disabled = true; this.form.submit();">
                            Buat Laporan
                        </button>
                    </form>

                    @php
                        $difference = (int) old('physical_cash') - $recap['closing_balance_system'];
                    @endphp
                    @if (session('error'))
                        <div class="mt-4 p-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm">
                            <strong>Selisih kas terdeteksi.</strong> Periksa kembali uang fisik di laci.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Transaksi {{ date('d/m/Y', strtotime($date)) }}</h2>
                @if ($transactions->isEmpty())
                    <p class="text-xs text-gray-500">Tidak ada transaksi pada tanggal ini.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($transactions as $transaction)
                            <li class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-mono text-xs text-gray-400">{{ $transaction->receipt_number }}</p>
                                    <p class="text-gray-900">{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</p>
                                </div>
                                <span class="font-mono {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->type === 'deposit' ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
