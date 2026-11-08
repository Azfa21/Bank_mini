@extends('layouts.app')

@section('title', 'Daftar Penabung | Bank Mini')
@section('section', 'Administrator · Penabung')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Daftar Penabung</h1>
            <p class="text-sm text-stone-500 mt-1">Penabung otomatis aktif setelah anggota menyetor uang di Teller.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.savers.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
        <div class="sm:w-72">
            <x-input-label for="search">Cari penabung</x-input-label>
            <x-input id="search" name="search" type="search" :value="request('search')" placeholder="Nama, no. siswa, atau kelas" />
        </div>
        <div class="sm:w-48">
            <x-input-label for="status">Status</x-input-label>
            <x-select id="status" name="status">
                <option value="">Semua status</option>
                @foreach (['active' => 'Aktif', 'blocked' => 'Diblokir', 'graduated' => 'Alumni'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
        @if (request('search') || request('status'))
            <a href="{{ route('admin.savers.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-medium text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">Reset</a>
        @endif
    </form>

    @php
        // Alasan: tautan ke detail rekening membawa filter daftar ini supaya tombol kembali
        // di halaman detail mengembalikan administrator ke daftar yang sama, bukan ke daftar penuh.
        $listUrl = route('admin.savers.index').(request()->getQueryString() ? '?'.request()->getQueryString() : '');

        // Alasan: kolom "Aksi" ditempel di kanan supaya tombolnya tetap terjangkau saat tabel
        // digeser ke samping di layar sempit; kolom Angkatan dan Email dilepas karena keduanya
        // bisa dilihat di Kelola Anggota dan detail penabung.
        $headers = [
            ['label' => 'No. siswa'],
            ['label' => 'Nama'],
            ['label' => 'Kelas'],
            ['label' => 'No. rekening'],
            ['label' => 'Saldo', 'align' => 'right'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'align' => 'right', 'sticky' => true],
        ];
    @endphp

    <x-data-table :headers="$headers">
        @forelse ($savers as $saver)
            <tr class="hover:bg-stone-50">
                <td class="px-5 py-3 font-mono text-xs">{{ $saver->student_number }}</td>
                <td class="px-5 py-3">{{ $saver->full_name }}</td>
                <td class="px-5 py-3 text-stone-500">{{ $saver->class_name ?? '-' }}</td>
                <td class="px-5 py-3 font-mono text-xs">
                    @if ($saver->account)
                        <a href="{{ route('admin.accounts.show', $saver->account) }}?from={{ urlencode($listUrl) }}" class="text-brand-900 hover:underline">{{ $saver->account->account_number }}</a>
                    @else
                        <span class="text-stone-500">-</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right font-mono">
                    Rp {{ number_format($saver->account->balance ?? 0, 0, ',', '.') }}
                </td>
                <td class="px-5 py-3">
                    @php
                        $statusStyles = [
                            'active' => 'bg-olive-100 text-olive-700',
                            'blocked' => 'bg-red-100 text-red-700',
                            'graduated' => 'bg-stone-200 text-stone-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$saver->status] ?? 'bg-stone-100 text-stone-700' }}">
                        {{ $saver->statusLabel() }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right sticky right-0 bg-white border-l border-stone-200">
                    <div class="flex items-center justify-end gap-2">
                        @if ($saver->account)
                            <a href="{{ route('admin.accounts.card-print', $saver->account) }}" class="text-brand-700 hover:text-brand-800 text-xs">Kartu</a>
                        @endif
                        @if ($saver->isGraduated())
                            <span class="text-xs text-stone-500" title="Data penabung alumni tidak dapat diubah.">Terkunci</span>
                        @else
                            <a href="{{ route('admin.savers.edit', $saver) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                            <form method="POST" action="{{ route('admin.savers.toggle-status', $saver) }}" class="inline" data-confirm="{{ $saver->status === 'active' ? 'Blokir penabung ini? Penabung yang diblokir tidak dapat bertransaksi.' : 'Aktifkan kembali penabung ini?' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs {{ $saver->status === 'active' ? 'text-amber-700 hover:text-amber-900' : 'text-brand-800 hover:text-olive-800' }}">
                                    {{ $saver->status === 'active' ? 'Blokir' : 'Aktifkan' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-stone-500">
                @if (request('search') || request('status'))
                    Tidak ada penabung yang cocok dengan filter ini.
                @else
                    Belum ada data penabung.
                @endif
            </td></tr>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $savers->withQueryString()->links() }}</div>
@endsection
