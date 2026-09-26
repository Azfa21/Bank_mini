@extends('layouts.app')

@section('title', 'Detail Rekening  EBank Mini')
@section('section', 'Administrator · Rekening')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Detail Rekening</h1>
        <p class="text-sm text-stone-500 mb-6">Rekening <strong>{{ $account->account_number }}</strong>.</p>

        <div class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Nomor Rekening</div>
                    <div class="text-sm font-mono font-semibold">{{ $account->account_number }}</div>
                </div>
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Tahun Ajaran</div>
                    <div class="text-sm font-semibold">{{ $account->academic_year ?? ' E }}</div>
                </div>
            </div>

            <hr class="border-stone-100">

            <div>
                <div class="text-xs text-stone-500 mb-0.5">Penabung</div>
                <div class="text-sm font-semibold">{{ $account->saver->full_name }}</div>
                <div class="text-xs text-stone-500">No. Siswa: {{ $account->saver->student_number }}</div>
            </div>

            <hr class="border-stone-100">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Saldo Awal</div>
                    <div class="text-sm font-mono">Rp {{ number_format($account->opening_balance, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Saldo Berjalan</div>
                    <div class="text-lg font-mono font-semibold text-olive-700">Rp {{ number_format($account->balance, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Saldo Minimum</div>
                    <div class="text-sm font-mono">Rp {{ number_format($account->minimum_balance, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-stone-500 mb-0.5">Status</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $account->status === 'active' ? 'bg-olive-100 text-olive-700' : 'bg-red-100 text-red-700' }}">
                        {{ $account->status === 'active' ? 'Aktif' : 'Ditutup' }}
                    </span>
                </div>
            </div>

            <hr class="border-stone-100">

            <div class="grid grid-cols-2 gap-4 text-xs text-stone-500">
                <div>Dibuka: {{ $account->opened_at?->format('d/m/Y H:i') ?? ' E }}</div>
                <div>Ditutup: {{ $account->closed_at?->format('d/m/Y H:i') ?? ' E }}</div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.savers.index') }}" class="text-sm text-stone-600 border border-stone-300 px-4 py-2 rounded-lg hover:bg-stone-50">&larr; Kembali ke Daftar Penabung</a>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-sm font-medium text-stone-900 mb-3">Kartu Penabung Virtual</h2>
        @include('partials.virtual-card', ['account' => $account, 'customer' => $account->saver, 'cardPrintUrl' => route('admin.accounts.card-print', $account)])
    </div>
@endsection
