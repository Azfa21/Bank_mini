@extends('layouts.app')

@section('title', 'Tambah Pengguna | Bank Mini')
@section('section', 'Administrator · Tambah Pengguna')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Tambah Pengguna Baru</h1>
        <p class="text-sm text-stone-500 mb-6">Buat akun untuk Administrator atau Teller.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" required />
            </div>
            <div>
                <x-input-label for="username">Username</x-input-label>
                <x-input id="username" name="username" required />
            </div>
            <div>
                <x-input-label for="email">Email</x-input-label>
                <x-input id="email" name="email" type="email" required placeholder="Tempat kode verifikasi dikirim" />
            </div>
            <div>
                <x-input-label for="password">Password</x-input-label>
                <x-input id="password" name="password" type="password" required minlength="6" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="role">Peran</x-input-label>
                    <x-select id="role" name="role" required>
                        <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                        <option value="teller" @selected(old('role') === 'teller')>Teller</option>
                    </x-select>
                </div>
                <div>
                    <x-input-label for="status">Status</x-input-label>
                    <x-select id="status" name="status" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                        <option value="blocked" @selected(old('status') === 'blocked')>Diblokir</option>
                    </x-select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <x-primary-button>Simpan</x-primary-button>
                <x-secondary-button :href="route('admin.users.index')">Batal</x-secondary-button>
            </div>
        </form>
    </div>
@endsection
