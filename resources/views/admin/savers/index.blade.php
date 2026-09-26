@extends('layouts.app')

@section('title', 'Daftar Penabung — Bank Mini')
@section('section', 'Administrator · Penabung')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Daftar Penabung</h1>
            <p class="text-sm text-stone-500 mt-1">Penabung lahir otomatis saat anggota menyetor aktivasi di Teller.</p>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, no. siswa, atau kelas..."
            class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 flex-1 min-w-[200px] focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
        <select name="status" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
        </select>
        <button type="submit" class="bg-brand-800 text-white text-sm font-medium px-4 py-1.5 rounded-lg hover:bg-brand-900 transition-colors">Filter</button>
        @if (request('search') || request('status'))
            <a href="{{ route('admin.savers.index') }}" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 hover:bg-stone-50">Reset</a>
        @endif
    </form>

    <x-data-table :headers="[['label' => 'No. Siswa'], ['label' => 'Nama'], ['label' => 'Kelas'], ['label' => 'Email'], ['label' => 'No. Rekening'], ['label' => 'Saldo', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Aksi', 'align' => 'right']]">
                @forelse ($savers as $saver)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 font-mono text-xs">{{ $saver->student_number }}</td>
                        <td class="px-5 py-3">{{ $saver->full_name }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $saver->class_name ?? '-' }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $saver->email ?? '-' }}</td>
                        <td class="px-5 py-3 font-mono text-xs">
                            @if ($saver->account)
                                <a href="{{ route('admin.accounts.show', $saver->account) }}" class="text-brand-900 hover:underline">{{ $saver->account->account_number }}</a>
                            @else
                                <span class="text-stone-500">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-mono">
                            Rp {{ number_format($saver->account->balance ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $saver->status === 'active' ? 'bg-olive-100 text-olive-700' : 'bg-red-100 text-red-700' }}">
                                {{ $saver->status === 'active' ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($saver->account)
                                    <a href="{{ route('admin.accounts.card-print', $saver->account) }}" class="text-brand-700 hover:text-brand-800 text-xs">Kartu</a>
                                @endif
                                <a href="{{ route('admin.savers.edit', $saver) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                                <form method="POST" action="{{ route('admin.savers.toggle-status', $saver) }}" class="inline" data-confirm="{{ $saver->status === 'active' ? 'Blokir penabung ini?' : 'Aktifkan kembali penabung ini?' }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs {{ $saver->status === 'active' ? 'text-amber-700 hover:text-amber-900' : 'text-brand-800 hover:text-olive-800' }}">
                                        {{ $saver->status === 'active' ? 'Blokir' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.savers.destroy', $saver) }}" class="inline" data-confirm="Hapus penabung ini? Histori transaksi tetap tersimpan dan tetap tampil di laporan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-700 hover:text-red-900">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-stone-500">
                        @if (request('search'))
                            Tidak ada penabung yang cocok dengan pencarian "{{ request('search') }}".
                        @else
                            Belum ada data penabung.
                        @endif
                    </td></tr>
                @endforelse
</x-data-table>

    <div class="mt-4">{{ $savers->withQueryString()->links() }}</div>
@endsection
