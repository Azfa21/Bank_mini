@extends('layouts.app')

@section('title', 'Transaksi Setoran  EBank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">SETORAN TUNAI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Terima setoran</h1>
            <p class="text-sm text-stone-600 mt-1">Slip dan uang fisik harus sama persis sebelum disimpan.</p>
        </div>
        <a href="{{ route('teller.members.show', $account) }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <aside aria-label="Penabung yang dilayani" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <p class="text-[13px] font-medium text-stone-600">Penabung</p>
                <p class="text-base font-bold text-stone-900 mt-1">{{ $account->saver->full_name }}</p>
                <p class="text-[13px] text-stone-600 mt-2">No. rekening: <span class="tnum font-medium text-stone-800">{{ $account->account_number }}</span></p>
                <div class="border-t border-stone-100 mt-4 pt-4">
                    <p class="text-[13px] font-medium text-stone-600">Saldo saat ini</p>
                    <p class="text-2xl font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                    <p id="deposit-preview" role="status" class="text-[13px] text-brand-900 font-medium mt-2">Saldo sesudah: Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>
        </aside>

        <section aria-label="Formulir setoran" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                <h2 class="text-base font-bold text-stone-900">Data setoran</h2>

                <form method="POST" action="{{ route('teller.deposits.store', $account) }}" class="mt-4 space-y-5" id="deposit-form" data-balance-before="{{ (int) $account->current_balance }}">
                    @csrf

                    <div>
                        <x-input-label for="slip_amount">Nominal pada slip (Rp) wajib</x-input-label>
                        <x-input id="slip_amount" name="slip_amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 100000" required
                            aria-describedby="slip-help slip-preview" />
                        <p id="slip-help" class="text-[13px] text-stone-600 mt-1.5">Salin angka dari slip setoran siswa.</p>
                        <p id="slip-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('slip_amount')" />
                    </div>

                    <details class="border-t border-stone-100 pt-5">
                        <summary class="inline-flex items-center min-h-[44px] cursor-pointer text-[15px] font-bold text-stone-900">
                            Bantu hitung uang fisik per pecahan (opsional)
                        </summary>
                        <p class="text-[13px] text-stone-600 mt-1 mb-3">Isi jumlah lembar atau keping bila perlu, lalu pakai totalnya. Bisa juga langsung ketik total di bawah.</p>
                        <x-denomination-input />
                        <p role="status" data-denomination-sum class="text-[13px] font-medium text-stone-700 mt-2">Subtotal rincian: Rp 0</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" data-denomination-apply class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-brand-900 border border-brand-800/30 bg-brand-50 rounded-xl hover:bg-brand-100 disabled:opacity-50">
                                Pakai total ini
                            </button>
                            <button type="button" data-denomination-reset class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-medium text-stone-600 hover:text-stone-900">
                                Ulangi
                            </button>
                        </div>
                    </details>

                    <div>
                        <x-input-label for="physical_cash_amount">Total uang fisik diterima (Rp) wajib</x-input-label>
                        <x-input id="physical_cash_amount" name="physical_cash_amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Hasil hitung uang di tangan" required data-denomination-total
                            aria-describedby="cash-help cash-match" />
                        <p id="cash-help" class="text-[13px] text-stone-600 mt-1.5">Harus sama dengan nominal slip, kalau beda transaksi ditolak.</p>
                        <p id="cash-match" role="status" class="text-[13px] font-semibold mt-1.5 text-stone-600">Menunggu isian slip dan uang fisik.</p>
                        <x-input-error :message="$errors->first('physical_cash_amount')" />
                    </div>

                    <x-primary-button id="deposit-submit" data-loading-text="Menyimpan setoran..." class="w-full">
                        Simpan setoran
                    </x-primary-button>
                    <p class="text-[13px] text-stone-600 text-center">Transaksi tersimpan tidak bisa diubah. Periksa dulu sebelum tekan.</p>
                </form>
            </div>
        </section>
    </div>
@endsection
