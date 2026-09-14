@extends('layouts.app')

@section('title', 'Neraca — Bank Mini')
@section('section', request()->routeIs('supervisor.*') ? 'Supervisor' : 'Administrator')

@section('content')
    @php
        $isSupervisor = request()->routeIs('supervisor.*');
        $indexRoute = $isSupervisor ? route('supervisor.reports.balance-sheet') : route('admin.reports.balance-sheet');
    @endphp

    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Neraca (Balance Sheet)</h1>
        <p class="text-sm text-gray-500 mt-1">Aset, kewajiban, dan ekuitas Bank Mini.</p>
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

    <div class="mb-4">
        @if ($isBalanced)
            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">Neraca Seimbang</span>
        @else
            <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700">Neraca Tidak Seimbang</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-5 py-3">
                <h2 class="text-sm font-medium text-gray-900">Aset</h2>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($assets as $code => $asset)
                        <tr>
                            <td class="px-5 py-3"><span class="font-mono text-xs text-gray-400 mr-2">{{ $code }}</span>{{ $asset['name'] }}</td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($asset['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50 font-semibold">
                        <td class="px-5 py-3">Total Aset</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($totalAssets, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-3">
                    <h2 class="text-sm font-medium text-gray-900">Kewajiban</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($liabilities as $code => $liability)
                            <tr>
                                <td class="px-5 py-3"><span class="font-mono text-xs text-gray-400 mr-2">{{ $code }}</span>{{ $liability['name'] }}</td>
                                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($liability['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-5 py-3">Total Kewajiban</td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-3">
                    <h2 class="text-sm font-medium text-gray-900">Ekuitas</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($equity as $code => $item)
                            <tr>
                                <td class="px-5 py-3"><span class="font-mono text-xs text-gray-400 mr-2">{{ $code }}</span>{{ $item['name'] }}</td>
                                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-5 py-3">Total Ekuitas</td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($totalEquity, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900">Total Kewajiban + Ekuitas</span>
                <span class="text-lg font-semibold text-gray-900">Rp {{ number_format($totalLiabilities + $totalEquity, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
@endsection
