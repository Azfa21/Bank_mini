@extends('layouts.app')

@section('title', 'Penutupan Rekening | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">PENUTUPAN REKENING</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Tutup rekening karena lulus</h1>
            <p class="text-sm text-stone-600 mt-1">Saldo di atas saldo mengendap dicairkan di loket, lalu Administrator memutuskan dari bagian Kelulusan.</p>
        </div>
        <a href="{{ route('teller.members.show', $account) }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <aside aria-label="Ringkasan pencairan" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <p class="text-[13px] font-medium text-stone-600">Penabung</p>
                <p class="text-base font-bold text-stone-900 mt-1">{{ $account->saver->full_name }}</p>
                <p class="text-[13px] text-stone-600 mt-2">No. siswa: <span class="tnum font-medium text-stone-800">{{ $account->saver->student_number }}</span></p>
                <p class="text-[13px] text-stone-600 mt-1">No. rekening: <span class="tnum font-medium text-stone-800">{{ $account->account_number }}</span></p>

                <div class="border-t border-stone-100 mt-4 pt-4 space-y-1.5">
                    @php
                        $minimumBalance = max(
                            (int) $account->minimum_balance,
                            (int) \App\Models\SystemConfig::integer('minimum_balance', 10000)
                        );
                        $payableBalance = max(0, $account->current_balance - $minimumBalance);
                    @endphp
                    <p class="text-[13px] font-medium text-stone-600">Saldo yang dicairkan</p>
                    <p class="text-2xl font-bold text-stone-900 tnum">Rp {{ number_format($payableBalance, 0, ',', '.') }}</p>
                    <p class="text-[13px] text-stone-600">Saldo saat ini: <span class="tnum font-medium text-stone-800">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</span></p>
                    <p class="text-[13px] text-stone-600">Saldo mengendap: <span class="tnum font-medium text-stone-800">Rp {{ number_format($minimumBalance, 0, ',', '.') }}</span></p>
                    <p class="text-[13px] text-stone-600 mt-3">Saldo mengendap tidak bisa dicairkan dan tetap tersimpan di rekening sampai ditutup.</p>
                </div>
            </div>
        </aside>

        <section aria-label="Formulir penutupan rekening" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                <h2 class="text-base font-bold text-stone-900">Konfirmasi penutupan</h2>

                <form method="POST" action="{{ route('teller.accounts.closure.store', $account) }}" class="mt-4 space-y-5" data-confirm="Cairkan saldo di atas saldo mengendap dan ajukan penutupan rekening? Uang diserahkan ke penabung sekarang.">
                    @csrf

                    <div>
                        <x-input-label for="pin">PIN penabung 6 digit wajib</x-input-label>
                        <x-input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="off" maxlength="6"
                            class="tracking-[0.3em] text-center" placeholder="••••••" required autofocus
                            aria-describedby="pin-help" />
                        <p id="pin-help" class="text-[13px] text-stone-600 mt-1.5">Minta penabung mengetik sendiri untuk mengotorisasi pencairan saldo.</p>
                        <x-input-error :message="$errors->first('pin')" />
                    </div>

                    <div>
                        <x-input-label for="notes">Catatan (opsional)</x-input-label>
                        <x-textarea id="notes" name="notes" rows="2" maxlength="500"
                            aria-describedby="notes-help" />
                        <p id="notes-help" class="text-[13px] text-stone-600 mt-1.5">Misalnya nomor kontak wali atau alasan penutupan.</p>
                        <x-input-error :message="$errors->first('notes')" />
                    </div>

                    <x-primary-button variant="danger" data-loading-text="Mencairkan saldo...">
                        Cairkan saldo & ajukan penutupan
                    </x-primary-button>
                    <p class="text-[13px] text-stone-600 text-center">Saldo langsung dicairkan ke penabung. Bila Administrator menolak dari bagian Kelulusan, saldo dikembalikan ke rekening.</p>
                </form>
            </div>
        </section>
    </div>
@endsection
