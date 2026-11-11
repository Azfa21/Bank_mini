@extends('layouts.app')

@section('title', 'Pencarian Penabung | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">LANGKAH 1 DARI TRANSAKSI</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Temukan penabung</h1>
            <p class="text-sm text-stone-600 mt-1">Pakai nomor rekening, NIS, nama lengkap, atau pindai QR Code di kartu.</p>
        </div>
        <a href="{{ route('teller.members.register') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-stone-900 rounded-xl hover:bg-stone-800 transition-colors sm:shrink-0">
            Daftarkan anggota baru
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-5">
        <section aria-label="Formulir pencarian penabung" class="lg:col-span-3 bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
            <h2 class="text-base font-bold text-stone-900">Cari manual</h2>

            <form method="GET" action="{{ route('teller.members.lookup') }}" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="q">Nomor rekening, NIS, atau nama</x-input-label>
                    <x-input id="q" name="q" :value="request('q')" placeholder="Contoh: Ani Wijaya atau 1026270001" autofocus autocomplete="off"
                        aria-describedby="q-help" />
                    <p id="q-help" class="text-[13px] text-stone-600 mt-1.5">Kalau hasil lebih dari satu, pilih nama yang tepat di daftar.</p>
                </div>
                <x-primary-button class="w-full">
                    Cari penabung
                </x-primary-button>
            </form>

            @include('teller.partials.qr-scanner')
        </section>

        <aside aria-label="Alur pencarian" class="lg:col-span-2 bg-brand-50 rounded-2xl border border-brand-100 p-5 sm:p-6">
            <h2 class="text-[15px] font-bold text-brand-950">Alur di loket</h2>
            <ol class="mt-3 space-y-3 text-sm text-brand-950">
                <li class="flex gap-3">
                    <span class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-brand-800 text-white text-[13px] font-bold" aria-hidden="true">1</span>
                    <span>Ketik identitas lalu tekan <strong>Cari penabung</strong>.</span>
                </li>
                <li class="flex gap-3">
                    <span class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-brand-800 text-white text-[13px] font-bold" aria-hidden="true">2</span>
                    <span>Cocokkan foto, nama, dan nomor rekening dengan buku tabungan.</span>
                </li>
                <li class="flex gap-3">
                    <span class="inline-flex items-center justify-center w-7 h-7 shrink-0 rounded-full bg-brand-800 text-white text-[13px] font-bold" aria-hidden="true">3</span>
                    <span>Buka profil, lalu pilih setoran, penarikan, atau transfer.</span>
                </li>
            </ol>
        </aside>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/qr-scanner.js'])
@endpush
