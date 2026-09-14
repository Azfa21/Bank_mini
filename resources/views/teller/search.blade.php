@extends('layouts.app')

@section('title', 'Pencarian Nasabah — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pencarian Nasabah</h1>
            <p class="text-sm text-gray-500 mt-1">Cari melalui nomor rekening, NIS, nama lengkap, atau pindai QR Code nasabah.</p>
        </div>
        <a href="{{ route('teller.customers.register') }}" class="text-sm bg-gray-900 text-white rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">
            Registrasi Nasabah
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Kata Kunci Pencarian</h2>

            <form method="GET" action="{{ route('teller.customers.lookup') }}" class="space-y-4">
                <div>
                    <x-input-label for="q" size="xs">Nomor rekening, NIS, nama lengkap, atau nilai QR Code</x-input-label>
                    <x-input id="q" name="q" :value="request('q')" placeholder="Contoh: Ani Wijaya / 1026270001" autofocus />
                </div>
                <x-primary-button class="w-full">
                    Cari Nasabah
                </x-primary-button>
            </form>

            <div class="mt-6 border-t border-gray-100 pt-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Pindai QR Code</h3>

                <div id="qr-scan-area" class="hidden">
                    <video id="qr-video" class="w-full rounded-lg border border-gray-200 bg-gray-900" playsinline muted></video>
                    <div class="mt-3 flex gap-2">
                        <button type="button" id="qr-stop" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50">Hentikan Kamera</button>
                        <span id="qr-status" class="text-xs text-gray-500 self-center"></span>
                    </div>
                </div>

                <button type="button" id="qr-start" class="text-sm bg-gray-900 text-white rounded-lg px-4 py-2 hover:bg-gray-800 transition-colors">
                    Nyalakan Kamera
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-medium text-gray-900 mb-3">Cara Penggunaan</h2>
            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                <li>Ketik nomor rekening, NIS, atau nama lengkap lalu tekan <strong>Cari Nasabah</strong>.</li>
                <li>Jika hasil lebih dari satu, sistem menampilkan daftar untuk dipilih.</li>
                <li>Atau nyalakan kamera dan arahkan ke QR Code pada kartu/buku tabungan nasabah.</li>
            </ol>
            <p class="text-xs text-gray-400 mt-4">Jika kamera tidak tersedia, gunakan pencarian manual.</p>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/qr-scanner.js'])
@endpush
