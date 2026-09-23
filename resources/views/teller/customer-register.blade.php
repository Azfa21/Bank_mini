@extends('layouts.app')

@section('title', 'Registrasi Nasabah — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">NASABAH BARU</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Daftarkan nasabah</h1>
            <p class="text-sm text-stone-600 mt-1">Didampingi Teller. Nomor rekening dibuat otomatis.</p>
        </div>
        <a href="{{ route('teller.customers.search') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke pencarian</a>
    </div>

    @if (session('registration'))
        @php $reg = session('registration'); @endphp
        <div role="status" class="mb-4 bg-brand-50 border border-brand-200 rounded-2xl p-5">
            <p class="text-[15px] font-bold text-brand-900">Registrasi berhasil. Catat untuk nasabah.</p>
            <dl class="mt-3 text-sm text-brand-950 space-y-1.5">
                <div class="flex justify-between gap-3"><dt>Nama</dt><dd class="font-semibold">{{ $reg['full_name'] }}</dd></div>
                <div class="flex justify-between gap-3"><dt>NIS</dt><dd class="tnum font-semibold">{{ $reg['student_number'] }}</dd></div>
                <div class="flex justify-between gap-3"><dt>No. rekening</dt><dd class="tnum font-semibold">{{ $reg['account_number'] }}</dd></div>
            </dl>
        </div>
    @endif

    <div>
        <form method="POST" action="{{ route('teller.customers.register.store') }}" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 space-y-5" id="register-form">
            @csrf

            <div>
                <x-input-label for="full_name">Nama lengkap sesuai kartu pelajar wajib</x-input-label>
                <x-input id="full_name" name="full_name" :value="old('full_name')" required autocomplete="name" placeholder="Contoh: Ani Wijaya" />
                <x-input-error :message="$errors->first('full_name')" />
            </div>

            <div>
                <x-input-label for="class_name">Kelas wajib</x-input-label>
                <x-input id="class_name" name="class_name" :value="old('class_name')" placeholder="Contoh: VII A" required />
                <x-input-error :message="$errors->first('class_name')" />
            </div>

            <div>
                <x-input-label for="phone_number">Nomor HP (opsional)</x-input-label>
                <x-input id="phone_number" name="phone_number" :value="old('phone_number')" inputmode="tel" autocomplete="tel" placeholder="Contoh: 0812xxxx" />
                <x-input-error :message="$errors->first('phone_number')" />
            </div>

            <div>
                <x-input-label for="email">Email wajib</x-input-label>
                <x-input id="email" name="email" type="email" :value="old('email')" autocomplete="email" placeholder="Contoh: ani.wijaya@example.test" required />
                <p class="text-[13px] text-stone-600 mt-1.5">Dipakai nasabah untuk verifikasi jika lupa PIN.</p>
                <x-input-error :message="$errors->first('email')" />
            </div>

            <div>
                <x-input-label for="pin">PIN awal 6 digit wajib</x-input-label>
                <x-input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6"
                    autocomplete="off" class="tracking-[0.3em] text-center" placeholder="••••••" required
                    aria-describedby="pin-help" />
                <p id="pin-help" class="text-[13px] text-stone-600 mt-1.5">Minta nasabah membuat sendiri dan menyimpannya. Teller tidak boleh tahu PIN setelah ini.</p>
                <x-input-error :message="$errors->first('pin')" />
            </div>

            <x-primary-button id="register-submit" data-loading-text="Mendaftarkan..." class="w-full">
                Daftarkan nasabah
            </x-primary-button>
        </form>
    </div>

@endsection
