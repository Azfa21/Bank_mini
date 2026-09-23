@php
    $activeType = old('account_type', 'staff');
    $stepTwo = old('otp') !== null || session('otp_sent');
    $staffStepTwo = $stepTwo && $activeType === 'staff';
    $nasabahStepTwo = $stepTwo && $activeType === 'nasabah';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password & Reset PIN — Bank Mini Sekolah</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-stone-50 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-700 rounded-2xl shadow-sm mb-3">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-stone-900">Pemulihan Kredensial</h1>
            <p class="text-sm text-stone-500 mt-1">Verifikasi email untuk reset password pegawai atau PIN nasabah</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-stone-200 bg-stone-50" role="tablist" aria-label="Pilih jenis akun">
                <button
                    type="button"
                    role="tab"
                    id="tab-btn-staff"
                    aria-selected="{{ $activeType === 'staff' ? 'true' : 'false' }}"
                    aria-controls="form-staff"
                    class="tab-btn flex-1 min-h-[48px] py-3 text-center text-sm font-medium transition-colors {{ $activeType === 'staff' ? 'active' : '' }}"
                >
                    Pegawai
                </button>
                <button
                    type="button"
                    role="tab"
                    id="tab-btn-nasabah"
                    aria-selected="{{ $activeType === 'nasabah' ? 'true' : 'false' }}"
                    aria-controls="form-nasabah"
                    tabindex="-1"
                    class="tab-btn flex-1 min-h-[48px] py-3 text-center text-sm font-medium transition-colors {{ $activeType === 'nasabah' ? 'active' : '' }}"
                >
                    Nasabah
                </button>
            </div>

            <div class="p-6">
                <!-- Session Alerts -->
                @if (session('error'))
                    <div class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 p-3.5 bg-olive-50 border border-olive-200 text-olive-700 text-sm rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-olive-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <x-form-errors class="rounded-xl" />

                <!-- Staff Panel -->
                <div id="form-staff" class="{{ $activeType === 'staff' ? '' : 'hidden' }}">
                    @if ($staffStepTwo)
                        <div class="mb-4 p-3.5 bg-stone-50 border border-stone-200 text-stone-600 text-sm rounded-xl">
                            Kode verifikasi dikirim ke <span class="font-semibold text-stone-800">{{ old('email') }}</span>.
                        </div>
                        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="staff">
                            <input type="hidden" name="email" value="{{ old('email') }}">

                            <div>
                                <x-input-label for="staff_otp">Kode Verifikasi (6 Digit)</x-input-label>
                                <x-input id="staff_otp" name="otp" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    autocomplete="one-time-code" placeholder="Masukkan kode dari email" />
                            </div>

                            <div>
                                <x-input-label for="staff_password">Password Baru</x-input-label>
                                <x-input id="staff_password" name="password" type="password" required minlength="8"
                                    placeholder="Minimal 8 karakter" />
                            </div>

                            <div>
                                <x-input-label for="staff_password_confirmation">Konfirmasi Password Baru</x-input-label>
                                <x-input id="staff_password_confirmation" name="password_confirmation" type="password" required minlength="8"
                                    placeholder="Ulangi password baru" />
                            </div>

                            <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                                Verifikasi & Simpan Password
                            </x-primary-button>
                        </form>

                        <form method="POST" action="{{ route('password.otp.request') }}" class="mt-4 text-center">
                            @csrf
                            <input type="hidden" name="account_type" value="staff">
                            <input type="hidden" name="email" value="{{ old('email') }}">
                            <button type="submit" class="text-sm font-medium text-brand-700 hover:text-brand-800">Kirim ulang kode</button>
                            <span class="text-stone-300 mx-2">•</span>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">Ganti email</a>
                        </form>
                    @else
                        <form method="POST" action="{{ route('password.otp.request') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="staff">
                            <div>
                                <x-input-label for="staff_email">Email Terdaftar</x-input-label>
                                <x-input id="staff_email" name="email" type="email" required
                                    placeholder="Masukkan email akun pegawai Anda" />
                            </div>

                            <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                                Kirim Kode Verifikasi
                            </x-primary-button>
                        </form>
                    @endif
                </div>

                <!-- Nasabah Panel -->
                <div id="form-nasabah" class="{{ $activeType === 'nasabah' ? '' : 'hidden' }}" data-auto-active="{{ $activeType === 'nasabah' ? '1' : '0' }}">
                    @if ($nasabahStepTwo)
                        <div class="mb-4 p-3.5 bg-stone-50 border border-stone-200 text-stone-600 text-sm rounded-xl">
                            Kode verifikasi dikirim ke <span class="font-semibold text-stone-800">{{ old('email') }}</span>.
                        </div>
                        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <input type="hidden" name="email" value="{{ old('email') }}">

                            <div>
                                <x-input-label for="nasabah_otp">Kode Verifikasi (6 Digit)</x-input-label>
                                <x-input id="nasabah_otp" name="otp" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    autocomplete="one-time-code" placeholder="Masukkan kode dari email" />
                            </div>

                            <div>
                                <x-input-label for="nasabah_pin">PIN Baru (6 Digit)</x-input-label>
                                <x-input id="nasabah_pin" name="pin" type="password" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    placeholder="6 digit angka (contoh: 123456)" />
                            </div>

                            <div>
                                <x-input-label for="nasabah_pin_confirmation">Konfirmasi PIN Baru</x-input-label>
                                <x-input id="nasabah_pin_confirmation" name="pin_confirmation" type="password" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    placeholder="Ulangi 6 digit PIN baru" />
                            </div>

                            <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                                Verifikasi & Simpan PIN
                            </x-primary-button>
                        </form>

                        <form method="POST" action="{{ route('password.otp.request') }}" class="mt-4 text-center">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <input type="hidden" name="email" value="{{ old('email') }}">
                            <button type="submit" class="text-sm font-medium text-brand-700 hover:text-brand-800">Kirim ulang kode</button>
                            <span class="text-stone-300 mx-2">•</span>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">Ganti email</a>
                        </form>
                    @else
                        <form method="POST" action="{{ route('password.otp.request') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <div>
                                <x-input-label for="nasabah_email">Email Terdaftar</x-input-label>
                                <x-input id="nasabah_email" name="email" type="email" required
                                    placeholder="Masukkan email akun nasabah Anda" />
                            </div>

                            <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                                Kirim Kode Verifikasi
                            </x-primary-button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Footer links -->
            <div class="px-6 py-4 bg-stone-50 border-t border-stone-100 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800 inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Halaman Login
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-stone-500 mt-6">&copy; {{ date('Y') }} Bank Mini Sekolah</p>
    </div>

</body>
</html>
