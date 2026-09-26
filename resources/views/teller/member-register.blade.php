@extends('layouts.app')

@section('title', 'Registrasi Anggota — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">ANGGOTA BARU</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Daftarkan anggota</h1>
            <p class="text-sm text-stone-600 mt-1">Didampingi Teller. Rekening dibuat otomatis saat setoran aktivasi.</p>
        </div>
        <a href="{{ route('teller.members.search') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke pencarian</a>
    </div>

    @if (session('registration'))
        @php $reg = session('registration'); @endphp
        <div role="status" class="mb-4 bg-brand-50 border border-brand-200 rounded-2xl p-5">
            <p class="text-[15px] font-bold text-brand-900">Anggota berhasil didaftarkan.</p>
            <dl class="mt-3 text-sm text-brand-950 space-y-1.5">
                <div class="flex justify-between gap-3"><dt>Nama</dt><dd class="font-semibold">{{ $reg['full_name'] }}</dd></div>
                <div class="flex justify-between gap-3"><dt>NIS</dt><dd class="tnum font-semibold">{{ $reg['nis'] }}</dd></div>
                <div class="flex justify-between gap-3"><dt>Angkatan keluar</dt><dd class="font-semibold">{{ $reg['graduation_year'] ?? '-' }}</dd></div>
            </dl>
            <p class="mt-3 text-[13px] text-brand-900">Anggota ini akan menjadi penabung setelah menyetor aktivasi di Teller.</p>
        </div>
    @endif

    <div>
        <form method="POST" action="{{ route('teller.members.register.store') }}" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 space-y-5" id="register-form">
            @csrf

            <div>
                <x-input-label for="nis">NIS (opsional, dibuat otomatis bila kosong)</x-input-label>
                <x-input id="nis" name="nis" :value="old('nis')" placeholder="Contoh: 20260001" />
                <x-input-error :message="$errors->first('nis')" />
            </div>

            <div>
                <x-input-label for="full_name">Nama lengkap sesuai kartu pelajar wajib</x-input-label>
                <x-input id="full_name" name="full_name" :value="old('full_name')" required autocomplete="name" placeholder="Contoh: Ani Wijaya" />
                <x-input-error :message="$errors->first('full_name')" />
            </div>

            <div>
                <x-input-label for="graduation_year">Angkatan keluar (opsional)</x-input-label>
                <x-input id="graduation_year" name="graduation_year" :value="old('graduation_year')" placeholder="Contoh: 2026" />
                <x-input-error :message="$errors->first('graduation_year')" />
            </div>

            <x-primary-button id="register-submit" data-loading-text="Mendaftarkan..." class="w-full">
                Daftarkan anggota
            </x-primary-button>
        </form>
    </div>

@endsection
