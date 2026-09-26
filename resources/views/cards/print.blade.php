@extends('layouts.app')

@section('title', 'Cetak Kartu Nasabah  EBank Mini')
@section('section', $sectionLabel)

@php
    $customer = $account->saver;
    $qrValue = $customer->qr_code_value ?? ('BMS:'.$account->account_number);
    $bankName = \Illuminate\Support\Str::upper(\App\Models\SystemConfig::value('bank_name', 'Bank Mini Sekolah'));
@endphp

@section('content')
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Cetak Kartu Nasabah</h1>
            <p class="text-sm text-stone-500 mt-1">Cetak kartu ini, lalu potong dan berikan kepada nasabah.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" data-print class="bg-brand-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-900 transition-colors">
                Cetak Sekarang
            </button>
            <a href="{{ $backUrl }}" class="text-sm border border-stone-300 rounded-lg px-4 py-2 hover:bg-stone-50">
                Kembali
            </a>
        </div>
    </div>

    <div class="print-sheet max-w-md mx-auto bg-white rounded-xl border-2 border-stone-800 p-6" id="customer-card">
        <div class="text-center border-b-2 border-stone-800 pb-3">
            <p class="font-bold tracking-wide text-base">{{ $bankName }}</p>
            <p class="text-xs mt-0.5">Kartu Nasabah{{ $account->academic_year ? '  ET.A. '.$account->academic_year : '' }}</p>
        </div>

        <div class="flex items-start justify-between gap-6 mt-5">
            <div class="space-y-3 text-sm min-w-0">
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-stone-500">Nama</p>
                    <p class="font-semibold">{{ $customer->full_name }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-stone-500">No. Siswa / Kelas</p>
                    <p class="font-mono">{{ $customer->student_number }} / {{ $customer->class_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-stone-500">No. Rekening</p>
                    <p class="font-mono font-semibold">{{ $account->account_number }}</p>
                </div>
            </div>
            <img src="{{ route('qr.show', ['data' => $qrValue, 'size' => 300]) }}"
                alt="QR Code {{ $customer->full_name }}"
                class="w-32 h-32 shrink-0 border border-stone-300 p-1">
        </div>

        <p class="mt-5 border-t border-stone-300 pt-2 text-[10px] text-stone-500 text-center">
            Tunjukkan atau pindai QR Code ini kepada teller untuk melakukan transaksi.
        </p>
    </div>
@endsection
