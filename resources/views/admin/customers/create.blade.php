@extends('layouts.app')

@section('title', 'Tambah Nasabah — Bank Mini')
@section('section', 'Administrator — Tambah Nasabah')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-gray-900 mb-1">Tambah Nasabah Baru</h1>
        <p class="text-sm text-gray-500 mb-6">Profil siswa akan otomatis mendapat rekening tabungan.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.customers.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label>Nomor Siswa</x-input-label>
                <p class="text-sm text-gray-500">Dibuat otomatis oleh sistem setelah nasabah disimpan.</p>
            </div>
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="class_name">Kelas</x-input-label>
                    <x-input id="class_name" name="class_name" />
                </div>
                <div>
                    <x-input-label for="phone_number">No. HP</x-input-label>
                    <x-input id="phone_number" name="phone_number" />
                </div>
            </div>
            <div>
                <x-input-label for="pin">PIN (6 digit)</x-input-label>
                <x-input id="pin" name="pin" type="password" required pattern="\d{6}" maxlength="6" />
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.customers.index')">Batal</x-secondary-button>
            </div>
        </form>
    </div>
@endsection
