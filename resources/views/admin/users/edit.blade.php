@extends('layouts.app')

@section('title', 'Ubah Pengguna — Bank Mini')
@section('section', 'Administrator — Ubah Pengguna')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Ubah Pengguna</h1>
        <p class="text-sm text-stone-500 mb-6">Perbarui data akun <strong>{{ $user->full_name }}</strong>.</p>

        <x-form-errors />

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="full_name">Nama Lengkap</x-input-label>
                <x-input id="full_name" name="full_name" :value="$user->full_name" required />
            </div>
            <div>
                <x-input-label for="username">Username</x-input-label>
                <x-input id="username" name="username" :value="$user->username" required />
            </div>
            <div>
                <x-input-label for="email">Email</x-input-label>
                <x-input id="email" name="email" type="email" :value="$user->email" required placeholder="Untuk verifikasi lupa password" />
            </div>
            <div>
                <x-input-label for="password">Password <span class="text-stone-500 font-normal">(kosongkan jika tidak diubah)</span></x-input-label>
                <x-input id="password" name="password" type="password" minlength="6" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="role">Role</x-input-label>
                    <x-select id="role" name="role" required>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                        <option value="teller" @selected(old('role', $user->role) === 'teller')>Teller</option>
                        <option value="supervisor" @selected(old('role', $user->role) === 'supervisor')>Supervisor</option>
                    </x-select>
                </div>
                <div>
                    <x-input-label for="status">Status</x-input-label>
                    <x-select id="status" name="status" required>
                        <option value="active" @selected(old('status', $user->status) === 'active')>Aktif</option>
                        <option value="blocked" @selected(old('status', $user->status) === 'blocked')>Diblokir</option>
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
