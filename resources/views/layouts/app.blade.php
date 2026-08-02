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
<body class="min-h-screen bg-gray-50">
    @php
        $currentUser = Auth::guard('web')->user() ?? Auth::guard('nasabah')->user();
        $menuItems = [];

        if ($currentUser) {
            $role = $currentUser->role ?? 'nasabah';
            $dashboardRoute = match ($role) {
                'admin' => route('admin.dashboard'),
                'teller' => route('teller.dashboard'),
                'supervisor' => route('supervisor.dashboard'),
                default => route('nasabah.dashboard'),
            };
            $dashboardActive = match ($role) {
                'admin' => request()->routeIs('admin.dashboard'),
                'teller' => request()->routeIs('teller.dashboard'),
                'supervisor' => request()->routeIs('supervisor.dashboard'),
                default => request()->routeIs('nasabah.dashboard'),
            };
            $menuItems[] = ['label' => 'Dashboard', 'url' => $dashboardRoute, 'active' => $dashboardActive];

            if ($role === 'admin') {
                $menuItems[] = ['label' => 'Kelola Pengguna', 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')];
                $menuItems[] = ['label' => 'Kelola Nasabah', 'url' => route('admin.customers.index'), 'active' => request()->routeIs('admin.customers.*', 'admin.accounts.*')];
                $menuItems[] = ['label' => 'Konfigurasi', 'url' => route('admin.config.edit'), 'active' => request()->routeIs('admin.config.*')];
            } elseif ($role === 'teller') {
                $menuItems[] = [
                    'label' => 'Pencarian Nasabah',
                    'url' => route('teller.customers.search'),
                    'active' => request()->routeIs('teller.customers.*', 'teller.deposits.*', 'teller.withdrawals.*', 'teller.transactions.*'),
                ];
                $menuItems[] = [
                    'label' => 'Penutupan Kas',
                    'url' => route('teller.closing.index'),
                    'active' => request()->routeIs('teller.closing.*'),
                ];
            } elseif ($role === 'supervisor') {
                $menuItems[] = [
                    'label' => 'Laporan Harian',
                    'url' => route('supervisor.reports.index'),
                    'active' => request()->routeIs('supervisor.reports.*'),
                ];
            }
        }
    @endphp

    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">Bank Mini</span>
                    <span class="text-xs text-gray-400 hidden sm:inline">·</span>
                    <span class="text-xs text-gray-500 hidden sm:inline">@yield('section', '')</span>
                </div>
                <div class="flex items-center gap-4">
                    @if ($currentUser)
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 leading-tight">{{ $currentUser->full_name }}</p>
                            <p class="text-xs text-gray-500 leading-tight">{{ ucfirst($role) }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">
                                Keluar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($currentUser && count($menuItems))
            <div class="border-t border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-1 overflow-x-auto">
                    @foreach ($menuItems as $item)
                        <a href="{{ $item['url'] }}"
                            class="py-2.5 px-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $item['active'] ? 'text-blue-600 border-blue-600' : 'text-gray-600 border-transparent hover:text-blue-600' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
