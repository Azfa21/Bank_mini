@extends('layouts.app')

@section('title', 'Konfigurasi Sistem — Bank Mini')
@section('section', 'Administrator · Konfigurasi')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-gray-900 mb-1">Konfigurasi Sistem</h1>
        <p class="text-sm text-gray-500 mb-6">Pengaturan global Bank Mini.</p>

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.config.update') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <x-input-label for="bank_name">Nama Bank Mini *</x-input-label>
                <x-input id="bank_name" name="bank_name" :value="$configs->get('bank_name')->config_value ?? 'Bank Mini Sekolah'" required />
                <p class="text-xs text-gray-400 mt-1">Ditampilkan pada struk transaksi dan laporan.</p>
                <x-input-error :message="$errors->first('bank_name')" />
            </div>

            <div>
                <x-input-label for="operational_hours">Jam Buka Operasional</x-input-label>
                <x-input name="operational_hours" id="operational_hours" :value="$configs->get('operational_hours')->config_value ?? '07.30 - 14.00 WIB'" />
                <p class="text-xs text-gray-400 mt-1">Informasi jam layanan yang ditampilkan pada dashboard.</p>
                <x-input-error :message="$errors->first('operational_hours')" />
            </div>

            <div>
                <x-input-label for="minimum_balance">Saldo Mengendap Minimum (Rp) *</x-input-label>
                <x-input id="minimum_balance" name="minimum_balance" type="number" :value="$configs->get('minimum_balance')->config_value ?? 10000" min="0" required />
                <p class="text-xs text-gray-400 mt-1">Penarikan tidak boleh menghasilkan saldo di bawah nilai ini.</p>
                <x-input-error :message="$errors->first('minimum_balance')" />
            </div>

            <div>
                <x-input-label for="admin_fee">Biaya Administrasi per Penarikan (Rp) *</x-input-label>
                <x-input id="admin_fee" name="admin_fee" type="number" :value="$configs->get('admin_fee')->config_value ?? 0" min="0" required />
                <p class="text-xs text-gray-400 mt-1">Biaya ini dicatat sebagai pendapatan (akun 401). Isi 0 jika tidak ada biaya.</p>
                <x-input-error :message="$errors->first('admin_fee')" />
            </div>

            <div>
                <x-input-label for="max_withdrawal_without_order">Batas Maksimal Penarikan Tanpa Pesanan (Rp) *</x-input-label>
                <x-input id="max_withdrawal_without_order" name="max_withdrawal_without_order" type="number" :value="$configs->get('max_withdrawal_without_order')->config_value ?? 0" min="0" required />
                <p class="text-xs text-gray-400 mt-1">Penarikan di atas nilai ini ditolak. Isi 0 untuk menonaktifkan batas.</p>
                <x-input-error :message="$errors->first('max_withdrawal_without_order')" />
            </div>

            <div class="pt-2">
                <x-primary-button>Simpan Konfigurasi</x-primary-button>
            </div>
        </form>
    </div>
@endsection
