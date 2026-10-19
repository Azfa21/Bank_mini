@extends('layouts.app')

@section('title', 'Ubah Anggota | Bank Mini')
@section('section', 'Administrator · Ubah Anggota')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Ubah Anggota</h1>
        <p class="text-sm text-stone-500 mb-6">Perbarui data <strong>{{ $member->full_name }}</strong>. Status rekening diatur sistem lewat aktivasi di Teller.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.members.update', $member) }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="nis">NIS</x-input-label>
                <x-input id="nis" name="nis" :value="$member->nis" required />
            </div>
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" :value="$member->full_name" required />
            </div>
            <div>
                <x-input-label for="class_name">Kelas</x-input-label>
                <x-input id="class_name" name="class_name" :value="$member->class_name" list="kelas-opsi" placeholder="Contoh: X RPL 1" aria-describedby="class-name-help" />
                <p id="class-name-help" class="text-[13px] text-stone-600 mt-1.5">Dipakai sebagai isian awal saat anggota mengaktivasi rekening di loket.</p>
            </div>
            <div>
                <x-input-label for="graduation_year">Angkatan Keluar</x-input-label>
                <x-input id="graduation_year" name="graduation_year" :value="$member->graduation_year" />
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.members.index')">Batal</x-secondary-button>
            </div>
        </form>

        <x-class-datalist />
    </div>
@endsection
