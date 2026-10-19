@extends('layouts.app')

@section('title', 'Kelola Pengguna | Bank Mini')
@section('section', 'Administrator · Pengguna')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Daftar Pengguna Internal</h1>
            <p class="text-sm text-stone-500 mt-1">Kelola akun Administrator dan Teller.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
            Tambah pengguna
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
        <div class="sm:w-48">
            <x-input-label for="role">Peran</x-input-label>
            <x-select id="role" name="role">
                <option value="">Semua peran</option>
                @foreach (['admin' => 'Administrator', 'teller' => 'Teller'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="sm:w-48">
            <x-input-label for="status">Status</x-input-label>
            <x-select id="status" name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="blocked" @selected(request('status') === 'blocked')>Diblokir</option>
            </x-select>
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
        @if (request('role') || request('status'))
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-medium text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">Reset</a>
        @endif
    </form>

    <x-data-table :headers="[['label' => 'Nama'], ['label' => 'Username'], ['label' => 'Email'], ['label' => 'Peran'], ['label' => 'Status'], ['label' => 'Aksi', 'align' => 'right', 'sticky' => true]]">
                @forelse ($users as $user)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3">{{ $user->full_name }}</td>
                        <td class="px-5 py-3 text-stone-500 font-mono">{{ $user->username }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $user->email ?? '-' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->role === 'admin' ? 'bg-brand-100 text-brand-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $user->role === 'admin' ? 'Administrator' : 'Teller' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->status === 'active' ? 'bg-olive-100 text-olive-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->status === 'active' ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right sticky right-0 bg-white border-l border-stone-200">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs {{ $user->status === 'active' ? 'text-amber-700 hover:text-amber-900' : 'text-brand-800 hover:text-olive-800' }}">
                                        {{ $user->status === 'active' ? 'Blokir' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm="Hapus akun ini?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-stone-500">Belum ada data pengguna.</td></tr>
                @endforelse
</x-data-table>

    <div class="mt-4">{{ $users->withQueryString()->links() }}</div>
@endsection
