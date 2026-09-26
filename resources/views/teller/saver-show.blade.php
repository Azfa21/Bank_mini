@extends('layouts.app')

@section('title', 'Profil Penabung — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">VERIFIKASI IDENTITAS</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Profil penabung</h1>
            <p class="text-sm text-stone-600 mt-1">Cocokkan nama, rekening, dan saldo sebelum pilih transaksi.</p>
        </div>
        <a href="{{ route('teller.members.search') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-brand-900 hover:underline sm:shrink-0">&larr; Pencarian baru</a>
    </div>

    <section aria-label="Ringkasan rekening" class="bg-white rounded-2xl border border-stone-200 ledger-rule overflow-hidden">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-stone-100">
            <div class="p-5 sm:p-6">
                <dt class="text-[13px] font-medium text-stone-600">Nama penabung</dt>
                <dd class="text-lg font-bold text-stone-900 mt-1">{{ $account->saver->full_name }}</dd>
                <dd class="text-[13px] text-stone-600 mt-2">No. siswa: <span class="tnum font-medium text-stone-800">{{ $account->saver->student_number }}</span></dd>
                <dd class="text-[13px] text-stone-600 mt-1">Kelas: {{ $account->saver->class_name ?? 'Belum diisi' }}</dd>
            </div>
            <div class="p-5 sm:p-6">
                <dt class="text-[13px] font-medium text-stone-600">Nomor rekening</dt>
                <dd class="text-lg tnum font-bold text-stone-900 mt-1">{{ $account->account_number }}</dd>
                <dd class="text-[13px] text-stone-600 mt-2">Tahun ajaran: {{ $account->academic_year ?? 'Belum diisi' }}</dd>
                <dd class="text-[13px] text-stone-600 mt-1.5">
                    Status:
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[13px] font-semibold border
                        {{ $account->status === 'active' ? 'bg-brand-50 border-brand-200 text-brand-900' : 'bg-red-50 border-red-200 text-red-800' }}">
                        <span aria-hidden="true">{{ $account->status === 'active' ? '✓' : '!' }}</span>
                        {{ $account->status === 'active' ? 'Aktif, bisa transaksi' : 'Tidak aktif' }}
                    </span>
                </dd>
            </div>
            <div class="p-5 sm:p-6 bg-stone-50/60">
                <dt class="text-[13px] font-medium text-stone-600">Saldo berjalan</dt>
                <dd class="text-[28px] leading-tight font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</dd>
                <dd class="text-[13px] text-stone-600 mt-1">Saldo terpotong otomatis setelah transaksi tersimpan.</dd>
            </div>
        </dl>

        <div class="border-t border-stone-100 p-5 sm:p-6">
            @if ($account->status === 'active')
                <h2 class="text-[13px] font-semibold text-stone-700 mb-3">Pilih transaksi untuk rekening ini</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                    <a href="{{ route('teller.deposits.create', $account) }}"
                        class="inline-flex items-center justify-center gap-2 min-h-[52px] px-4 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">
                        Setoran tunai
                    </a>
                    <a href="{{ route('teller.withdrawals.create', $account) }}"
                        class="inline-flex items-center justify-center gap-2 min-h-[52px] px-4 text-[15px] font-semibold text-red-800 bg-white border-2 border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                        Penarikan tunai
                    </a>
                    <a href="{{ route('teller.transfers.create', $account) }}"
                        class="inline-flex items-center justify-center min-h-[52px] px-4 text-[15px] font-semibold text-stone-800 bg-white border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">
                        Transfer
                    </a>
                    <a href="{{ route('teller.passbook.show', $account) }}"
                        class="inline-flex items-center justify-center min-h-[52px] px-4 text-[15px] font-semibold text-stone-700 rounded-xl hover:bg-stone-100 transition-colors">
                        Buku tabungan
                    </a>
                </div>
            @else
                <p role="alert" class="text-sm font-medium text-red-800 bg-red-50 border border-red-200 rounded-xl p-4">Rekening tidak aktif. Transaksi tidak dapat diproses. Hubungi Administrator untuk aktivasi.</p>
            @endif
        </div>
    </section>
@endsection
