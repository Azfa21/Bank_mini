@extends('layouts.app')

@section('title', 'Ubah PIN Transaksi | Bank Mini')
@section('section', 'Penabung')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Ubah PIN Transaksi</h1>
        <p class="text-sm text-stone-500 mb-6">
            PIN dipakai hanya saat transaksi di loket teller, bukan untuk masuk
            ke aplikasi ini. Password login tidak berubah di halaman ini.
        </p>

        <x-form-errors />

        @if (session('success'))
            <div role="status" class="mb-4 p-4 bg-brand-50 border border-brand-200 text-brand-900 text-sm rounded-xl">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('nasabah.pin.update') }}" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <x-input-label for="current_pin">PIN Saat Ini</x-input-label>
                <x-input id="current_pin" name="current_pin" type="password" inputmode="numeric" maxlength="6"
                    class="tracking-[0.3em] text-center" autocomplete="off" required />
                <p class="text-[13px] text-stone-600 mt-1.5">
                    Salah 3 kali berturut-turut mengunci transaksi selama 15 menit.
                </p>
                <x-input-error :message="$errors->first('current_pin')" />
            </div>

            <div>
                <x-input-label for="pin">PIN Baru (6 Digit)</x-input-label>
                <x-input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6"
                    class="tracking-[0.3em] text-center" autocomplete="new-password" required />
                <p class="text-[13px] text-stone-600 mt-1.5">Pilih yang mudah Anda ingat tapi bukan tanggal lahir atau NIS.</p>
                <x-input-error :message="$errors->first('pin')" />
            </div>

            <div>
                <x-input-label for="pin_confirmation">Konfirmasi PIN Baru</x-input-label>
                <x-input id="pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric" maxlength="6"
                    class="tracking-[0.3em] text-center" autocomplete="new-password" required />
                <x-input-error :message="$errors->first('pin_confirmation')" />
            </div>

            <div class="flex gap-3 pt-2">
                <x-primary-button data-loading-text="Menyimpan PIN...">
                    Simpan PIN baru
                </x-primary-button>
                <a href="{{ route('nasabah.dashboard') }}"
                    class="inline-flex items-center justify-center min-h-[44px] px-5 text-sm font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
