@extends('layouts.app')

@section('title', 'Dashboard Nasabah — Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Selamat Datang</h1>
        <p class="text-sm text-gray-500 mt-1">Lihat saldo dan riwayat mutasi rekening Anda.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Saldo Rekening</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">—</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Terakhir Transaksi</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">—</div>
        </div>
    </div>
@endsection
