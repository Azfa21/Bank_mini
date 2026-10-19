@extends('layouts.app')

@section('title', 'Transaksi Setoran | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">SETORAN TUNAI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Terima setoran</h1>
            <p class="text-sm text-stone-600 mt-1">Angka pada slip langsung menjadi nominal setoran. Periksa dulu sebelum disimpan.</p>
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
                        <x-input id="slip_amount" name="slip_amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 100000" required autofocus
                            aria-describedby="slip-help slip-preview" />
                        <p id="slip-help" class="text-[13px] text-stone-600 mt-1.5">Salin angka dari slip setoran siswa.</p>
                        <p id="slip-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('slip_amount')" />
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
