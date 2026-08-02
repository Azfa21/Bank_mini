@extends('layouts.app')

@section('title', 'Transaksi Penarikan — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Transaksi Penarikan</h1>
            <p class="text-sm text-gray-500 mt-1">Pastikan saldo cukup dan PIN nasabah diverifikasi.</p>
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
                    <p class="text-xs text-gray-400 mt-1">Saldo minimum: Rp {{ number_format((int) (\App\Models\SystemConfig::find('minimum_balance')->config_value ?? 10000), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-sm font-medium text-gray-900 mb-4">Data Penarikan</h2>

                <form method="POST" action="{{ route('teller.withdrawals.store', $account) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="slip_number" class="block text-xs font-medium text-gray-600 mb-1">Nomor Slip *</label>
                        <input type="text" id="slip_number" name="slip_number" value="{{ old('slip_number') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Contoh: SLIP-20260802-002" required>
                        @error('slip_number')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="amount" class="block text-xs font-medium text-gray-600 mb-1">Nominal Penarikan *</label>
                        <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="1" step="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Contoh: 50000" required>
                        @error('amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pin" class="block text-xs font-medium text-gray-600 mb-1">PIN Nasabah (6 digit) *</label>
                        <input type="password" id="pin" name="pin" value="" inputmode="numeric" autocomplete="off" maxlength="6"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 tracking-widest"
                            placeholder="••••••" required>
                        @error('pin')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-red-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-red-700 transition-colors"
                        onclick="this.disabled = true; this.form.submit();">
                        Proses Penarikan
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
