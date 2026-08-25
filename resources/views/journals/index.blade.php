@extends('layouts.app')

@section('title', 'Audit Jurnal — Bank Mini')
@section('section', $userRole === 'admin' ? 'Administrator' : 'Supervisor')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Audit Jurnal Akuntansi</h1>
            <p class="text-sm text-gray-500 mt-1">Jurnal double-entry setiap transaksi setoran, penarikan, dan transfer.</p>
        </div>
        <a href="{{ $userRole === 'admin' ? route('admin.reports.export', 'journals') : route('supervisor.reports.export', 'journals') }}"
            class="text-sm bg-green-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            Ekspor CSV
        </a>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end">
        <div>
            <x-input-label for="transaction_number" size="xs">Nomor Transaksi</x-input-label>
            <x-input id="transaction_number" name="transaction_number" :value="request('transaction_number')" :block="false" placeholder="TRX-..." />
        </div>
        <div>
            <x-input-label for="account_code" size="xs">Kode Akun</x-input-label>
            <x-select id="account_code" name="account_code" :block="false">
                <option value="">Semua</option>
                <option value="101" @selected(request('account_code') === '101')>101 — Kas</option>
                <option value="201" @selected(request('account_code') === '201')>201 — Tabungan</option>
            </x-select>
        </div>
        <div>
            <x-input-label for="date_from" size="xs">Dari Tanggal</x-input-label>
            <x-input id="date_from" name="date_from" type="date" :value="request('date_from')" :block="false" />
        </div>
        <div>
            <x-input-label for="date_to" size="xs">Sampai Tanggal</x-input-label>
            <x-input id="date_to" name="date_to" type="date" :value="request('date_to')" :block="false" />
        </div>
        <x-primary-button>
            Filter
        </x-primary-button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Baris Jurnal</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $journals->total() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Debit</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Total Kredit</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
        </div>
    </div>

    <x-data-table :headers="[['label' => 'Nomor Jurnal'], ['label' => 'Transaksi'], ['label' => 'Tanggal'], ['label' => 'Akun'], ['label' => 'Jenis'], ['label' => 'Debit', 'align' => 'right'], ['label' => 'Kredit', 'align' => 'right'], ['label' => 'Keterangan']]">
                @forelse ($journals as $journal)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono">{{ $journal->journal_number }}</td>
                        <td class="px-5 py-3 font-mono">{{ $journal->transaction?->receipt_number }}</td>
                        <td class="px-5 py-3">{{ $journal->journal_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 font-mono">{{ $journal->account_code }}</td>
                        <td class="px-5 py-3">
                            <span class="text-xs px-2 py-1 rounded-full {{ $journal->entry_type === 'debit' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($journal->entry_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($journal->debit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($journal->credit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $journal->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada jurnal.</td>
                    </tr>
                @endforelse
</x-data-table>

    <div class="mt-4">
        {{ $journals->links() }}
    </div>
@endsection
