@extends('layouts.app')

@section('title', 'Kelulusan Angkatan | Bank Mini')
@section('section', 'Administrator · Kelulusan')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">ARSIP KELULUSAN</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Kelulusan angkatan</h1>
        <p class="text-sm text-stone-600 mt-1">Arsipkan angkatan yang sudah lulus. Penabung bersaldo nol ditutup, yang masih bersaldo diselesaikan di loket.</p>
    </div>

    <form method="GET" action="{{ route('admin.graduations.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="sm:w-60">
            <x-input-label for="year">Angkatan keluar</x-input-label>
            <x-select id="year" name="year">
                <option value="">Pilih angkatan</option>
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                @endforeach
            </x-select>
        </div>
        <x-primary-button>Tampilkan angkatan</x-primary-button>
    </form>

    @if ($year === null || $year === '')
        <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
            @if ($years->isEmpty())
                Belum ada data angkatan keluar. Isi kolom angkatan pada data anggota atau impor CSV agar kelulusan bisa diproses per angkatan.
            @else
                Pilih angkatan keluar untuk melihat daftar siswa dan status kelulusannya.
            @endif
        </div>
    @else
        @php
            $pendingCounter = $rows->where('state', 'pending_counter');
            $ready = $rows->whereIn('state', ['ready', 'roster_only']);
        @endphp

        <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div class="text-sm text-stone-700">
                Angkatan <span class="font-semibold text-stone-900">{{ $year }}</span>:
                <span class="tnum font-semibold text-stone-900">{{ $rows->count() }}</span> siswa,
                <span class="tnum font-semibold text-stone-900">{{ $ready->count() }}</span> siap diarsipkan,
                <span class="tnum font-semibold text-stone-900">{{ $pendingCounter->count() }}</span> menunggu loket.
            </div>
            @if ($ready->isNotEmpty())
                <form method="POST" action="{{ route('admin.graduations.store') }}" data-confirm="Proses kelulusan angkatan {{ $year }}? Anggota dan penabung bersaldo nol akan diarsipkan, penabung bersaldo tetap menunggu loket.">
                    @csrf
                    <input type="hidden" name="graduation_year" value="{{ $year }}">
                    <x-primary-button>Proses kelulusan angkatan</x-primary-button>
                </form>
            @elseif ($rows->isEmpty())
                <span class="text-sm text-stone-500">Tidak ada siswa pada angkatan ini.</span>
            @else
                <span class="text-sm text-stone-500">Semua siswa pada angkatan ini sudah diproses atau menunggu loket.</span>
            @endif
        </div>

        <x-data-table caption="Daftar siswa per angkatan dan status kelulusannya" :headers="[['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Rekening'], ['label' => 'Saldo', 'align' => 'right'], ['label' => 'Status kelulusan'], ['label' => 'Tindakan', 'align' => 'right']]">
            @forelse ($rows as $row)
                @php
                    $labels = [
                        'alumni' => ['Alumni', 'bg-stone-200 text-stone-700'],
                        'roster_only' => ['Siap diarsipkan', 'bg-brand-50 text-brand-900'],
                        'ready' => ['Siap ditutup', 'bg-brand-50 text-brand-900'],
                        'pending_approval' => ['Menunggu persetujuan', 'bg-amber-50 text-amber-900'],
                        'pending_counter' => ['Menunggu loket', 'bg-amber-50 text-amber-900'],
                    ];
                    [$stateLabel, $stateStyle] = $labels[$row['state']] ?? [$row['state'], 'bg-stone-100 text-stone-700'];
                    $keterangan = match ($row['state']) {
                        'roster_only' => 'Belum punya rekening penabung.',
                        'ready' => 'Saldo nol, aman ditutup dan diarsipkan.',
                        'pending_approval' => 'Penutupan menunggu persetujuan Administrator.',
                        'pending_counter' => 'Sisa saldo Rp '.number_format((int) $row['account']->balance, 0, ',', '.').', selesaikan di loket Teller.',
                        'alumni' => 'Sudah diarsipkan sebagai alumni.',
                        default => '-',
                    };
                @endphp
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3 font-mono text-xs">{{ $row['member']->nis }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-stone-900">{{ $row['member']->full_name }}</div>
                        <div class="text-xs text-stone-500">{{ $keterangan }}</div>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs">{{ $row['account']?->account_number ?? '-' }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format((int) ($row['account']?->balance ?? 0), 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[13px] font-semibold {{ $stateStyle }}">{{ $stateLabel }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if (in_array($row['state'], ['roster_only', 'ready'], true))
                            <form method="POST" action="{{ route('admin.graduations.member', $row['member']) }}" data-confirm="Arsipkan {{ $row['member']->full_name }} sebagai alumni sekarang?">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-brand-900 hover:underline">Luluskan</button>
                            </form>
                        @else
                            <span class="text-xs text-stone-600">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm text-stone-600">Tidak ada siswa pada angkatan ini.</td>
                </tr>
            @endforelse
        </x-data-table>
    @endif
@endsection
