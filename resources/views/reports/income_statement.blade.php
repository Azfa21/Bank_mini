@extends('layouts.app')

@section('title', 'Laporan Laba Rugi — Bank Mini')
@section('section', request()->routeIs('supervisor.*') ? 'Supervisor' : 'Administrator')

@section('content')
    @php
        $isSupervisor = request()->routeIs('supervisor.*');
        $indexRoute = $isSupervisor ? route('supervisor.reports.income-statement') : route('admin.reports.income-statement');
    @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Laporan Laba Rugi</h1>
        <p class="text-sm text-gray-500 mt-1">Pendapatan dikurangi beban sampai tanggal tertentu.</p>
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

    <div class="max-w-2xl space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-3">
                <h2 class="text-sm font-medium text-gray-900">Pendapatan</h2>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($incomes as $code => $income)
                        <tr>
                            <td class="px-5 py-3"><span class="font-mono text-xs text-gray-400 mr-2">{{ $code }}</span>{{ $income['name'] }}</td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($income['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-3 text-gray-500">Belum ada pendapatan.</td></tr>
                    @endforelse
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-5 py-3">Total Pendapatan</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-3">
                <h2 class="text-sm font-medium text-gray-900">Beban</h2>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($expenses as $code => $expense)
                        <tr>
                            <td class="px-5 py-3"><span class="font-mono text-xs text-gray-400 mr-2">{{ $code }}</span>{{ $expense['name'] }}</td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($expense['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-3 text-gray-500">Belum ada beban tercatat.</td></tr>
                    @endforelse
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-5 py-3">Total Beban</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-900">Laba (Rugi) Bersih</span>
            <span class="text-xl font-semibold {{ $netIncome >= 0 ? 'text-green-600' : 'text-red-600' }}">
                Rp {{ number_format($netIncome, 0, ',', '.') }}
            </span>
        </div>
    </div>
@endsection
