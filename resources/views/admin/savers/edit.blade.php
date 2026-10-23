@extends('layouts.app')

@section('title', 'Ubah Penabung | Bank Mini')
@section('section', 'Administrator · Ubah Penabung')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Ubah Penabung</h1>
        <p class="text-sm text-stone-500 mb-6">Perbarui data <strong>{{ $saver->full_name }}</strong>.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.savers.update', $saver) }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="student_number">Nomor Siswa</x-input-label>
                <x-input id="student_number" name="student_number" :value="$saver->student_number" required />
            </div>
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" :value="$saver->full_name" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="class_name">Kelas</x-input-label>
                    <x-input id="class_name" name="class_name" :value="$saver->class_name" list="kelas-opsi" placeholder="Contoh: XI RPL 2" />
                </div>
                <div>
                    <x-input-label for="phone_number">No. HP</x-input-label>
                    <x-input id="phone_number" name="phone_number" :value="$saver->phone_number" />
                </div>
            </div>
            <div>
                <x-input-label for="email">Email</x-input-label>
                <x-input id="email" name="email" type="email" :value="$saver->email" required placeholder="Tempat kode verifikasi dikirim" />
            </div>
            <div>
                <x-input-label for="pin">PIN Transaksi Baru <span class="text-stone-500 font-normal">(kosongkan jika tidak diubah)</span></x-input-label>
                <x-input id="pin" name="pin" type="password" pattern="\d{6}" maxlength="6" aria-describedby="pin-help" />
                <p id="pin-help" class="text-[13px] text-stone-600 mt-1.5">
                    Mengisi kolom ini langsung mengganti PIN transaksi yang dipakai di loket.
                    Password login penabung tidak terpengaruh. Alasan di bawah wajib diisi
                    dan tersimpan di log audit.
                </p>
            </div>
            <div>
                <x-input-label for="reset_reason">Alasan Reset PIN</x-input-label>
                <textarea id="reset_reason" name="reset_reason" rows="2" maxlength="255"
                    class="w-full px-4 py-3 text-[15px] text-stone-900 placeholder:text-stone-500 bg-white border border-stone-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-800 focus:border-brand-800"
                    placeholder="Contoh: siswa lupa PIN, kartu rekening dibawa sendiri">{{ old('reset_reason') }}</textarea>
                <x-input-error :message="$errors->first('reset_reason')" />
            </div>
            <div>
                <x-input-label for="status">Status</x-input-label>
                <x-select id="status" name="status" required>
                    <option value="active" @selected(old('status', $saver->status) === 'active')>Aktif</option>
                    <option value="blocked" @selected(old('status', $saver->status) === 'blocked')>Diblokir</option>
                </x-select>
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.savers.index')">Batal</x-secondary-button>
            </div>
        </form>

        <x-class-datalist />
    </div>
@endsection
