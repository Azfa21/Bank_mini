@extends('layouts.app')

@section('title', 'Buku Besar & Neraca Saldo | Bank Mini')
@section('section', 'Administrator')

@section('content')
    @php
        $indexRoute = route('admin.reports.general-ledger');
        $accountName = $accounts[$accountCode]['name'] ?? 'Akun '.$accountCode;
        $balanceDateLabel = \Illuminate\Support\Carbon::parse($balanceDate)->format('d/m/Y');
        $dateFromLabel = $dateFrom ? \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') : null;
        $dateToLabel = $dateTo ? \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') : null;

        // Ekspor dan tautan ganti akun membawa filter aktif supaya angka yang
        // diunduh atau dibuka sama dengan yang sedang terlihat.
        $filterQuery = array_filter([
            'account_code' => $accountCode,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ], fn ($value) => $value !== null && $value !== '');

        $ledgerExportUrl = route('admin.reports.export', 'general_ledger').'?'.http_build_query($filterQuery);
        $balanceExportUrl = route('admin.reports.export', 'trial_balance').'?'.http_build_query(['date' => $balanceDate]);
    @endphp

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Buku Besar &amp; Neraca Saldo</h1>
            <p class="text-sm text-stone-600 mt-1">Saldo akun kas per tanggal, lalu mutasi akun yang dipilih.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $ledgerExportUrl }}"
                class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-olive-600 rounded-xl hover:bg-olive-700 transition-colors">
                Ekspor Buku Besar
            </a>
            <a href="{{ $balanceExportUrl }}"
                class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-olive-600 rounded-xl hover:bg-olive-700 transition-colors">
                Ekspor Saldo Kas
            </a>
        </div>
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-5 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
        <div class="sm:w-72">
            <x-input-label for="account_code">Akun</x-input-label>
            <x-select id="account_code" name="account_code">
                @foreach ($accounts as $code => $info)
                    <option value="{{ $code }}" @selected((string) $accountCode === (string) $code)>{{ $info['name'] }}</option>
                @endforeach
            </x-select>
        </div>
        <div>
            <x-input-label for="date_from">Dari tanggal</x-input-label>
            <x-input id="date_from" name="date_from" type="date" :value="$dateFrom" />
        </div>
        <div>
            <x-input-label for="date_to">Sampai tanggal</x-input-label>
            <x-input id="date_to" name="date_to" type="date" :value="$dateTo" />
        </div>
        <x-primary-button>Tampilkan</x-primary-button>
    </form>

    <section aria-label="Keseimbangan jurnal" class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 mb-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-stone-900">Keseimbangan jurnal</h2>
                <p class="text-[13px] text-stone-600 mt-0.5">Kumulatif sampai {{ $balanceDateLabel }}.</p>
            </div>
            @if ($isBalanced)
                <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full bg-olive-100 text-olive-700">Seimbang</span>
            @else
                <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">Tidak seimbang</span>
            @endif
        </div>
        {{-- Alasan: nominalnya tidak ditampilkan karena hanya akun kas yang tampil di halaman
             ini, sehingga angka penyeimbangnya tidak punya pasangan baris untuk dibandingkan. --}}
        <p class="text-[13px] text-stone-600 mt-3">
            Sistem memeriksa seluruh baris jurnal, termasuk akun yang tidak ditampilkan di halaman ini.
            Halaman ini hanya menampilkan akun kas.
        </p>
    </section>

    <section aria-label="Saldo akun kas" class="mb-8">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-3 mb-3">
            <h2 class="text-lg font-semibold text-stone-900">Saldo akun kas</h2>
            <p class="text-[13px] text-stone-600">Pilih akun untuk melihat mutasinya di bawah.</p>
        </div>

        <x-data-table caption="Saldo akun kas sampai {{ $balanceDateLabel }}" :headers="[
            ['label' => 'Nama akun'],
            ['label' => 'Total debit', 'align' => 'right'],
            ['label' => 'Total kredit', 'align' => 'right'],
            ['label' => 'Saldo debit', 'align' => 'right'],
            ['label' => 'Saldo kredit', 'align' => 'right'],
        ]">
            @foreach ($balances as $code => $balance)
                @php
                    $isSelected = (string) $accountCode === (string) $code;
                    $accountUrl = $indexRoute.'?'.http_build_query(array_merge($filterQuery, ['account_code' => $code])).'#mutasi';
                @endphp
                <tr class="hover:bg-stone-50 {{ $isSelected ? 'bg-brand-50' : '' }}" @if ($isSelected) aria-current="true" @endif>
                    <td class="px-5 py-3">
                        @if ($isSelected)
                            <span class="font-semibold text-brand-900">{{ $balance['name'] }}</span>
                        @else
                            <a href="{{ $accountUrl }}"
                                class="font-medium text-brand-800 underline decoration-brand-300 underline-offset-2 hover:decoration-brand-800"
                                aria-label="Lihat mutasi akun {{ $balance['name'] }}">{{ $balance['name'] }}</a>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($balance['total_debit'], 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($balance['total_credit'], 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tnum {{ $balance['net_debit'] ? 'font-semibold text-brand-900' : '' }}">Rp {{ number_format($balance['net_debit'], 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tnum {{ $balance['net_credit'] ? 'font-semibold text-brand-900' : '' }}">Rp {{ number_format($balance['net_credit'], 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <x-slot:footer>
                <tr class="font-semibold">
                    <td colspan="3" class="px-5 py-3 text-right">Total kas</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($grandDebit, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($grandCredit, 0, ',', '.') }}</td>
                </tr>
            </x-slot:footer>
        </x-data-table>
    </section>

    <section id="mutasi" aria-label="Mutasi buku besar">
        <div class="mb-3">
            <h2 class="text-lg font-semibold text-stone-900">Mutasi {{ $accountName }}</h2>
            <p class="text-[13px] text-stone-600 mt-0.5">
                @if ($dateFrom && $dateTo)
                    Rentang {{ $dateFromLabel }} sampai {{ $dateToLabel }}.
                @elseif ($dateFrom)
                    Sejak {{ $dateFromLabel }} tanpa batas akhir.
                @elseif ($dateTo)
                    Sampai {{ $dateToLabel }}, termasuk seluruh riwayat sebelumnya.
                @else
                    Seluruh riwayat jurnal akun ini.
                @endif
            </p>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 {{ $dateFrom ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3 sm:gap-4 mb-4">
            @if ($dateFrom)
                <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
                    <dt class="text-[13px] font-medium text-stone-600">Saldo awal sebelum {{ $dateFromLabel }}</dt>
                    <dd class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($openingBalance, 0, ',', '.') }}</dd>
                </div>
            @endif
            <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
                <dt class="text-[13px] font-medium text-stone-600">Debit periode</dt>
                <dd class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totalDebit, 0, ',', '.') }}</dd>
            </div>
            <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
                <dt class="text-[13px] font-medium text-stone-600">Kredit periode</dt>
                <dd class="text-xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($totalCredit, 0, ',', '.') }}</dd>
            </div>
            <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
                <dt class="text-[13px] font-medium text-stone-600">Saldo akhir per {{ $balanceDateLabel }} (normal {{ $normalBalance === 'debit' ? 'debit' : 'kredit' }})</dt>
                <dd class="text-xl font-bold mt-1 tnum {{ $endingBalance < 0 ? 'text-red-600' : 'text-brand-900' }}">Rp {{ number_format($endingBalance, 0, ',', '.') }}</dd>
            </div>
        </dl>

        @if ($endingBalance < 0)
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3.5 mb-4">
                Peringatan: saldo akhir akun ini negatif. Untuk akun kas, artinya mutasi jurnal pengeluaran melebihi penerimaan, segera periksa riwayat jurnal dan lakukan rekonsiliasi fisik.
            </div>
        @endif

        <x-data-table caption="Mutasi jurnal akun {{ $accountName }}" :headers="[
            ['label' => 'Nomor jurnal'],
            ['label' => 'Tanggal'],
            ['label' => 'Referensi transaksi'],
            ['label' => 'Debit', 'align' => 'right'],
            ['label' => 'Kredit', 'align' => 'right'],
            ['label' => 'Keterangan'],
        ]">
            @forelse ($entries as $entry)
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3 tnum font-medium">{{ $entry->journal_number }}</td>
                    <td class="px-5 py-3 tnum">{{ $entry->journal_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 tnum">{{ $entry->transaction?->receipt_number ?? 'TRF-'.$entry->cash_transfer_id }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($entry->debit_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($entry->credit_amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-stone-600">{{ $entry->description }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada mutasi jurnal untuk filter ini.</td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-4">
            {{ $entries->links() }}
        </div>
    </section>
@endsection
