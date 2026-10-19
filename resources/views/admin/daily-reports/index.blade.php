@extends('layouts.app')

@section('title', 'Laporan Harian | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">VALIDASI KAS</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Laporan harian Teller</h1>
        <p class="text-sm text-stone-600 mt-1">Dahulukan status menunggu. Periksa arus kas sebelum menyetujui.</p>
    </div>

    <form method="GET" action="{{ route('admin.daily-reports.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="sm:w-48">
            <x-input-label for="status">Status</x-input-label>
            <x-select id="status" name="status">
                <option value="">Semua status</option>
                @foreach (['draft' => 'Draf', 'submitted' => 'Menunggu validasi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="sm:w-56">
            <x-input-label for="report_date">Tanggal kerja</x-input-label>
            <x-input id="report_date" name="report_date" type="date" :value="request('report_date')" />
        </div>
        <x-primary-button>
            Tampilkan laporan
        </x-primary-button>
    </form>

    <x-data-table caption="Daftar laporan harian teller" :headers="[['label' => 'Nomor laporan'], ['label' => 'Teller'], ['label' => 'Tanggal'], ['label' => 'Saldo sistem', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Tindakan', 'align' => 'right']]">
                @forelse ($reports as $report)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 tnum font-medium">{{ $report->report_number }}</td>
                        <td class="px-5 py-3">{{ $report->teller->full_name }}</td>
                        <td class="px-5 py-3 tnum">{{ $report->report_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-right tnum">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1.5 text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $report->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : ($report->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900') }}">
                                {{ $report->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.daily-reports.show', $report) }}" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-brand-900 hover:underline">Periksa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada laporan untuk filter ini. Ubah status atau tanggal.</td>
                    </tr>
                @endforelse
</x-data-table>

    <div class="mt-4">
        {{ $reports->links() }}
    </div>
@endsection
