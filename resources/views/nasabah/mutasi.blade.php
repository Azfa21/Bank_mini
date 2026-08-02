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
                <label for="type" class="block text-xs font-medium text-gray-600 mb-1">Jenis Transaksi</label>
                <select id="type" name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="deposit" @selected(request('type') === 'deposit')>Setoran</option>
                    <option value="withdrawal" @selected(request('type') === 'withdrawal')>Penarikan</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-xs font-medium text-gray-600 mb-1">Dari Tanggal</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-medium text-gray-600 mb-1">Sampai Tanggal</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Filter
            </button>
        </form>

        <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Nomor Transaksi</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3 text-right">Nominal</th>
                        <th class="px-5 py-3 text-right">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">{{ $transaction->transaction_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 font-mono">{{ $transaction->receipt_number }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->type === 'deposit' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada mutasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
@endsection
