@extends('layouts.app')

@section('title', 'Transfer Antar Rekening  EBank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">TRANSFER ANTAR REKENING</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Pindahkan saldo</h1>
            <p class="text-sm text-stone-600 mt-1">Periksa rekening tujuan dan PIN pengirim sebelum proses.</p>
        </div>
        <a href="{{ route('teller.members.show', $account) }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke profil</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <aside aria-label="Pengirim" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <p class="text-[13px] font-medium text-stone-600">Pengirim</p>
                <p class="text-base font-bold text-stone-900 mt-1">{{ $account->saver->full_name }}</p>
                <p class="text-[13px] text-stone-600 mt-2">No. rekening: <span class="tnum font-medium text-stone-800">{{ $account->account_number }}</span></p>
                <div class="border-t border-stone-100 mt-4 pt-4">
                    <p class="text-[13px] font-medium text-stone-600">Saldo pengirim</p>
                    <p class="text-2xl font-bold text-stone-900 tnum mt-1">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                    <p class="text-[13px] text-stone-600 mt-1">Sisa tidak boleh di bawah <span class="tnum font-medium text-stone-800">Rp {{ number_format(max((int) $account->minimum_balance, (int) \App\Models\SystemConfig::value('minimum_balance', 10000)), 0, ',', '.') }}</span></p>
                    <p id="transfer-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-2">Sisa sesudah: Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
                </div>
            </div>
        </aside>

        <section aria-label="Formulir transfer" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                <h2 class="text-base font-bold text-stone-900">Data transfer</h2>

                <form method="POST" action="{{ route('teller.transfers.store', $account) }}" class="mt-4 space-y-5" id="transfer-form" data-balance="{{ (int) $account->current_balance }}">
                    @csrf

                    <div>
                        <x-input-label for="recipient_account_number">Nomor rekening tujuan wajib</x-input-label>
                        <x-input id="recipient_account_number" name="recipient_account_number"
                            class="tnum" placeholder="Contoh: 1026270002" required autocomplete="off"
                            aria-describedby="recipient-help" />
                        <p id="recipient-help" class="text-[13px] text-stone-600 mt-1.5">Minta penabung menyebutkan atau menunjukkan nomor tujuan.</p>
                        <x-input-error :message="$errors->first('recipient_account_number')" />
                    </div>

                    <div>
                        <x-input-label for="amount">Nominal transfer (Rp) wajib</x-input-label>
                        <x-input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric" placeholder="Contoh: 50000" required
                            aria-describedby="amount-preview" />
                        <p id="amount-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1.5">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('amount')" />
                    </div>

                    <div>
                        <x-input-label for="pin">PIN pengirim 6 digit wajib</x-input-label>
                        <x-input id="pin" name="pin" type="password" inputmode="numeric" autocomplete="off" maxlength="6"
                            class="tracking-[0.3em] text-center" placeholder="••••••" required />
                        <x-input-error :message="$errors->first('pin')" />
                    </div>

                    <div>
                        <x-input-label for="notes">Keterangan (opsional)</x-input-label>
                        <x-textarea id="notes" name="notes" rows="2"
                            placeholder="Contoh: Iuran kas kelas" />
                        <x-input-error :message="$errors->first('notes')" />
                    </div>

                    <x-primary-button id="transfer-submit" data-loading-text="Menyimpan transfer..." class="w-full">
                        Simpan transfer
                    </x-primary-button>
                </form>
            </div>
        </section>
    </div>
@endsection
