@extends('layouts.app')

@section('title', 'Buku Besar — Bank Mini')
@section('section', request()->routeIs('supervisor.*') ? 'Supervisor' : 'Administrator')

@section('content')
    @php
        $isSupervisor = request()->routeIs('supervisor.*');
        $indexRoute = $isSupervisor ? route('supervisor.reports.general-ledger') : route('admin.reports.general-ledger');
        $exportRoute = $isSupervisor ? route('supervisor.reports.export', 'general_ledger') : route('admin.reports.export', 'general_ledger');
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Buku Besar (General Ledger)</h1>
            <p class="text-sm text-gray-500 mt-1">Mutasi jurnal per kode akun dengan saldo akhir.</p>
        </div>
        <a href="{{ $exportRoute }}?account_code={{ $accountCode }}" class="text-sm bg-green-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            Ekspor CSV
        </a>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end">
        <div>
            <x-input-label for="account_code" size="xs">Kode Akun</x-input-label>
            <x-select id="account_code" name="account_code" :block="false">
                @foreach ($accounts as $code => $info)
                    <option value="{{ $code }}" @selected((string) $accountCode === (string) $code)>{{ $code }} — {{ $info['name'] }}</option>
                @endforeach
            </x-select>
        </div>
        <div>
            <x-input-label for="date_from" size="xs">Dari Tanggal</x-input-label>
            <x-input id="date_from" name="date_from" type="date" :value="$dateFrom" :block="false" />
        </div>
        <div>
            <x-input-label for="date_to" size="xs">Sampai Tanggal</x-input-label>
            <x-input id="date_to" name="date_to" type="date" :value="$dateTo" :block="false" />
        </div>
        <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Filter
        </button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Akun</div>
            <div class="text-lg font-semibold text-gray-900 mt-1">{{ $accountCode }} — {{ $accounts[$accountCode]['name'] ?? 'Akun '.$accountCode }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Debit</div>
            <div class="text-xl font-semibold text-gray-900 mt-1">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Kredit</div>
            <div class="text-xl font-semibold text-gray-900 mt-1">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
        </div>
    </div>

    @if ($endingBalance < 0)
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-6">
            Peringatan: saldo akhir akun ini negatif. Untuk akun kas, artinya mutasi jurnal pengeluaran melebihi penerimaan — segera periksa riwayat jurnal dan lakukan rekonsiliasi fisik.
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-500">Saldo Akhir Buku Besar ({{ $accounts[$accountCode]['normal'] === 'debit' ? 'Normal Debit' : 'Normal Kredit' }})</span>
            <span class="text-xl font-semibold {{ $endingBalance < 0 ? 'text-red-600' : 'text-blue-600' }}">Rp {{ number_format($endingBalance, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">Nomor Jurnal</th>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Referensi Transaksi</th>
                    <th class="px-5 py-3 text-right">Debet</th>
                    <th class="px-5 py-3 text-right">Kredit</th>
                    <th class="px-5 py-3">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($entries as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono">{{ $entry->journal_number }}</td>
                        <td class="px-5 py-3">{{ $entry->journal_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 font-mono">{{ $entry->transaction?->receipt_number ?? 'TRF-'.$entry->cash_transfer_id }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($entry->debit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($entry->credit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $entry->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada mutasi jurnal untuk akun ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
@endsection
