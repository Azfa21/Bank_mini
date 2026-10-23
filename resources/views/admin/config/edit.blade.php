@extends('layouts.app')

@section('title', 'Konfigurasi Sistem | Bank Mini')
@section('section', 'Administrator · Konfigurasi')

@section('content')
    <div>
        <p class="text-xs font-semibold text-brand-900">PENGATURAN GLOBAL</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Konfigurasi sistem</h1>
        <p class="text-sm text-stone-600 mt-1 mb-5">Perubahan langsung dipakai loket, struk, validasi penarikan, dan saran isian kelas.</p>

        <form method="POST" action="{{ route('admin.config.update') }}" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 space-y-5" id="config-form">
            @csrf @method('PUT')

            <div>
                <x-input-label for="bank_name">Nama bank mini</x-input-label>
                <x-input id="bank_name" name="bank_name" :value="$configs->get('bank_name')->config_value ?? 'Bank Mini Sekolah'" required
                    aria-describedby="bank-name-help" />
                <p id="bank-name-help" class="text-[13px] text-stone-600 mt-1.5">Tampil di struk, laporan, dan kartu penabung.</p>
                <x-input-error :message="$errors->first('bank_name')" />
            </div>

            <div>
                <x-input-label for="minimum_balance">Saldo mengendap (Rp) wajib</x-input-label>
                <x-input id="minimum_balance" name="minimum_balance" type="number" inputmode="numeric" :value="$configs->get('minimum_balance')->config_value ?? 10000" min="0" required
                    aria-describedby="min-help" />
                <p id="min-help" class="text-[13px] text-stone-600 mt-1.5">Sisa penarikan tidak boleh di bawah ini.</p>
                <x-input-error :message="$errors->first('minimum_balance')" />
            </div>

            <div>
                <x-input-label for="min_deposit_to_activate">Setoran minimal aktivasi anggota (Rp) wajib</x-input-label>
                <x-input id="min_deposit_to_activate" name="min_deposit_to_activate" type="number" inputmode="numeric" :value="$configs->get('min_deposit_to_activate')->config_value ?? 10000" min="0" required
                    aria-describedby="activate-help" />
                <p id="activate-help" class="text-[13px] text-stone-600 mt-1.5">Anggota otomatis menjadi penabung setelah setoran minimal ini.</p>
                <x-input-error :message="$errors->first('min_deposit_to_activate')" />
            </div>

            <div>
                <x-input-label for="class_majors">Jurusan <span class="text-stone-500 font-normal">(pisahkan dengan koma)</span></x-input-label>
                <x-input id="class_majors" name="class_majors" :value="$configs->get('class_majors')->config_value ?? implode(', ', \App\Support\ClassRoom::DEFAULT_MAJORS)"
                    aria-describedby="majors-help" />
                <p id="majors-help" class="text-[13px] text-stone-600 mt-1.5">Daftar jurusan untuk saran isian kolom kelas anggota dan penabung. Kelas tetap bisa diketik bebas. Contoh: RPL, TKJ, DKV.</p>
                <x-input-error :message="$errors->first('class_majors')" />
            </div>

            <div class="pt-1">
                <x-primary-button id="config-submit" data-loading-text="Menyimpan..." class="w-full sm:w-auto sm:min-w-[220px]">
                    Simpan konfigurasi
                </x-primary-button>
            </div>
        </form>
    </div>

@endsection
