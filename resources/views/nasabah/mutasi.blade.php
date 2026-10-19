@extends('layouts.app')

@section('title', 'Riwayat Mutasi | Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">RIWAYAT UANG</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Mutasi rekening</h1>
        <p class="text-sm text-stone-600 mt-1">Setoran masuk hijau, penarikan keluar merah.</p>
    </div>

    @if (! $account)
        <div role="status" class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-2xl p-5">
            Anda belum punya rekening aktif. Hubungi loket Bank Mini sekolah untuk pembukaan.
        </div>
    @else
        <form method="GET" action="{{ route('nasabah.mutasi') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="sm:w-48">
                <x-input-label for="type">Jenis</x-input-label>
                <x-select id="type" name="type">
                    <option value="">Semua jenis</option>
                    <option value="deposit" @selected(request('type') === 'deposit')>Setoran</option>
                    <option value="withdrawal" @selected(request('type') === 'withdrawal')>Penarikan</option>
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
                Tampilkan mutasi
            </x-primary-button>
        </form>

        <x-data-table caption="Riwayat mutasi rekening {{ $account->account_number }}" :headers="[['label' => 'Tanggal'], ['label' => 'Nomor transaksi'], ['label' => 'Jenis'], ['label' => 'Nominal', 'align' => 'right'], ['label' => 'Saldo akhir', 'align' => 'right']]">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-stone-50">
                            <td class="px-5 py-3 tnum">{{ $transaction->transaction_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 tnum">{{ $transaction->receipt_number }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $transaction->isCreditFor($account->id) ? 'bg-brand-50 border-brand-200 text-brand-900' : 'bg-red-50 border-red-200 text-red-800' }}">
                                    {{ $transaction->typeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right tnum font-semibold {{ $transaction->isCreditFor($account->id) ? 'text-brand-900' : 'text-red-800' }}">
                                {{ $transaction->isCreditFor($account->id) ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right tnum">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada mutasi untuk filter ini. Coba rentang tanggal lain.</td>
                        </tr>
                    @endforelse
</x-data-table>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
@endsection
