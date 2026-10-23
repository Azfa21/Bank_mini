@extends('layouts.app')

@section('title', 'Kelola Anggota | Bank Mini')
@section('section', 'Administrator · Anggota')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Kelola Anggota</h1>
            <p class="text-sm text-stone-500 mt-1">Data siswa yang belum punya rekening penabung.</p>
        </div>
        <div class="flex gap-2">
            <x-secondary-button :href="route('admin.members.import.form')">Impor CSV</x-secondary-button>
            <a href="{{ route('admin.members.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                Tambah anggota
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.members.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
        <div class="sm:w-80">
            <x-input-label for="search">Cari anggota</x-input-label>
            <x-input id="search" name="search" type="search" :value="request('search')" placeholder="NIS, nama, kelas, atau angkatan keluar" />
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
        @if (request('search'))
            <a href="{{ route('admin.members.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-medium text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">Reset</a>
        @endif
    </form>

    <x-data-table :headers="[['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Kelas'], ['label' => 'Angkatan keluar'], ['label' => 'Aksi', 'align' => 'right']]">
        @forelse ($members as $member)
            <tr class="hover:bg-stone-50">
                <td class="px-5 py-3 font-mono text-xs">{{ $member->nis }}</td>
                <td class="px-5 py-3">{{ $member->full_name }}</td>
                <td class="px-5 py-3 text-stone-500">{{ $member->class_name ?? '-' }}</td>
                <td class="px-5 py-3 text-stone-500">{{ $member->graduation_year ?? '-' }}</td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.members.edit', $member) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-stone-500">
                @if (request('search'))
                    Tidak ada anggota yang cocok dengan pencarian "{{ request('search') }}".
                @elseif ($hasMembers)
                    Semua anggota sudah punya rekening penabung. Datanya di <a href="{{ route('admin.savers.index') }}" class="text-brand-900 hover:underline">Kelola Penabung</a>.
                @else
                    Belum ada data anggota. Tambahkan satu per satu lewat tombol di atas, atau impor dari CSV.
                @endif
            </td></tr>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $members->withQueryString()->links() }}</div>
@endsection
