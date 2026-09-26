@extends('layouts.app')

@section('title', 'Konfigurasi Sistem  EBank Mini')
@section('section', 'Administrator · Konfigurasi')

@section('content')
    <div>
        <p class="text-xs font-semibold text-brand-900">PENGATURAN GLOBAL</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Konfigurasi sistem</h1>
        <p class="text-sm text-stone-600 mt-1 mb-5">Perubahan langsung dipakai loket, struk, dan validasi penarikan.</p>

        <form method="POST" action="{{ route('admin.config.update') }}" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 space-y-5" id="config-form">
            @csrf @method('PUT')

            <div>
                <x-input-label for="bank_name">Nama bank mini wajib</x-input-label>
                <x-input id="bank_name" name="bank_name" :value="$configs->get('bank_name')->config_value ?? 'Bank Mini Sekolah'" required
                    aria-describedby="bank-name-help" />
                <p id="bank-name-help" class="text-[13px] text-stone-600 mt-1.5">Tampil di struk, laporan, dan kartu penabung.</p>
                <x-input-error :message="$errors->first('bank_name')" />
            </div>

            <div>
                <x-input-label for="operational_hours">Jam layanan loket</x-input-label>
                <x-input name="operational_hours" id="operational_hours" :value="$configs->get('operational_hours')->config_value ?? '07.30 - 14.00 WIB'"
                    aria-describedby="hours-help" />
                <p id="hours-help" class="text-[13px] text-stone-600 mt-1.5">Tampil di dashboard Teller dan footer.</p>
                <x-input-error :message="$errors->first('operational_hours')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="minimum_balance">Saldo mengendap (Rp) wajib</x-input-label>
                    <x-input id="minimum_balance" name="minimum_balance" type="number" inputmode="numeric" :value="$configs->get('minimum_balance')->config_value ?? 10000" min="0" required
                        aria-describedby="min-help" />
                    <p id="min-help" class="text-[13px] text-stone-600 mt-1.5">Sisa penarikan tidak boleh di bawah ini.</p>
                    <x-input-error :message="$errors->first('minimum_balance')" />
                </div>

                <div>
                    <x-input-label for="admin_fee">Biaya admin per penarikan (Rp) wajib</x-input-label>
                    <x-input id="admin_fee" name="admin_fee" type="number" inputmode="numeric" :value="$configs->get('admin_fee')->config_value ?? 0" min="0" required
                        aria-describedby="fee-help" />
                    <p id="fee-help" class="text-[13px] text-stone-600 mt-1.5">Masuk akun 401. Isi 0 bila gratis.</p>
                    <x-input-error :message="$errors->first('admin_fee')" />
                </div>
            </div>

            <div>
                <x-input-label for="max_withdrawal_without_order">Batas penarikan tanpa pesanan (Rp) wajib</x-input-label>
                <x-input id="max_withdrawal_without_order" name="max_withdrawal_without_order" type="number" inputmode="numeric" :value="$configs->get('max_withdrawal_without_order')->config_value ?? 0" min="0" required
                    aria-describedby="limit-help" />
                <p id="limit-help" class="text-[13px] text-stone-600 mt-1.5">Di atas batas ini transaksi ditolak. Isi 0 untuk nonaktif.</p>
                <x-input-error :message="$errors->first('max_withdrawal_without_order')" />
            </div>

            <div>
                <x-input-label for="min_deposit_to_activate">Setoran minimal aktivasi anggota (Rp) wajib</x-input-label>
                <x-input id="min_deposit_to_activate" name="min_deposit_to_activate" type="number" inputmode="numeric" :value="$configs->get('min_deposit_to_activate')->config_value ?? 10000" min="0" required
                    aria-describedby="activate-help" />
                <p id="activate-help" class="text-[13px] text-stone-600 mt-1.5">Anggota otomatis menjadi penabung setelah setoran minimal ini.</p>
                <x-input-error :message="$errors->first('min_deposit_to_activate')" />
            </div>

            <div class="pt-1">
                <x-primary-button id="config-submit" data-loading-text="Menyimpan..." class="w-full sm:w-auto sm:min-w-[220px]">
                    Simpan konfigurasi
                </x-primary-button>
            </div>
        </form>
    </div>

@endsection
