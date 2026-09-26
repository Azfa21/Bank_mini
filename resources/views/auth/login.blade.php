<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Loket — Bank Mini Sekolah</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#f7f1e8] text-stone-900 flex items-center justify-center px-4 py-8">
    <main class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-brand-800 rounded-2xl mb-4" aria-hidden="true">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                </svg>
            </div>
            <p class="text-xs font-semibold tracking-normal text-brand-900">LOKET BANK MINI SEKOLAH</p>
            <h1 class="text-[26px] leading-tight font-bold text-stone-900 mt-1">Masuk untuk melayani transaksi</h1>
            <p class="text-sm text-stone-600 mt-2">Teller, Supervisor, dan Administrator memakai username. Nasabah memakai nomor siswa dan PIN.</p>
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-6 sm:p-7" style="box-shadow: 0 1px 2px rgb(28 25 23 / 0.06);">
            @if (session('success'))
                <div role="status" class="mb-4 p-3.5 bg-brand-50 border border-brand-200 text-brand-900 text-sm rounded-xl">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl">
                    {{ session('error') }}
                </div>
            @endif

            <x-form-errors />

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="identifier">Username atau nomor siswa</x-input-label>
                    <x-input id="identifier" name="identifier" required autofocus autocomplete="username"
                        aria-describedby="identifier-help"
                        placeholder="Contoh: teller1 atau 2023001" />
                    <p id="identifier-help" class="text-[13px] text-stone-600 mt-1.5">Pegawai memakai username. Siswa memakai NIS.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <x-input-label for="secret" class="mb-0">Password atau PIN</x-input-label>
                        <a href="{{ route('password.request') }}" class="inline-flex items-center min-h-[44px] text-[13px] font-semibold text-brand-900 hover:underline">
                            Lupa password atau PIN?
                        </a>
                    </div>
                    <div class="relative">
                        <x-input id="secret" name="secret" type="password" required autocomplete="current-password"
                            aria-describedby="secret-help" class="pr-20"
                            placeholder="Password pegawai atau PIN 6 digit" />
                        <button type="button" id="toggle-secret" aria-pressed="false" aria-controls="secret" class="absolute right-1.5 top-1/2 -translate-y-1/2 inline-flex items-center justify-center min-h-[44px] px-3 text-[13px] font-semibold text-stone-700 rounded-lg hover:bg-stone-100">
                            Tampil
                        </button>
                    </div>
                    <p id="secret-help" class="text-[13px] text-stone-600 mt-1.5">PIN nasabah tepat 6 angka. Jangan bagikan ke siapa pun.</p>
                </div>

                <x-primary-button id="login-submit" data-loading-text="Memeriksa akun..." class="w-full">
                    Masuk ke loket
                </x-primary-button>

                <div class="text-center border-t border-stone-100 pt-4">
                    <a href="{{ route('password.request') }}" class="inline-flex items-center justify-center min-h-[44px] text-[13px] text-stone-600 hover:text-stone-900">
                        Kesulitan masuk? <span class="text-brand-900 font-semibold hover:underline ml-1">Pulihkan akun</span>
                    </a>
                </div>
            </form>
        </div>

        <p class="text-center text-[13px] text-stone-600 mt-5">Jam loket {{ \App\Models\SystemConfig::value('operational_hours', '07.30 - 14.00 WIB') }} · &copy; {{ date('Y') }} Bank Mini Sekolah</p>
    </main>

</body>
</html>
