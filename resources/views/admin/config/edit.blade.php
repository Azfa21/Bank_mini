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
                <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Mengendap Minimum (Rp)</label>
                <input type="number" name="minimum_balance" value="{{ $configs->get('minimum_balance')->config_value ?? 10000 }}" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-400 mt-1">Penarikan tidak boleh menghasilkan saldo di bawah nilai ini.</p>
            </div>
            <div class="pt-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">Simpan Konfigurasi</button>
            </div>
        </form>
    </div>
@endsection
