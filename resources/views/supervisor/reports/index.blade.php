@extends('layouts.app')

@section('title', 'Laporan Harian — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Laporan Harian Teller</h1>
            <p class="text-sm text-gray-500 mt-1">Periksa, setujui, atau tolak laporan penutupan kas.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('supervisor.reports.index') }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end">
        <div>
            <x-input-label for="status" size="xs">Status</x-input-label>
            <x-select id="status" name="status" :block="false">
                <option value="">Semua</option>
                @foreach (['draft', 'submitted', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-select>
        </div>
        <div>
            <x-input-label for="report_date" size="xs">Tanggal Kerja</x-input-label>
            <x-input id="report_date" name="report_date" type="date" :value="request('report_date')" :block="false" />
        </div>
        <x-primary-button>
            Filter
        </x-primary-button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Nomor Laporan</th>
                    <th class="px-5 py-3">Teller</th>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3 text-right">Saldo Sistem</th>
                    <th class="px-5 py-3 text-right">Uang Fisik</th>
                    <th class="px-5 py-3 text-right">Selisih</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reports as $report)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono">{{ $report->report_number }}</td>
                        <td class="px-5 py-3">{{ $report->teller->full_name }}</td>
                        <td class="px-5 py-3">{{ $report->report_date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($report->closing_balance_system, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($report->closing_balance_physical ?? 0, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $report->cash_difference === 0 ? 'text-green-600' : 'text-red-600' }}">
                            Rp {{ number_format($report->cash_difference ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="text-xs px-2 py-1 rounded-full {{ $report->status === 'approved' ? 'bg-green-100 text-green-700' : ($report->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('supervisor.reports.show', $report) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Periksa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada laporan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $reports->links() }}
    </div>
@endsection
