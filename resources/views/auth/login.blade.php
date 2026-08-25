<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Bank Mini Sekolah</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V3h18v18H3Zm4-4h10M7 11h10"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-gray-900">Bank Mini Sekolah</h1>
            <p class="text-sm text-gray-500 mt-1">Masuk ke akun Anda</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
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

            <x-form-errors />

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="identifier">Username / No. Siswa</x-input-label>
                    <x-input id="identifier" name="identifier" required autofocus
                        placeholder="Masukkan username atau nomor siswa" />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <x-input-label for="secret" class="mb-0">Password / PIN</x-input-label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline">
                            Lupa Password / PIN?
                        </a>
                    </div>
                    <x-input id="secret" name="secret" type="password" required
                        placeholder="Masukkan password atau PIN 6 digit" />
                </div>

                <x-primary-button class="w-full py-2.5 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Masuk
                </x-primary-button>

                <div class="text-center pt-2">
                    <a href="{{ route('password.request') }}" class="text-xs text-gray-500 hover:text-gray-700">
                        Kesulitan masuk akun? <span class="text-blue-600 font-medium hover:underline">Pulihkan Akun</span>
                    </a>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">&copy; {{ date('Y') }} Bank Mini Sekolah</p>
    </div>
</body>
</html>
