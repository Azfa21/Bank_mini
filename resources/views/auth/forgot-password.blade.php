<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password & Reset PIN — Bank Mini Sekolah</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        /* Fallback styling in case Vite build is running pure CSS */
        .tab-btn.active {
            border-bottom: 2px solid #2563eb;
            color: #2563eb;
            font-weight: 600;
        }
        .tab-btn:not(.active) {
            border-bottom: 2px solid transparent;
            color: #6b7280;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl shadow-sm mb-3">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Pemulihan Kredensial</h1>
            <p class="text-sm text-gray-500 mt-1">Reset password akun pegawai atau PIN nasabah</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-50/50">
                <button
                    type="button"
                    id="tab-btn-staff"
                    onclick="switchTab('staff')"
                    class="tab-btn flex-1 py-3 text-center text-sm font-medium transition-colors active"
                >
                    Pegawai / Staff
                </button>
                <button
                    type="button"
                    id="tab-btn-nasabah"
                    onclick="switchTab('nasabah')"
                    class="tab-btn flex-1 py-3 text-center text-sm font-medium transition-colors"
                >
                    Nasabah / Siswa
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
                    <div class="mb-4 p-3.5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <x-form-errors class="rounded-xl" />

                <!-- Staff Reset Form -->
                <form id="form-staff" method="POST" action="{{ route('password.staff.reset') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="staff_username">Username</x-input-label>
                        <x-input id="staff_username" name="username" required
                            placeholder="Contoh: admin, teller1, supervisor1" />
                    </div>

                    <div>
                        <x-input-label for="staff_full_name">Nama Lengkap Terdaftar</x-input-label>
                        <x-input id="staff_full_name" name="full_name" required
                            placeholder="Masukkan nama lengkap untuk verifikasi identitas" />
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

                    <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Reset Password
                    </x-primary-button>
                </form>

                <!-- Nasabah Reset Form -->
                <form id="form-nasabah" method="POST" action="{{ route('password.nasabah.reset') }}" class="space-y-4 hidden">
                    @csrf
                    <div>
                        <x-input-label for="nasabah_student_number">Nomor Siswa (NIS)</x-input-label>
                        <x-input id="nasabah_student_number" name="student_number" required placeholder="Contoh: 2023001" />
                    </div>

                    <div>
                        <x-input-label for="nasabah_class_name">Kelas Terdaftar</x-input-label>
                        <x-input id="nasabah_class_name" name="class_name" required placeholder="Contoh: X RPL 1, XI AKL 2" />
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

                    <x-primary-button class="w-full py-2.5 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Reset PIN Nasabah
                    </x-primary-button>
                </form>
            </div>

            <!-- Footer links -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Halaman Login
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} Bank Mini Sekolah</p>
    </div>

    <script>
        function switchTab(tab) {
            const btnStaff = document.getElementById('tab-btn-staff');
            const btnNasabah = document.getElementById('tab-btn-nasabah');
            const formStaff = document.getElementById('form-staff');
            const formNasabah = document.getElementById('form-nasabah');

            if (tab === 'staff') {
                btnStaff.classList.add('active');
                btnNasabah.classList.remove('active');
                formStaff.classList.remove('hidden');
                formNasabah.classList.add('hidden');
            } else {
                btnNasabah.classList.add('active');
                btnStaff.classList.remove('active');
                formNasabah.classList.remove('hidden');
                formStaff.classList.add('hidden');
            }
        }

        @if(old('student_number') || old('class_name'))
            switchTab('nasabah');
        @endif
    </script>
</body>
</html>
