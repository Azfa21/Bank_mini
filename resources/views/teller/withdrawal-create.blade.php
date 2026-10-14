@extends('layouts.app')

@section('title', 'Transaksi Penarikan  EBank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-red-800">PENARIKAN TUNAI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Serahkan penarikan</h1>
            <p class="text-sm text-stone-600 mt-1">Saldo harus cukup dan PIN 6 digit harus benar.</p>
        </div>
        <a href="{{ route('teller.members.show', $account) }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <aside aria-label="Batas saldo penabung" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <p class="text-[13px] font-medium text-stone-600">Penabung</p>
                <p class="text-base font-bold text-stone-900 mt-1">{{ $account->saver->full_name }}</p>
                <p class="text-[13px] text-stone-600 mt-2">No. rekening: <span class="tnum font-medium text-stone-800">{{ $account->account_number }}</span></p>
                <div class="border-t border-stone-100 mt-4 pt-4 space-y-1.5">
                    <p class="text-[13px] font-medium text-stone-600">Saldo saat ini</p>
                    <p class="text-2xl font-bold text-stone-900 tnum">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                    @php
                        $maxWithoutOrder = (int) \App\Models\SystemConfig::value('max_withdrawal_without_order', 0);
                        $minimumBalance = max(
                            (int) $account->minimum_balance,
                            (int) \App\Models\SystemConfig::value('minimum_balance', 10000)
                        );
                    @endphp
                    <p class="text-[13px] text-stone-600">Saldo mengendap: <span class="tnum font-medium text-stone-800">Rp {{ number_format($minimumBalance, 0, ',', '.') }}</span></p>
                    @if ($maxWithoutOrder > 0)
                        <p class="text-[13px] font-semibold text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-2.5 mt-2">Batas tanpa pesanan: <span class="tnum">Rp {{ number_format($maxWithoutOrder, 0, ',', '.') }}</span></p>
                    @endif
                    <p id="withdraw-preview" role="status" class="text-[13px] font-medium text-stone-800">Sisa sesudah penarikan: Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>
        </aside>

        <section aria-label="Formulir penarikan" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                <h2 class="text-base font-bold text-stone-900">Data penarikan</h2>

                <form method="POST" action="{{ route('teller.withdrawals.store', $account) }}" class="mt-4 space-y-5" id="withdraw-form" data-balance="{{ (int) $account->current_balance }}">
                    @csrf

                    <div>
                        <x-input-label for="amount">Nominal penarikan (Rp) wajib</x-input-label>
                        <x-input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 50000" required
                            aria-describedby="amount-help amount-preview" />
                        <p id="amount-help" class="text-[13px] text-stone-600 mt-1.5">Sisa saldo tidak boleh di bawah saldo mengendap.</p>
                        <p id="amount-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('amount')" />
                    </div>

                    <details class="border-t border-stone-100 pt-5">
                        <summary class="inline-flex items-center min-h-[44px] cursor-pointer text-[15px] font-bold text-stone-900">
                            Siapkan pecahan untuk penabung (opsional)
                        </summary>
                        <p class="text-[13px] text-stone-600 mt-1 mb-3">Bantu teller menyiapkan uang yang diserahkan. Tidak memengaruhi nominal.</p>
                        <x-denomination-input />
                        <p role="status" data-denomination-sum class="text-[13px] font-medium text-stone-700 mt-2">Subtotal rincian: Rp 0</p>
                    </details>

                    <div>
                        <x-input-label for="pin">PIN penabung 6 digit wajib</x-input-label>
                        <x-input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="off" maxlength="6"
                            class="tracking-[0.3em] text-center" placeholder="••••••" required
                            aria-describedby="pin-help" />
                        <p id="pin-help" class="text-[13px] text-stone-600 mt-1.5">Minta penabung mengetik sendiri. Teller tidak boleh melihat.</p>
                        <x-input-error :message="$errors->first('pin')" />
                    </div>

                    <x-primary-button variant="danger" id="withdraw-submit" data-loading-text="Memeriksa PIN dan saldo..." class="w-full">
                        Simpan penarikan
                    </x-primary-button>
                    <p class="text-[13px] text-stone-600 text-center">Periksa pecahan dan PIN dulu. Transaksi tersimpan tidak bisa diubah.</p>
                </form>
            </div>
        </section>
    </div>
@endsection
