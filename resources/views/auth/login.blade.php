<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Layanan Loket &middot; Bank Mini Skansaba</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-paper text-ink flex items-center justify-center px-4 py-8">
    <main class="w-full max-w-md">
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl shrink-0 overflow-hidden" aria-hidden="true">
                    <img src="{{ asset('images/bank-mini-logo.svg') }}" alt="" width="48" height="48" class="w-12 h-12 object-contain">
                </div>
                <div>
                    <p class="text-[15px] font-semibold text-stone-900 leading-tight">Bank Mini Skansaba</p>
                    <p class="text-[13px] text-stone-600 leading-tight">SMKN 1 Bantul</p>
                </div>
            </div>

            <h1 class="text-[26px] leading-tight font-bold text-stone-900 mt-5">Masuk untuk melayani transaksi</h1>
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 ledger-rule shadow-sm p-6 sm:p-7">
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
                    <x-input-label for="identifier">Username</x-input-label>
                    <x-input id="identifier" name="identifier" required autofocus autocomplete="username"
                        aria-describedby="identifier-help"
                        placeholder="" />
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 mb-1.5">
                        <x-input-label for="secret" flush>Password</x-input-label>
                        <a href="{{ route('password.request') }}" class="tap-target inline-flex items-center text-[13px] font-semibold text-brand-900 hover:underline">
                            Lupa password?
                        </a>
                    </div>
                    <div class="relative">
                        <x-input id="secret" name="secret" type="password" required autocomplete="current-password"
                            aria-describedby="secret-help" class="pr-24"
                            placeholder="" />
                        <button type="button" id="toggle-secret" aria-pressed="false" aria-controls="secret" class="absolute right-1.5 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-20 min-h-[44px] text-[13px] font-semibold text-stone-700 rounded-lg hover:bg-stone-100">
                            Tampil
                        </button>
                    </div>
                </div>

                <x-primary-button id="login-submit" data-loading-text="Memeriksa akun..." class="w-full">
                    Masuk ke loket
                </x-primary-button>
            </form>
        </div>

        <p class="text-[13px] text-stone-600 mt-5">&copy; 2026 Bank Mini Skansaba. Seluruh hak cipta dilindungi.</p>
    </main>

</body>
</html>
