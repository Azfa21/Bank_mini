@extends('layouts.app')

@section('title', 'Registrasi Nasabah — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Registrasi Nasabah</h1>
            <p class="text-sm text-gray-500 mt-1">Pendaftaran nasabah didampingi Teller. Nomor rekening dibuat otomatis oleh sistem.</p>
        </div>
        <a href="{{ route('teller.customers.search') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Pencarian nasabah</a>
    </div>

    @if (session('registration'))
        @php $reg = session('registration'); @endphp
        <div class="mb-6 max-w-lg bg-green-50 border border-green-200 rounded-xl p-5">
            <p class="text-sm font-semibold text-green-800">Registrasi berhasil.</p>
            <div class="mt-3 text-sm text-green-900 space-y-1">
                <div class="flex justify-between"><span>Nama</span><span class="font-medium">{{ $reg['full_name'] }}</span></div>
                <div class="flex justify-between"><span>NIS</span><span class="font-mono">{{ $reg['student_number'] }}</span></div>
                <div class="flex justify-between"><span>Nomor Rekening</span><span class="font-mono">{{ $reg['account_number'] }}</span></div>
            </div>
            <p class="text-xs text-green-700 mt-3">Catat NIS dan nomor rekening untuk diserahkan kepada nasabah.</p>
        </div>
    @endif

    <div class="max-w-lg">
        <form method="POST" action="{{ route('teller.customers.register.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf

            <div>
                <x-input-label for="full_name">Nama Lengkap *</x-input-label>
                <x-input id="full_name" name="full_name" :value="old('full_name')" required />
                <x-input-error :message="$errors->first('full_name')" />
            </div>

            <div>
                <x-input-label for="class_name">Kelas *</x-input-label>
                <x-input id="class_name" name="class_name" :value="old('class_name')" placeholder="Contoh: VII A" required />
                <x-input-error :message="$errors->first('class_name')" />
            </div>

            <div>
                <x-input-label for="phone_number">No. HP (opsional)</x-input-label>
                <x-input id="phone_number" name="phone_number" :value="old('phone_number')" />
                <x-input-error :message="$errors->first('phone_number')" />
            </div>

            <div>
                <x-input-label for="pin">PIN Awal Nasabah (6 digit) *</x-input-label>
                <x-input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6"
                    autocomplete="off" class="tracking-widest" required />
                <x-input-error :message="$errors->first('pin')" />
                <p class="text-xs text-gray-400 mt-1">PIN digunakan nasabah saat penarikan. Minta nasabah menyimpan PIN ini dengan aman.</p>
            </div>

            <x-primary-button class="w-full py-2.5">
                Daftarkan Nasabah
            </x-primary-button>
        </form>
    </div>
@endsection
