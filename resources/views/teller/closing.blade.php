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
        <x-input-label for="report_date" size="xs">Tanggal Kerja</x-input-label>
        <div class="flex gap-3">
            <x-input id="report_date" name="report_date" type="date" :value="$date" :block="false" class="max-w-xs" />
            <x-primary-button>
                Tampilkan
            </x-primary-button>
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
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-gray-500">Biaya Administrasi (jika ada)</span>
                        <span class="font-mono text-gray-700">Rp {{ number_format($recap['total_admin_fee'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3 bg-gray-50 font-semibold">
                        <span class="text-gray-900">Saldo Akhir Sistem</span>
                        <span class="font-mono text-gray-900">Rp {{ number_format($recap['closing_balance_system'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    <p class="text-xs text-gray-500 mb-3">Hitung rincian uang fisik per pecahan (kertas &amp; logam). Total dihitung otomatis dan harus sama dengan saldo akhir sistem.</p>
                    <form method="POST" action="{{ route('teller.closing.store') }}" class="space-y-4" id="closing-form">
                        @csrf
                        <input type="hidden" name="report_date" value="{{ $date }}">

                        <x-denomination-input :selected="old('denominations', [])" />

                        <div class="flex gap-3 items-end flex-wrap">
                            <div class="flex-1 max-w-xs">
                                <x-input-label for="physical_cash" size="xs">Total Uang Fisik di Laci (otomatis) *</x-input-label>
                                <x-input id="physical_cash" name="physical_cash" type="number" min="0" step="1"
                                    :value="old('physical_cash', 0)" :readonly="true" required data-denomination-total />
                                <x-input-error :message="$errors->first('physical_cash')" />
                            </div>
                            <x-primary-button variant="green"
                                onclick="this.disabled = true; this.form.submit();">
                                Buat Laporan
                            </x-primary-button>
                        </div>
                    </form>

                    @if (session('error'))
                        <div class="mt-4 p-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm">
                            <strong>Selisih kas terdeteksi.</strong> Periksa kembali rincian uang fisik di laci. {{ session('error') }}
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
                                    <p class="text-gray-900">{{ $transaction->typeLabel() }}</p>
                                </div>
                                <span class="font-mono {{ $transaction->isCreditFor($transaction->account_id) ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $transaction->isCreditFor($transaction->account_id) ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const inputs = document.querySelectorAll('[data-denomination-input]');
            const total = document.querySelector('[data-denomination-total]');

            const compute = () => {
                let sum = 0;
                inputs.forEach((input) => {
                    const count = parseInt(input.value || '0', 10) || 0;
                    const value = parseInt(input.dataset.value, 10) || 0;
                    sum += count * value;
                });
                if (total) {
                    total.value = sum;
                }
            };

            inputs.forEach((input) => input.addEventListener('input', compute));
            compute();
        })();
    </script>
@endpush
