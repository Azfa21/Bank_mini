@extends('layouts.app')

@section('title', 'Transaksi Penarikan | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-red-800">PENARIKAN TUNAI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Serahkan penarikan</h1>
            <p class="text-sm text-stone-600 mt-1">Penarikan harian maksimal Rp 1.000.000. Total lebih dari itu perlu booking yang disetujui teller.</p>
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
                        $minimumBalance = max(
                            (int) $account->minimum_balance,
                            (int) \App\Models\SystemConfig::value('minimum_balance', 10000)
                        );
                    @endphp
                    <p class="text-[13px] text-stone-600">Saldo mengendap: <span class="tnum font-medium text-stone-800">Rp {{ number_format($minimumBalance, 0, ',', '.') }}</span></p>
                    <p class="text-[13px] text-stone-600">Penarikan hari ini: <span class="tnum font-medium text-stone-800">Rp {{ number_format($withdrawalDaily['withdrawn'], 0, ',', '.') }}</span> dari <span class="tnum font-medium text-stone-800">Rp {{ number_format($withdrawalDaily['limit'], 0, ',', '.') }}</span></p>
                    @if ($withdrawalDaily['booking'])
                        <p class="text-[13px] font-medium text-olive-700">Booking disetujui untuk hari ini hingga total Rp {{ number_format($withdrawalDaily['limit'], 0, ',', '.') }}.</p>
                    @else
                        <p class="text-[13px] text-stone-600">Sisa batas harian: <span class="tnum font-medium text-stone-800">Rp {{ number_format($withdrawalDaily['remaining'], 0, ',', '.') }}</span></p>
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
                        <x-input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 50000" required autofocus
                            aria-describedby="amount-help amount-preview" />
                        <p id="amount-help" class="text-[13px] text-stone-600 mt-1.5">Total penarikan harian maksimal Rp 1.000.000 tanpa booking. Sisa saldo juga tidak boleh di bawah saldo mengendap.</p>
                        <p id="amount-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('amount')" />
                    </div>

                    @if ($withdrawalDaily['booking'])
                        <input type="hidden" name="withdrawal_booking_id" value="{{ $withdrawalDaily['booking']->id }}">
                        <p class="rounded-xl border border-olive-200 bg-olive-50 p-3 text-[13px] text-olive-700">
                            Booking tanggal {{ $withdrawalDaily['booking']->withdrawal_date->format('d/m/Y') }} disetujui teller. Sistem akan memakainya bila total penarikan hari ini melewati Rp 1.000.000.
                        </p>
                    @else
                        <p class="text-[13px] text-stone-600">
                            Belum ada booking yang disetujui untuk hari ini.
                            <a href="{{ route('teller.withdrawal-bookings.index') }}" class="font-semibold text-brand-900 hover:underline">Periksa daftar booking</a>.
                        </p>
                    @endif

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
                    <p class="text-[13px] text-stone-600 text-center">Periksa nominal dan PIN dulu. Transaksi tersimpan tidak bisa diubah.</p>
                </form>
            </div>
        </section>
    </div>
@endsection
