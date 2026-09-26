@extends('layouts.app')

@section('title', 'Cetak Buku Tabungan  EBank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Buku Tabungan Fisik</h1>
            <p class="text-sm text-stone-500 mt-1">Pilih baris awal cetak agar tidak menimpa baris sebelumnya di buku tabungan.</p>
        </div>
        <a href="{{ route('teller.members.show', $account) }}" class="text-sm text-brand-900 hover:text-brand-800">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-stone-200 p-5">
                <p class="text-xs text-stone-500">Penabung</p>
                <p class="text-sm font-semibold text-stone-900 mt-1">{{ $account->saver->full_name }}</p>
                <p class="text-xs text-stone-500 mt-2">No. Siswa: <span class="font-mono">{{ $account->saver->student_number }}</span></p>
                <p class="text-xs text-stone-500 mt-1">No. Rekening: <span class="font-mono">{{ $account->account_number }}</span></p>
                <div class="border-t border-stone-100 mt-4 pt-4">
                    <p class="text-xs text-stone-500">Saldo Saat Ini</p>
                    <p class="text-xl font-semibold text-brand-900 mt-1">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-stone-200 p-5 mt-4">
                <h2 class="text-sm font-medium text-stone-900 mb-3">Pengaturan Cetak</h2>
                <form method="GET" action="{{ route('teller.passbook.print', $account) }}" class="space-y-4" target="_blank">
                    <div>
                        <label for="start_line" class="block text-xs font-medium text-stone-600 mb-1">Mulai dari Baris ke-</label>
                        <input type="number" id="start_line" name="start_line" value="{{ request('start_line', 1) }}" min="1" max="20"
                            class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600">
                        <p class="text-xs text-stone-500 mt-1">Baris berikutnya otomatis dihitung (maks. 20 baris per halaman).</p>
                    </div>
                    <button type="submit"
                        class="w-full bg-brand-800 text-white text-sm font-medium px-4 py-2.5 rounded-lg hover:bg-brand-900 transition-colors">
                        Cetak Buku Tabungan
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-stone-200 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 text-left">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Tipe</th>
                            <th class="px-5 py-3">Debet (Masuk)</th>
                            <th class="px-5 py-3">Kredit (Keluar)</th>
                            <th class="px-5 py-3 text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($transactions as $index => $transaction)
                            <tr class="hover:bg-stone-50">
                                <td class="px-5 py-3 text-stone-500">{{ $transactions->firstItem() + $index }}</td>
                                <td class="px-5 py-3">{{ $transaction->transaction_at?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $transaction->isCreditFor($account->id) ? 'bg-olive-100 text-olive-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $transaction->typeLabel() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-mono text-olive-700">
                                    @if ($transaction->isCreditFor($account->id))
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-red-700">
                                    @if (! $transaction->isCreditFor($account->id))
                                        Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-stone-500">Belum ada mutasi untuk buku tabungan.</td>
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
