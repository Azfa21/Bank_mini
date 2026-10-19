@extends('layouts.app')

@section('title', 'Penutupan Rekening | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">VALIDASI PENUTUPAN REKENING</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Penutupan rekening alumni</h1>
        <p class="text-sm text-stone-600 mt-1">Teller sudah mencairkan saldo di loket. Setujui untuk menjadikan penabung alumni, atau tolak agar saldo dikembalikan.</p>
    </div>

    <form method="GET" action="{{ route('admin.closures.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="sm:w-52">
            <x-input-label for="status">Status</x-input-label>
            <x-select id="status" name="status">
                <option value="">Semua status</option>
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="sm:w-64">
            <x-input-label for="search">Cari penabung</x-input-label>
            <x-input id="search" name="search" type="text" :value="request('search')" placeholder="Nama atau no. siswa" />
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
    </form>

    <x-data-table caption="Daftar permintaan penutupan rekening" :headers="[['label' => 'Penabung'], ['label' => 'No. rekening'], ['label' => 'Diajukan oleh'], ['label' => 'Tanggal'], ['label' => 'Nominal cair', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Tindakan', 'align' => 'right']]">
        @forelse ($closures as $closure)
            <tr class="hover:bg-stone-50">
                <td class="px-5 py-3">
                    <div class="font-medium text-stone-900">{{ $closure->saver?->full_name ?? '-' }}</div>
                    <div class="text-xs text-stone-500 font-mono">{{ $closure->saver?->student_number ?? '-' }}</div>
                </td>
                <td class="px-5 py-3 font-mono text-xs">{{ $closure->account?->account_number ?? '-' }}</td>
                <td class="px-5 py-3 text-stone-600">{{ $closure->requester?->full_name ?? '-' }}</td>
                <td class="px-5 py-3 tnum text-stone-600">{{ $closure->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                <td class="px-5 py-3 text-right tnum">Rp {{ number_format($closure->payout_amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $closure->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : ($closure->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900') }}">
                        {{ $closure->statusLabel() }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('admin.closures.show', $closure) }}" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-brand-900 hover:underline">Periksa</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-8 text-center text-sm text-stone-600">
                    @if (request('status') || request('search'))
                        Tidak ada permintaan penutupan untuk filter ini.
                    @else
                        Belum ada permintaan penutupan rekening. Permintaan dibuat Teller saat siswa lulus.
                    @endif
                </td>
            </tr>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $closures->withQueryString()->links() }}</div>
@endsection
