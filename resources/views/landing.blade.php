<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bank Mini Sekolah: Loket dan aplikasi Android untuk siswa.">
    <title>Bank Mini Sekolah &middot; Tabungan siswa dan loket</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen flex flex-col bg-paper text-ink antialiased">
    @php
        $staff = Auth::guard('web')->user();
        $dashboardUrl = $staff
            ? route($staff->role === 'admin' ? 'admin.dashboard' : 'teller.dashboard')
            : null;

        $apkUrl = trim((string) config('bankmini.apk_url')) ?: null;
        $apkIsLocal = false;

        if ($apkUrl === null && is_file(public_path('unduhan/bank-mini-nasabah.apk'))) {
            $apkUrl = asset('unduhan/bank-mini-nasabah.apk');
            $apkIsLocal = true;
        }
    @endphp

    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-brand-800 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">
        Lewati ke konten utama
    </a>

    <header class="border-b border-stone-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3 min-h-[68px] py-2">
                <a href="{{ route('home') }}" class="flex items-center gap-3 min-w-0 min-h-[44px]" aria-label="Bank Mini Sekolah, halaman awal">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-brand-800 rounded-xl shrink-0" aria-hidden="true">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold text-stone-900 leading-tight truncate">Bank Mini Sekolah</span>
                        <span class="block text-xs text-stone-600 leading-tight truncate">Loket dan tabungan siswa</span>
                    </span>
                </a>

                <div class="shrink-0">
                    @if ($staff)
                        <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-lg bg-brand-800 text-sm font-semibold text-white transition-colors hover:bg-brand-900">
                            Buka dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-lg bg-brand-800 text-sm font-semibold text-white transition-colors hover:bg-brand-900">
                            Masuk loket
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main id="konten-utama" class="flex-1">
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-14 sm:pt-14 sm:pb-16 lg:pt-20 lg:pb-20" aria-labelledby="hero-title">
            <div class="max-w-3xl">
                <div>
                    <div class="w-12 border-t-[3px] border-brand-800" aria-hidden="true"></div>
                    <h1 id="hero-title" class="mt-5 text-[34px] leading-[1.08] font-bold tracking-tight text-stone-900 text-balance sm:text-[44px] lg:text-[52px]">
                        Tabungan siswa, tercatat dari loket sampai ponsel.
                    </h1>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-stone-600 sm:text-lg">
                        Setoran, penarikan, dan transfer dilayani di loket sekolah. Siswa memantau saldo, mutasi, dan kartu tabungan lewat aplikasi Bank Mini.
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @if ($staff)
                            <a href="{{ $dashboardUrl }}" class="inline-flex items-center justify-center min-h-[48px] px-5 rounded-lg bg-brand-800 text-sm font-semibold text-white transition-colors hover:bg-brand-900">
                                Buka dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center min-h-[48px] px-5 rounded-lg bg-brand-800 text-sm font-semibold text-white transition-colors hover:bg-brand-900">
                                Masuk ke loket
                            </a>
                        @endif

                        @if ($apkUrl)
                            <a href="{{ $apkUrl }}"
                                @if ($apkIsLocal) download="bank-mini-nasabah.apk" @else target="_blank" rel="noopener" @endif
                                class="inline-flex items-center justify-center min-h-[48px] px-5 rounded-lg border border-stone-300 bg-white text-sm font-semibold text-stone-800 transition-colors hover:bg-stone-50">
                                {{ $apkIsLocal ? 'Unduh aplikasi nasabah' : 'Buka tautan unduhan' }}
                            </a>
                        @endif
                    </div>

                    @if (! $apkUrl)
                        <p class="mt-4 text-xs text-stone-600 sm:text-sm">
                            Aplikasi nasabah untuk siswa. Berkas APK dapat diminta ke petugas loket.
                        </p>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-stone-900">Bank Mini Sekolah</p>
                <p class="mt-0.5 text-xs text-stone-600">&copy; {{ date('Y') }} Bank Mini Sekolah</p>
            </div>
        </div>
    </footer>
</body>
</html>
