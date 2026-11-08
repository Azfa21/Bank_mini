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
            default => 'login',
        };

        $menuItems = [];

        if ($role !== null) {
            // Alasan: satu perhitungan status aktif dipakai item langsung maupun item di dalam grup
            // supaya penanda halaman aktif tidak pernah berbeda antar keduanya.
            $isActive = static fn (array $entry): bool => collect($entry['routes'] ?? [])->contains(fn ($pattern) => request()->routeIs($pattern))
                || collect($entry['paths'] ?? [])->contains(fn ($pattern) => request()->is($pattern));

            $toLink = static fn (array $entry): array => [
                'label' => $entry['label'],
                'url' => route($entry['route']),
                'active' => $isActive($entry),
            ];

            $menuItems[] = [
                'label' => 'Dashboard',
                'url' => route($dashboardRouteName),
                'active' => request()->routeIs($dashboardRouteName),
            ];

            foreach (config('menu.'.$role, []) as $entry) {
                if (isset($entry['items'])) {
                    $children = array_map($toLink, $entry['items']);

                    $menuItems[] = [
                        'label' => $entry['label'],
                        'children' => $children,
                        // Alasan: grup harus ikut menyala saat halaman aktif ada di dalamnya, supaya
                        // pengguna tahu posisinya tanpa membuka dropdown. Closure dipakai karena
                        // contains('active') tidak membaca kunci array.
                        'active' => collect($children)->contains(static fn (array $child): bool => $child['active']),
                    ];

                    continue;
                }

                $menuItems[] = $toLink($entry);
            }
        }

        $roleLabel = match ($role) {
            'admin' => 'Administrator',
            'teller' => 'Piket Teller',
            'nasabah' => 'Penabung',
            default => $role,
        };

        // Alasan: posisi halaman dihitung dari config/menu.php yang sama dengan navigasi,
        // jadi label breadcrumb tidak bisa menyimpang dari label menu. Hanya untuk layar
        // staf; halaman nasabah tidak memakainya.
        $breadcrumb = [];

        if (in_array($role, ['admin', 'teller'], true)) {
            foreach (config('menu.'.$role, []) as $entry) {
                if (isset($entry['items'])) {
                    foreach ($entry['items'] as $child) {
                        if ($isActive($child)) {
                            $breadcrumb = $entry['label'] === $child['label']
                                ? [$entry['label']]
                                : [$entry['label'], $child['label']];
                            break 2;
                        }
                    }

                    continue;
                }

                if ($isActive($entry)) {
                    $breadcrumb = [$entry['label']];
                    break;
                }
            }
        }
    @endphp

    <header class="bg-white border-b border-stone-200 md:sticky md:top-0 md:z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-3 min-h-[64px] py-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0" aria-hidden="true">
                        <img src="{{ asset('images/bank-mini-logo.svg') }}" alt="" width="40" height="40" class="w-10 h-10 object-contain">
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
                        <div class="text-right hidden sm:block">
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
                {{-- Alasan: menu Data mengelompokkan pengelolaan siswa dan proses akhir tahun
                     agar seluruh tujuan tetap terjangkau tanpa memenuhi bilah navigasi. --}}
                <div data-auto-hide-nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 hidden md:flex md:flex-wrap items-center gap-x-1">
                    @foreach ($menuItems as $index => $item)
                        @if (isset($item['children']))
                            <div class="relative" data-nav-group>
                                <button type="button" id="nav-group-{{ $index }}" data-nav-toggle
                                    aria-expanded="false" aria-controls="nav-group-panel-{{ $index }}"
                                    class="inline-flex items-center gap-1.5 min-h-[44px] py-2 px-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $item['active'] ? 'text-brand-900 border-brand-800' : 'text-stone-600 border-transparent hover:text-brand-900 hover:border-brand-200' }}">
                                    {{ $item['label'] }}
                                    {{-- Alasan: chevron menandai tombol ini membuka daftar, bukan pindah halaman. --}}
                                    <svg data-nav-chevron class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                                    </svg>
                                </button>
                                {{-- Alasan: panel mengambang di atas konten, jadi shadow dipakai sebagai penanda elevasi. --}}
                                <div id="nav-group-panel-{{ $index }}" data-nav-panel
                                    class="hidden absolute left-0 top-full mt-1 z-30 min-w-[230px] bg-white border border-stone-200 rounded-xl shadow-lg py-1.5">
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['url'] }}" @if ($child['active']) aria-current="page" @endif
                                            class="flex items-center min-h-[44px] px-4 text-sm {{ $child['active'] ? 'text-brand-900 bg-brand-50 font-semibold' : 'text-stone-700 hover:bg-stone-100' }}">
                                            {{ $child['label'] }}
                                            @if ($child['active'])
                                                <span class="sr-only">(halaman aktif)</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                                class="inline-flex items-center min-h-[44px] py-2 px-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $item['active'] ? 'text-brand-900 border-brand-800' : 'text-stone-600 border-transparent hover:text-brand-900 hover:border-brand-200' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
                {{-- Alasan: di layar sempit semua tujuan ditampilkan rata satu tingkat supaya tidak ada
                     tujuan yang tersembunyi di balik dua kali ketuk; panel dibatasi tinggi viewport
                     agar header tidak menelan seluruh layar. --}}
                <div id="menu-panel" class="md:hidden hidden border-t border-stone-100 bg-white">
                    <div class="max-w-7xl mx-auto px-4 py-2 flex flex-col max-h-[70vh] overflow-y-auto">
                        @foreach ($menuItems as $item)
                            @if (isset($item['children']))
                                <p class="px-2 pt-3 pb-1 text-[13px] font-semibold text-stone-500">{{ $item['label'] }}</p>
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" @if ($child['active']) aria-current="page" @endif
                                        class="inline-flex items-center min-h-[48px] px-2 text-[15px] font-medium rounded-lg {{ $child['active'] ? 'text-brand-900 bg-brand-50' : 'text-stone-700 hover:bg-stone-100' }}">
                                        {{ $child['label'] }}
                                        @if ($child['active'])
                                            <span class="sr-only">(halaman aktif)</span>
                                        @endif
                                    </a>
                                @endforeach
                            @else
                                <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                                    class="inline-flex items-center min-h-[48px] px-2 text-[15px] font-medium rounded-lg {{ $item['active'] ? 'text-brand-900 bg-brand-50' : 'text-stone-700 hover:bg-stone-100' }}">
                                    {{ $item['label'] }}
                                    @if ($item['active'])
                                        <span class="sr-only">(halaman aktif)</span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif
    </header>

    <main id="konten-utama" class="w-full max-w-7xl mx-auto flex-1 px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
        @if (count($breadcrumb))
            <nav aria-label="Posisi halaman" class="mb-3 text-[13px] text-stone-600 print:hidden">
                <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                    <li><a href="{{ route($dashboardRouteName) }}" class="hover:text-brand-900 hover:underline">Dashboard</a></li>
                    @foreach ($breadcrumb as $crumb)
                        <li aria-hidden="true" class="text-stone-500">/</li>
                        <li @if ($loop->last) aria-current="page" class="font-medium text-stone-900" @endif>{{ $crumb }}</li>
                    @endforeach
                </ol>
            </nav>
        @endif

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
            <p class="font-medium text-stone-800">&copy; 2026 Bank Mini Skansaba. Seluruh hak cipta dilindungi.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
