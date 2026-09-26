@extends('layouts.app')

@section('title', 'Kelola Anggota — Bank Mini')
@section('section', 'Administrator · Anggota')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Kelola Anggota</h1>
            <p class="text-sm text-stone-500 mt-1">Data siswa yang belum menabung atau belum mengaktifkan rekening.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.members.import.form') }}" class="bg-white border border-stone-300 text-stone-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-stone-50 transition-colors">
                Import CSV
            </a>
            <a href="{{ route('admin.members.create') }}" class="bg-brand-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-900 transition-colors">
                + Tambah Anggota
            </a>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIS, nama, atau angkatan keluar..."
            class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 flex-1 min-w-[200px] focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
        <select name="status" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Status</option>
            <option value="account_inactive" {{ request('status') === 'account_inactive' ? 'selected' : '' }}>Rekening tidak aktif</option>
            <option value="account_active" {{ request('status') === 'account_active' ? 'selected' : '' }}>Rekening aktif</option>
        </select>
        <button type="submit" class="bg-brand-800 text-white text-sm font-medium px-4 py-1.5 rounded-lg hover:bg-brand-900 transition-colors">Filter</button>
        @if (request('search') || request('status'))
            <a href="{{ route('admin.members.index') }}" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 hover:bg-stone-50">Reset</a>
        @endif
    </form>

    <x-data-table :headers="[['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Angkatan Keluar'], ['label' => 'Status Rekening'], ['label' => 'Aksi', 'align' => 'right']]">
        @forelse ($members as $member)
            <tr class="hover:bg-stone-50">
                <td class="px-5 py-3 font-mono text-xs">{{ $member->nis }}</td>
                <td class="px-5 py-3">{{ $member->full_name }}</td>
                <td class="px-5 py-3 text-stone-500">{{ $member->graduation_year ?? '-' }}</td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $member->isAccountActive() ? 'bg-olive-100 text-olive-700' : 'bg-amber-100 text-amber-800' }}">
                        {{ $member->isAccountActive() ? 'Rekening aktif' : 'Rekening tidak aktif' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.members.edit', $member) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                        <form method="POST" action="{{ route('admin.members.destroy', $member) }}" class="inline" data-confirm="Hapus anggota ini?">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-700 hover:text-red-900">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-stone-500">
                @if (request('search'))
                    Tidak ada anggota yang cocok dengan pencarian "{{ request('search') }}".
                @else
                    Belum ada data anggota.
                @endif
            </td></tr>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $members->withQueryString()->links() }}</div>
@endsection
