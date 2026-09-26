<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bank Mini Sekolah')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
    <body class="min-h-screen flex flex-col bg-[#f7f1e8] text-stone-900 antialiased">
    <a href="#konten-utama" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-brand-800 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">
        Lewati ke konten utama
    </a>

    @php
        $currentUser = Auth::guard('web')->user() ?? Auth::guard('nasabah')->user();
        $role = $currentUser ? ($currentUser->role ?? 'nasabah') : null;
        $dashboardRouteName = match ($role) {
            'admin' => 'admin.dashboard',
            'teller' => 'teller.dashboard',
            'supervisor' => 'supervisor.dashboard',
            default => 'nasabah.dashboard',
        };

        $menuItems = [];

        if ($role !== null) {
            $menuItems[] = [
                'label' => 'Dashboard',
                'url' => route($dashboardRouteName),
                'active' => request()->routeIs($dashboardRouteName),
            ];

            foreach (config('menu.'.$role, []) as $item) {
                $active = collect($item['routes'] ?? [])->contains(fn ($pattern) => request()->routeIs($pattern))
                    || collect($item['paths'] ?? [])->contains(fn ($pattern) => request()->is($pattern));

                $menuItems[] = [
                    'label' => $item['label'],
                    'url' => route($item['route']),
                    'active' => $active,
                ];
            }
        }

        $roleLabel = match ($role) {
            'admin' => 'Administrator',
            'teller' => 'Piket Teller',
            'supervisor' => 'Supervisor',
            'nasabah' => 'Penabung',
            default => $role,
        };
    @endphp

    <header class="bg-white border-b border-stone-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3 min-h-[64px] py-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-brand-800 rounded-xl flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-6 9 6M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9m-4 6H8"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-stone-900 leading-tight truncate">Bank Mini Sekolah</p>
                        <p class="text-xs text-stone-600 leading-tight truncate">@yield('section', 'Loket')</p>
                    </div>
                    @if ($roleLabel)
                        <span class="hidden sm:inline-flex items-center rounded-full border border-brand-800/30 bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-900">
                            {{ $roleLabel }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if ($currentUser)
                        <div class="text-right hidden xs:block sm:block">
                            <p class="text-sm font-medium text-stone-900 leading-tight truncate max-w-[160px]">{{ $currentUser->full_name }}</p>
                            <p class="text-xs text-stone-600 leading-tight">{{ $roleLabel }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-medium text-stone-700 border border-stone-300 rounded-lg hover:bg-stone-100 transition-colors">
                                Keluar
                            </button>
                        </form>
                        @if (count($menuItems))
                            <button type="button" id="menu-toggle" aria-expanded="false" aria-controls="menu-panel" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 text-sm font-medium text-white bg-brand-800 rounded-lg hover:bg-brand-900 transition-colors md:hidden">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                Menu
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        @if ($currentUser && count($menuItems))
            <nav aria-label="Navigasi utama" class="border-t border-stone-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 hidden md:flex items-center gap-1">
                    @foreach ($menuItems as $item)
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                            class="inline-flex items-center min-h-[44px] py-2 px-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $item['active'] ? 'text-brand-900 border-brand-800' : 'text-stone-600 border-transparent hover:text-brand-900 hover:border-brand-200' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
                <div id="menu-panel" class="md:hidden hidden border-t border-stone-100 bg-white">
                    <div class="max-w-7xl mx-auto px-4 py-2 flex flex-col">
                        @foreach ($menuItems as $item)
                            <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                                class="inline-flex items-center min-h-[48px] px-2 text-[15px] font-medium rounded-lg {{ $item['active'] ? 'text-brand-900 bg-brand-50' : 'text-stone-700 hover:bg-stone-100' }}">
                                {{ $item['label'] }}
                                @if ($item['active'])
                                    <span class="sr-only">(halaman aktif)</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif
    </header>

    <main id="konten-utama" class="w-full max-w-7xl mx-auto flex-1 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
        @if (session('success'))
            <div role="status" class="mb-4 p-4 bg-brand-50 border border-brand-200 text-brand-900 text-sm rounded-xl flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl flex items-start gap-2.5">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-xs text-stone-600">
            <p class="font-medium text-stone-800">Bank Mini Sekolah</p>
            <p class="hidden sm:inline" aria-hidden="true">·</p>
            <p>Jam loket {{ \App\Models\SystemConfig::value('operational_hours', '07.30 - 14.00 WIB') }}</p>
            <p class="sm:ml-auto">Simpan slip dan struk sebagai bukti transaksi.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
