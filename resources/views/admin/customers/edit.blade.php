@extends('layouts.app')

@section('title', 'Ubah Nasabah — Bank Mini')
@section('section', 'Administrator — Ubah Nasabah')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-gray-900 mb-1">Ubah Nasabah</h1>
        <p class="text-sm text-gray-500 mb-6">Perbarui data <strong>{{ $customer->full_name }}</strong>.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="student_number">Nomor Siswa</x-input-label>
                <x-input id="student_number" name="student_number" :value="$customer->student_number" required />
            </div>
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" :value="$customer->full_name" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="class_name">Kelas</x-input-label>
                    <x-input id="class_name" name="class_name" :value="$customer->class_name" />
                </div>
                <div>
                    <x-input-label for="phone_number">No. HP</x-input-label>
                    <x-input id="phone_number" name="phone_number" :value="$customer->phone_number" />
                </div>
            </div>
            <div>
                <x-input-label for="pin">PIN Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></x-input-label>
                <x-input id="pin" name="pin" type="password" pattern="\d{6}" maxlength="6" />
            </div>
            <div>
                <x-input-label for="status">Status</x-input-label>
                <x-select id="status" name="status" required>
                    <option value="active" @selected(old('status', $customer->status) === 'active')>Aktif</option>
                    <option value="blocked" @selected(old('status', $customer->status) === 'blocked')>Diblokir</option>
                </x-select>
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.customers.index')">Batal</x-secondary-button>
            </div>
        </form>
    </div>
@endsection
