@php
    $activeType = old('account_type', 'staff');
    $stepTwo = old('otp') !== null || session('otp_sent');
    $staffStepTwo = $stepTwo && $activeType === 'staff';
    $nasabahStepTwo = $stepTwo && $activeType === 'nasabah';
    $email = session('email');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password &middot; Bank Mini Sekolah</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-paper text-ink flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-brand-800 rounded-xl shrink-0" aria-hidden="true">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[15px] font-semibold text-stone-900 leading-tight">Bank Mini Sekolah</p>
                    <p class="text-[13px] text-stone-600 leading-tight">Loket teller dan tabungan siswa</p>
                </div>
            </div>

            <h1 class="text-[26px] leading-tight font-bold text-stone-900 mt-5">Lupa password</h1>
            <p class="text-sm text-stone-600 mt-1.5">Masukkan username atau nomor siswa Anda. Kode dikirim ke email terdaftar atas nama akun itu, berlaku {{ config('recovery.otp_expire_minutes', 10) }} menit, dan hanya bisa dipakai sekali. Penabung yang lupa password memakai alur yang sama untuk membuat password baru.</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl border border-stone-200 ledger-rule overflow-hidden">
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
                            @if ($email)
                                Kode verifikasi dikirim ke <span class="font-semibold text-stone-800">{{ $email }}</span>.
                                Cek kotak masuk akun <span class="font-semibold text-stone-800">{{ old('identifier') }}</span>,
                                lalu masukkan kode dan password baru di bawah ini.
                            @else
                                Masukkan kode verifikasi yang dikirim ke email terdaftar akun ini.
                            @endif
                        </div>
                        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="staff">
                            <input type="hidden" name="identifier" value="{{ old('identifier') }}">

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
                            <input type="hidden" name="identifier" value="{{ old('identifier') }}">
                            <button type="submit" class="text-sm font-medium text-brand-700 hover:text-brand-800">Kirim ulang kode</button>
                            <span class="text-stone-300 mx-2">•</span>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">Ganti username</a>
                        </form>
                    @else
                        <form method="POST" action="{{ route('password.otp.request') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="staff">
                            <div>
                                <x-input-label for="staff_identifier">Username Akun</x-input-label>
                                <x-input id="staff_identifier" name="identifier" required autocomplete="username"
                                    aria-describedby="staff_identifier-help"
                                    placeholder="Contoh: teller1" />
                                <p id="staff_identifier-help" class="text-[13px] text-stone-600 mt-1.5">
                                    Kalau username lupa, minta Administrator mengatur ulang password Anda di loket.
                                </p>
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
                            @if ($email)
                                Kode verifikasi dikirim ke <span class="font-semibold text-stone-800">{{ $email }}</span>.
                                Cek kotak masuk akun nomor siswa <span class="font-semibold text-stone-800">{{ old('identifier') }}</span>,
                                lalu masukkan kode dan password baru di bawah ini.
                            @else
                                Masukkan kode verifikasi yang dikirim ke email terdaftar akun ini.
                            @endif
                        </div>
                        <form method="POST" action="{{ route('password.reset.submit') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <input type="hidden" name="identifier" value="{{ old('identifier') }}">

                            <div>
                                <x-input-label for="nasabah_otp">Kode Verifikasi (6 Digit)</x-input-label>
                                <x-input id="nasabah_otp" name="otp" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    autocomplete="one-time-code" placeholder="Masukkan kode dari email" />
                            </div>

                            <div>
                                <x-input-label for="nasabah_password">Password Baru</x-input-label>
                                <x-input id="nasabah_password" name="password" type="password" required minlength="8"
                                    placeholder="Minimal 8 karakter" />
                            </div>

                            <div>
                                <x-input-label for="nasabah_password_confirmation">Konfirmasi Password Baru</x-input-label>
                                <x-input id="nasabah_password_confirmation" name="password_confirmation" type="password" required minlength="8"
                                    placeholder="Ulangi password baru" />
                            </div>

                            <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-brand-600 focus:ring-offset-2">
                                Verifikasi &amp; Simpan Password
                            </x-primary-button>
                        </form>

                        <form method="POST" action="{{ route('password.otp.request') }}" class="mt-4 text-center">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <input type="hidden" name="identifier" value="{{ old('identifier') }}">
                            <button type="submit" class="text-sm font-medium text-brand-700 hover:text-brand-800">Kirim ulang kode</button>
                            <span class="text-stone-300 mx-2">•</span>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-stone-500 hover:text-stone-700">Ganti nomor siswa</a>
                        </form>
                    @else
                        <form method="POST" action="{{ route('password.otp.request') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="account_type" value="nasabah">
                            <div>
                                <x-input-label for="nasabah_identifier">Nomor Siswa</x-input-label>
                                <x-input id="nasabah_identifier" name="identifier" required inputmode="numeric"
                                    aria-describedby="nasabah_identifier-help"
                                    placeholder="Contoh: 2023001" />
                                <p id="nasabah_identifier-help" class="text-[13px] text-stone-600 mt-1.5">
                                    Masukkan nomor siswa yang tercetak di kartu rekening.
                                </p>
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

        <p class="text-[13px] text-stone-600 mt-5">&copy; {{ date('Y') }} Bank Mini Sekolah</p>
    </div>

</body>
</html>
