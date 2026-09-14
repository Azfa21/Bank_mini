@extends('layouts.app')

@section('title', 'Transaksi Setoran — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Transaksi Setoran</h1>
            <p class="text-sm text-gray-500 mt-1">Periksa kelengkapan slip dan nominal uang fisik.</p>
        </div>
        <a href="{{ route('teller.customers.show', $account) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-500">Nasabah</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $account->customer->full_name }}</p>
                <p class="text-xs text-gray-500 mt-2">No. Siswa: <span class="font-mono">{{ $account->customer->student_number }}</span></p>
                <p class="text-xs text-gray-500 mt-1">No. Rekening: <span class="font-mono">{{ $account->account_number }}</span></p>
                <div class="border-t border-gray-100 mt-4 pt-4">
                    <p class="text-xs text-gray-500">Saldo Saat Ini</p>
                    <p class="text-xl font-semibold text-blue-600 mt-1">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-medium text-gray-900 mb-4">Data Setoran</h2>

                <form method="POST" action="{{ route('teller.deposits.store', $account) }}" class="space-y-4" id="deposit-form">
                    @csrf

                    <div>
                        <x-input-label for="slip_amount" size="xs">Nominal pada Slip *</x-input-label>
                        <x-input id="slip_amount" name="slip_amount" type="number" min="1" step="1" placeholder="Contoh: 100000" required />
                        <x-input-error :message="$errors->first('slip_amount')" />
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-medium text-gray-600 mb-1">Rincian Uang Fisik Diterima (opsional)</p>
                        <p class="text-xs text-gray-400 mb-3">Isi jumlah lembar/keping bila perlu; total dihitung otomatis.</p>
                        <x-denomination-input />
                    </div>

                    <div>
                        <x-input-label for="physical_cash_amount" size="xs">Nominal Uang Fisik (total) *</x-input-label>
                        <x-input id="physical_cash_amount" name="physical_cash_amount" type="number" min="1" step="1" placeholder="Hitung ulang uang yang diterima" required data-denomination-total />
                        <x-input-error :message="$errors->first('physical_cash_amount')" />
                    </div>

                    <x-primary-button variant="green" class="w-full py-2.5"
                        onclick="this.disabled = true; this.form.submit();">
                        Proses Setoran
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const inputs = document.querySelectorAll('[data-denomination-input]');
            const total = document.querySelector('[data-denomination-total]');
            if (!total) return;

            const compute = () => {
                let sum = 0;
                inputs.forEach((input) => {
                    sum += (parseInt(input.value || '0', 10) || 0) * (parseInt(input.dataset.value, 10) || 0);
                });
                if (sum > 0) total.value = sum;
            };

            inputs.forEach((input) => input.addEventListener('input', compute));
            compute();
        })();
    </script>
@endpush
