@extends('layouts.app')

@section('title', 'Dashboard Nasabah — Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">TABUNGAN SAYA</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Halo, {{ $nasabah->full_name }}</h1>
        <p class="text-sm text-stone-600 mt-1">Pantau saldo dan mutasi rekening sekolah Anda.</p>
    </div>

    @if ($account)
        <section aria-label="Ringkasan rekening" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-end gap-2 sm:justify-between">
                <div>
                    <p class="text-[13px] font-medium text-stone-600">Saldo berjalan</p>
                    <p class="text-[32px] leading-tight font-bold text-stone-900 tnum">Rp {{ number_format($account->balance, 0, ',', '.') }}</p>
                    <p class="text-[13px] text-stone-600 mt-1">No. rekening <span class="tnum font-medium text-stone-800">{{ $account->account_number }}</span></p>
                </div>
                <p>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[13px] font-semibold border {{ $account->status === 'active' ? 'bg-brand-50 border-brand-200 text-brand-900' : 'bg-red-50 border-red-200 text-red-800' }}">
                        <span aria-hidden="true">{{ $account->status === 'active' ? '●' : '■' }}</span>
                        {{ $account->status === 'active' ? 'Rekening aktif' : 'Rekening tidak aktif' }}
                    </span>
                </p>
            </div>

            <div class="mt-4 border-t border-stone-100 pt-4">
                <h2 class="text-[13px] font-semibold text-stone-700">Transaksi terakhir</h2>
                @if ($lastTransaction)
                    <p class="text-[15px] font-semibold text-stone-900 mt-1">
                        @if ($lastTransaction->type === 'transfer')
                            {{ $lastTransaction->recipient_account_id === $account->id ? 'Transfer masuk' : 'Transfer keluar' }}
                        @else
                            {{ $lastTransaction->typeLabel() }}
                        @endif
                        <span class="tnum">· Rp {{ number_format($lastTransaction->amount, 0, ',', '.') }}</span>
                    </p>
                    <p class="text-[13px] text-stone-600 mt-1">{{ $lastTransaction->transaction_at->format('d/m/Y H:i') }}</p>
                @else
                    <p class="text-sm text-stone-600 mt-1">Belum ada transaksi. Setoran pertama bisa dilakukan di loket sekolah.</p>
                @endif
            </div>

            <div class="mt-4 flex flex-col sm:flex-row gap-2.5">
                <a href="{{ route('nasabah.mutasi') }}"
                    class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                    Lihat riwayat mutasi
                </a>
                <a href="{{ route('nasabah.statement') }}"
                    class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">
                    Unduh rekening koran
                </a>
            </div>
        </section>

        <section aria-label="Kartu nasabah">
            <h2 class="text-[15px] font-bold text-stone-900 mb-2.5">Kartu nasabah virtual</h2>
            @include('partials.virtual-card', ['account' => $account, 'customer' => $nasabah, 'cardPrintUrl' => route('nasabah.card.print')])
        </section>
    @else
        <div role="status" class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-2xl p-5">
            <p class="font-semibold">Anda belum punya rekening aktif.</p>
            <p class="mt-1">Datang ke loket Bank Mini sekolah dengan kartu pelajar untuk pembukaan rekening.</p>
        </div>
    @endif
@endsection
