@extends('layouts.app')

@section('title', 'Buku Besar & Mutasi Kas | Bank Mini')
@section('section', 'Administrator')

@section('content')
    @php
        $indexRoute = route('admin.reports.general-ledger');
        $balanceDateLabel = \Illuminate\Support\Carbon::parse($balanceDate)->format('d/m/Y');
        $dateFromLabel = $dateFrom ? \Illuminate\Support\Carbon::parse($dateFrom)->format('d/m/Y') : null;
        $dateToLabel = $dateTo ? \Illuminate\Support\Carbon::parse($dateTo)->format('d/m/Y') : null;
        $filterQuery = array_filter([
            'account_code' => $accountCode,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'search' => $search,
        ], fn ($value) => $value !== null && $value !== '');
        $exportUrl = route('admin.reports.export', 'general_ledger').(count($filterQuery) ? '?'.http_build_query($filterQuery) : '');
        $dateSummary = $dateFromLabel && $dateToLabel
            ? $dateFromLabel.'–'.$dateToLabel
            : ($dateFromLabel ? 'Sejak '.$dateFromLabel : ($dateToLabel ? 'Sampai '.$dateToLabel : 'Seluruh riwayat'));
    @endphp

    <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2">
        <h1 class="text-2xl font-bold tracking-tight text-stone-900">Buku Besar &amp; Mutasi Kas</h1>
        @if ($isBalanced)
            <span role="status" class="inline-flex min-h-7 items-center rounded-full bg-olive-100 px-2.5 text-xs font-semibold text-olive-800">Jurnal Seimbang</span>
        @else
            <span role="status" class="inline-flex min-h-7 items-center rounded-full bg-red-100 px-2.5 text-xs font-semibold text-red-800">Jurnal Tidak Seimbang</span>
        @endif
    </div>

    <form method="GET" action="{{ $indexRoute }}" class="mb-4 grid grid-cols-1 gap-2.5 rounded-2xl border border-stone-200 bg-white p-3 sm:grid-cols-2 sm:p-4 lg:grid-cols-3 xl:grid-cols-[minmax(13rem,1.15fr)_minmax(10rem,.85fr)_minmax(10rem,.85fr)_minmax(17rem,1.55fr)_8rem_9rem]">
        <div>
            <x-input-label for="account_code">Akun kas</x-input-label>
            <x-select id="account_code" name="account_code">
                <option value="" @selected($accountCode === '')>Semua Akun Kas</option>
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
        <div>
            <x-input-label for="search">Cari jurnal kas</x-input-label>
            <x-input id="search" name="search" :value="$search" placeholder="No. jurnal, referensi, atau keterangan" autocomplete="off" />
        </div>
        <button type="submit" class="inline-flex min-h-[44px] w-fit cursor-pointer items-center justify-center justify-self-start rounded-xl bg-brand-800 px-3 text-[13px] font-semibold text-white transition-colors hover:bg-brand-900 focus-visible:outline-brand-800">Tampilkan</button>
        <a href="{{ $exportUrl }}" class="inline-flex min-h-[44px] w-fit cursor-pointer items-center justify-center justify-self-end rounded-xl border border-olive-700 bg-olive-600 px-3 text-[13px] font-semibold text-white transition-colors hover:bg-olive-700" title="Berkas CSV dapat dibuka di Excel">Ekspor CSV</a>
    </form>

    <section aria-label="Ringkasan saldo kas" class="mb-5 space-y-3">
        <div class="min-w-0">
            <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                <h2 class="text-lg font-semibold text-stone-900">Saldo akun kas</h2>
                <p class="text-xs text-stone-600">{{ $dateSummary }} · klik baris untuk melihat mutasi</p>
            </div>

            <x-data-table caption="Ringkasan saldo akun kas" :headers="[
                ['label' => 'Nama akun'],
                ['label' => 'Total debit', 'align' => 'right'],
                ['label' => 'Total kredit', 'align' => 'right'],
                ['label' => 'Saldo akhir', 'align' => 'right'],
            ]">
                @foreach ($summaryBalances as $code => $balance)
                    @php
                        $accountFilterQuery = array_filter([
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'search' => $search,
                        ], fn ($value) => $value !== null && $value !== '');
                        $accountFilterQuery['account_code'] = $code;
                        $accountUrl = $indexRoute.'?'.http_build_query($accountFilterQuery).'#mutasi';
                        $isSelected = (string) $accountCode === (string) $code;
                    @endphp
                    <tr data-account-url="{{ $accountUrl }}" class="cursor-pointer transition-colors hover:bg-brand-50 {{ $isSelected ? 'bg-brand-50' : '' }}" @if ($isSelected) aria-current="true" @endif>
                        <td class="px-4 py-3">
                            <a href="{{ $accountUrl }}" class="font-medium text-brand-900 underline decoration-brand-300 underline-offset-2 hover:decoration-brand-800">{{ $balance['name'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-right tnum">Rp {{ number_format($balance['total_debit'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right tnum">Rp {{ number_format($balance['total_credit'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold tnum {{ $balance['ending_balance'] < 0 ? 'text-red-700' : 'text-stone-900' }}">Rp {{ number_format($balance['ending_balance'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach

            </x-data-table>
        </div>

        <dl aria-label="Total periode" class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
            <div class="ledger-rule rounded-xl border border-stone-200 bg-white px-4 py-3">
                <dt class="text-xs font-medium text-stone-600">Total debit periode</dt>
                <dd class="mt-1 text-lg font-bold text-stone-900 tnum">Rp {{ number_format($totalDebit, 0, ',', '.') }}</dd>
            </div>
            <div class="ledger-rule rounded-xl border border-stone-200 bg-white px-4 py-3">
                <dt class="text-xs font-medium text-stone-600">Total kredit periode</dt>
                <dd class="mt-1 text-lg font-bold text-stone-900 tnum">Rp {{ number_format($totalCredit, 0, ',', '.') }}</dd>
            </div>
            <div class="ledger-rule rounded-xl border border-stone-200 bg-white px-4 py-3">
                <dt class="text-xs font-medium text-stone-600">Total saldo akhir kas · {{ $balanceDateLabel }}</dt>
                <dd class="mt-1 text-lg font-bold tnum {{ $cashEndingBalance < 0 ? 'text-red-700' : 'text-brand-900' }}">Rp {{ number_format($cashEndingBalance, 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>

    <section id="mutasi" aria-label="Detail mutasi kas" class="scroll-mt-4">
        <div class="mb-2 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <h2 class="text-lg font-semibold text-stone-900">Detail mutasi kas{{ $accountCode !== '' ? ' · '.$accounts[$accountCode]['name'] : '' }}</h2>
            <p class="text-xs text-stone-600">{{ $dateSummary }}{{ $search !== '' ? ' · hasil pencarian “'.$search.'”' : '' }}</p>
        </div>

        <x-data-table caption="Detail mutasi dan audit jurnal kas" :headers="array_merge(
            [
                ['label' => 'Tanggal & waktu'],
                ['label' => 'No. jurnal / referensi'],
            ],
            $accountCode === '' ? [['label' => 'Akun']] : [],
            [
                ['label' => 'Debit (Rp)', 'align' => 'right'],
                ['label' => 'Kredit (Rp)', 'align' => 'right'],
                ['label' => 'Keterangan'],
            ],
        )">
            @forelse ($entries as $entry)
                @php
                    $reference = $entry->transaction?->receipt_number ?? $entry->cashRequest?->request_number ?? ($entry->cash_transfer_id ? 'TRF-'.$entry->cash_transfer_id : '—');
                @endphp
                <tr class="hover:bg-stone-50">
                    <td class="whitespace-nowrap px-4 py-2.5 tnum">{{ $entry->journal_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2.5">
                        <span class="block whitespace-nowrap font-medium tnum">{{ $entry->journal_number }}</span>
                        <span class="block text-xs text-stone-500 tnum">{{ $reference }}</span>
                    </td>
                    @if ($accountCode === '')
                        <td class="px-4 py-2.5">{{ $accounts[$entry->account_code]['name'] ?? 'Akun '.$entry->account_code }}</td>
                    @endif
                    <td class="whitespace-nowrap px-4 py-2.5 text-right tnum">{{ $entry->debit_amount > 0 ? 'Rp '.number_format($entry->debit_amount, 0, ',', '.') : '—' }}</td>
                    <td class="whitespace-nowrap px-4 py-2.5 text-right tnum">{{ $entry->credit_amount > 0 ? 'Rp '.number_format($entry->credit_amount, 0, ',', '.') : '—' }}</td>
                    <td class="min-w-48 px-4 py-2.5 text-stone-600">{{ $entry->description ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $accountCode === '' ? 6 : 5 }}" class="px-4 py-8 text-center text-sm text-stone-600">Tidak ada mutasi kas untuk filter ini.</td>
                </tr>
            @endforelse
        </x-data-table>

        <div class="mt-3">
            {{ $entries->links() }}
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-account-url]');

                if (row && !event.target.closest('a, button, input, select')) {
                    window.location.assign(row.dataset.accountUrl);
                }
            });
        </script>
    @endpush
@endsection
