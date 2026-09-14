@extends('layouts.app')

@section('title', 'Riwayat Mutasi — Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Riwayat Mutasi</h1>
        <p class="text-sm text-gray-500 mt-1">Seluruh setoran dan penarikan rekening Anda.</p>
    </div>

    @if (! $account)
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-xl p-5">
            Anda belum memiliki rekening aktif. Silakan hubungi administrator Bank Mini.
        </div>
    @else
        <form method="GET" action="{{ route('nasabah.mutasi') }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end">
            <div>
                <x-input-label for="type" size="xs">Jenis Transaksi</x-input-label>
                <x-select id="type" name="type" :block="false">
                    <option value="">Semua</option>
                    <option value="deposit" @selected(request('type') === 'deposit')>Setoran</option>
                    <option value="withdrawal" @selected(request('type') === 'withdrawal')>Penarikan</option>
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

        <x-data-table :headers="[['label' => 'Tanggal'], ['label' => 'Nomor Transaksi'], ['label' => 'Jenis'], ['label' => 'Nominal', 'align' => 'right'], ['label' => 'Saldo Akhir', 'align' => 'right']]">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">{{ $transaction->transaction_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 font-mono">{{ $transaction->receipt_number }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $transaction->isCreditFor($account->id) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $transaction->typeLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono {{ $transaction->isCreditFor($account->id) ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->isCreditFor($account->id) ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada mutasi.</td>
                        </tr>
                    @endforelse
</x-data-table>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
@endsection
