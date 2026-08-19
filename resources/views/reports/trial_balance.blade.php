@extends('layouts.app')

@section('title', 'Neraca Saldo — Bank Mini')
@section('section', request()->routeIs('supervisor.*') ? 'Supervisor' : 'Administrator')

@section('content')
    @php
        $isSupervisor = request()->routeIs('supervisor.*');
        $indexRoute = $isSupervisor ? route('supervisor.reports.trial-balance') : route('admin.reports.trial-balance');
        $exportRoute = $isSupervisor ? route('supervisor.reports.export', 'journals') : route('admin.reports.export', 'journals');
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Neraca Saldo (Trial Balance)</h1>
            <p class="text-sm text-gray-500 mt-1">Keseimbangan total debit dan kredit serta rasio likuiditas.</p>
        </div>
        <a href="{{ $exportRoute }}" class="text-sm bg-green-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            Ekspor Jurnal CSV
        </a>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end">
        <div>
            <label for="date" class="block text-xs font-medium text-gray-600 mb-1">Per Tanggal</label>
            <input type="date" id="date" name="date" value="{{ $date }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Tampilkan
        </button>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-900">Keseimbangan Jurnal</span>
                @if ($isBalanced)
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Seimbang</span>
                @else
                    <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">Tidak Seimbang</span>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500">Total Debet</p>
                    <p class="text-lg font-semibold text-gray-900">Rp {{ number_format($grandDebit, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Kredit</p>
                    <p class="text-lg font-semibold text-gray-900">Rp {{ number_format($grandCredit, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <span class="text-sm font-medium text-gray-900">Rasio Likuiditas</span>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <p class="text-xs text-gray-500">Total Kas Fisik (Laci + Brankas)</p>
                    <p class="text-lg font-semibold text-blue-600">Rp {{ number_format($totalPhysicalCash, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Kewajiban Simpanan Nasabah</p>
                    <p class="text-lg font-semibold text-gray-900">Rp {{ number_format($totalSavingsObligation, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-end justify-between border-t border-gray-100 pt-3">
                <span class="text-xs text-gray-500">Rasio (Kas / Kewajiban × 100%)</span>
                <span class="text-xl font-semibold text-blue-600">{{ number_format($liquidityRatio, 2, ',', '.') }}%</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Kode Akun</th>
                    <th class="px-5 py-3">Nama Akun</th>
                    <th class="px-5 py-3 text-right">Total Debit</th>
                    <th class="px-5 py-3 text-right">Total Kredit</th>
                    <th class="px-5 py-3 text-right">Saldo Debit</th>
                    <th class="px-5 py-3 text-right">Saldo Kredit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($balances as $code => $balance)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono">{{ $code }}</td>
                        <td class="px-5 py-3">{{ $balance['name'] }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($balance['total_debit'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($balance['total_credit'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $balance['net_debit'] ? 'text-blue-700' : '' }}">Rp {{ number_format($balance['net_debit'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $balance['net_credit'] ? 'text-blue-700' : '' }}">Rp {{ number_format($balance['net_credit'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr class="font-semibold">
                    <td colspan="4" class="px-5 py-3 text-right">Total</td>
                    <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($grandDebit, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($grandCredit, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
