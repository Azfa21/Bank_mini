@extends('layouts.app')

@section('title', 'Ubah Pengguna — Bank Mini')
@section('section', 'Administrator · Ubah Pengguna')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-gray-900 mb-1">Ubah Pengguna</h1>
        <p class="text-sm text-gray-500 mb-6">Perbarui data akun <strong>{{ $user->full_name }}</strong>.</p>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="teller" {{ old('role', $user->role) === 'teller' ? 'selected' : '' }}>Teller</option>
                        <option value="supervisor" {{ old('role', $user->role) === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="blocked" {{ old('status', $user->status) === 'blocked' ? 'selected' : '' }}>Diblokir</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">Simpan</button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
            </div>
        </form>
    </div>
@endsection
