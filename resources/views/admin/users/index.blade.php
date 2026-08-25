@extends('layouts.app')

@section('title', 'Kelola Pengguna â€” Bank Mini')
@section('section', 'Administrator Â· Pengguna')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Daftar Pengguna Internal</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola akun Administrator, Teller, dan Supervisor.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            + Tambah Pengguna
        </a>
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <select name="role" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Role</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="teller" {{ request('role') === 'teller' ? 'selected' : '' }}>Teller</option>
            <option value="supervisor" {{ request('role') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
        </select>
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
        </select>
        <button type="submit" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50">Filter</button>
    </form>

    <x-data-table :headers="[['label' => 'Nama'], ['label' => 'Username'], ['label' => 'Role'], ['label' => 'Status'], ['label' => 'Aksi', 'align' => 'right']]">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">{{ $user->full_name }}</td>
                        <td class="px-5 py-3 text-gray-500 font-mono">{{ $user->username }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($user->role === 'teller' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->status === 'active' ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-800 text-xs">Ubah</a>
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs {{ $user->status === 'active' ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800' }}">
                                        {{ $user->status === 'active' ? 'Blokir' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Hapus akun ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada data pengguna.</td></tr>
                @endforelse
</x-data-table>

    <div class="mt-4">{{ $users->withQueryString()->links() }}</div>
@endsection
