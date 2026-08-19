@extends('layouts.app')

@section('title', 'Cetak Buku Tabungan — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Buku Tabungan Fisik</h1>
            <p class="text-sm text-gray-500 mt-1">Pilih baris awal cetak agar tidak menimpa baris sebelumnya di buku tabungan.</p>
        </div>
        <a href="{{ route('teller.customers.show', $account) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-xs text-gray-500">Nasabah</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">{{ $account->customer->full_name }}</p>
                <p class="text-xs text-gray-500 mt-2">No. Siswa: <span class="font-mono">{{ $account->customer->student_number }}</span></p>
                <p class="text-xs text-gray-500 mt-1">No. Rekening: <span class="font-mono">{{ $account->account_number }}</span></p>
                <div class="border-t border-gray-100 mt-4 pt-4">
                    <p class="text-xs text-gray-500">Saldo Saat Ini</p>
                    <p class="text-xl font-semibold text-blue-600 mt-1">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 mt-4">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Pengaturan Cetak</h2>
                <form method="GET" action="{{ route('teller.passbook.print', $account) }}" class="space-y-4" target="_blank">
                    <div>
                        <label for="start_line" class="block text-xs font-medium text-gray-600 mb-1">Mulai dari Baris ke-</label>
                        <input type="number" id="start_line" name="start_line" value="{{ request('start_line', 1) }}" min="1" max="20"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Baris berikutnya otomatis dihitung (maks. 20 baris per halaman).</p>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors">
                        Cetak Buku Tabungan
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Tipe</th>
                            <th class="px-5 py-3">Debet (Masuk)</th>
                            <th class="px-5 py-3">Kredit (Keluar)</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $index => $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-400">{{ $transactions->firstItem() + $index }}</td>
                                <td class="px-5 py-3">{{ $transaction->transaction_at?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-700' : ($transaction->type === 'transfer' && $transaction->recipient_account_id === $account->id ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') }}">
                                        @if ($transaction->type === 'deposit')
                                            Setoran
                                        @elseif ($transaction->type === 'withdrawal')
                                            Penarikan
                                        @elseif ($transaction->type === 'transfer' && $transaction->recipient_account_id === $account->id)
                                            Transfer Masuk
                                        @else
                                            Transfer Keluar
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-mono text-green-700">
                                    @if ($transaction->type === 'deposit' || ($transaction->type === 'transfer' && $transaction->recipient_account_id === $account->id))
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-red-700">
                                    @if ($transaction->type === 'withdrawal' || ($transaction->type === 'transfer' && $transaction->account_id === $account->id))
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada mutasi untuk buku tabungan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
@endsection
