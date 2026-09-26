@extends('layouts.app')

@section('title', 'Tambah Anggota — Bank Mini')
@section('section', 'Administrator · Tambah Anggota')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Tambah Anggota</h1>
        <p class="text-sm text-stone-500 mb-6">Anggota belum memiliki rekening sampai menyetor aktivasi di Teller.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.members.store') }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="nis">NIS</x-input-label>
                <x-input id="nis" name="nis" :value="old('nis')" required />
            </div>
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" :value="old('full_name')" required />
            </div>
            <div>
                <x-input-label for="graduation_year">Angkatan Keluar <span class="text-stone-500 font-normal">(opsional)</span></x-input-label>
                <x-input id="graduation_year" name="graduation_year" :value="old('graduation_year')" />
            </div>
            <div>
                <x-input-label for="status">Status Rekening</x-input-label>
                <x-select id="status" name="status">
                    <option value="account_inactive" @selected(old('status', 'account_inactive') === 'account_inactive')>Rekening tidak aktif</option>
                    <option value="account_active" @selected(old('status') === 'account_active')>Rekening aktif</option>
                </x-select>
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.members.index')">Batal</x-secondary-button>
            </div>
        </form>
    </div>
@endsection
