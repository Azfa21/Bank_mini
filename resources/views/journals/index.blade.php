@extends('layouts.app')

@section('title', 'Audit Jurnal — Bank Mini')
@section('section', $userRole === 'admin' ? 'Administrator' : 'Supervisor')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">DOUBLE-ENTRY</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Audit jurnal</h1>
            <p class="text-sm text-stone-600 mt-1">Setiap transaksi wajib dua baris seimbang: debit sama dengan kredit.</p>
        </div>
        <a href="{{ $userRole === 'admin' ? route('admin.reports.export', 'journals') : route('supervisor.reports.export', 'journals') }}"
            class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors sm:shrink-0">
            Unduh CSV
        </a>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
        <div class="sm:w-52">
            <x-input-label for="transaction_number">Nomor transaksi</x-input-label>
            <x-input id="transaction_number" name="transaction_number" :value="request('transaction_number')" placeholder="Contoh: TRX-..." />
        </div>
        <div class="sm:w-48">
            <x-input-label for="account_code">Kode akun</x-input-label>
            <x-select id="account_code" name="account_code">
                <option value="">Semua akun</option>
                <option value="101" @selected(request('account_code') === '101')>101, Kas</option>
                <option value="201" @selected(request('account_code') === '201')>201, Tabungan</option>
            </x-select>
        </div>
        <div>
            <x-input-label for="date_from">Dari tanggal</x-input-label>
            <x-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
        </div>
        <div>
            <x-input-label for="date_to">Sampai tanggal</x-input-label>
            <x-input id="date_to" name="date_to" type="date" :value="request('date_to')" />
        </div>
        <x-primary-button>
            Tampilkan jurnal
        </x-primary-button>
    </form>

    <section aria-label="Total jurnal" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4">
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Baris jurnal</dt>
            <dd class="text-2xl font-bold text-stone-900 mt-1 tnum">{{ $journals->total() }}</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Total debit</dt>
            <dd class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totalDebit, 0, ',', '.') }}</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Total kredit</dt>
            <dd class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totalCredit, 0, ',', '.') }}</dd>
        </dl>
    </section>

    <x-data-table caption="Audit jurnal akuntansi" :headers="[['label' => 'Nomor jurnal'], ['label' => 'Transaksi'], ['label' => 'Tanggal'], ['label' => 'Akun'], ['label' => 'Jenis'], ['label' => 'Debit', 'align' => 'right'], ['label' => 'Kredit', 'align' => 'right'], ['label' => 'Keterangan']]">
                @forelse ($journals as $journal)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 tnum font-medium">{{ $journal->journal_number }}</td>
                        <td class="px-5 py-3 tnum">{{ $journal->transaction?->receipt_number }}</td>
                        <td class="px-5 py-3 tnum">{{ $journal->journal_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 tnum">{{ $journal->account_code }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $journal->entry_type === 'debit' ? 'bg-stone-900 text-white border-stone-900' : 'bg-white text-stone-700 border-stone-300' }}">
                                {{ ucfirst($journal->entry_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right tnum">Rp {{ number_format($journal->debit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tnum">Rp {{ number_format($journal->credit_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-stone-600">{{ $journal->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada jurnal untuk filter ini.</td>
                    </tr>
                @endforelse
</x-data-table>

    <div class="mt-4">
        {{ $journals->links() }}
    </div>
@endsection
