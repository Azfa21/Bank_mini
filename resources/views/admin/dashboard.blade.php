@extends('layouts.app')

@section('title', 'Dashboard Admin — Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard Administrator</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola pengguna, rekening, dan konfigurasi sistem.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm text-gray-500">Pengguna Internal</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ \App\Models\User::count() }}</div>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm text-gray-500">Nasabah Aktif</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ \App\Models\Customer::where('status', 'active')->count() }}</div>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm text-gray-500">Rekening Aktif</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ \App\Models\Account::where('status', 'active')->count() }}</div>
        </a>
        <a href="{{ route('admin.config.edit') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm text-gray-500">Saldo Minimum</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">Rp {{ number_format((int) (\App\Models\SystemConfig::find('minimum_balance')->config_value ?? 10000), 0, ',', '.') }}</div>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Kelola Pengguna</div>
            <div class="text-xs text-gray-500 mt-1">Tambah, ubah, blokir, dan hapus akun internal.</div>
        </a>
        <a href="{{ route('admin.customers.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Kelola Nasabah</div>
            <div class="text-xs text-gray-500 mt-1">Profil siswa, rekening, dan saldo.</div>
        </a>
        <a href="{{ route('admin.config.edit') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 transition-colors">
            <div class="text-sm font-medium text-gray-900">Konfigurasi Sistem</div>
            <div class="text-xs text-gray-500 mt-1">Saldo minimum dan pengaturan QR Code.</div>
        </a>
    </div>
@endsection
