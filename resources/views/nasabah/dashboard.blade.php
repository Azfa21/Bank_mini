@extends('layouts.app')

@section('title', 'Dashboard Nasabah — Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Selamat Datang, {{ $nasabah->full_name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Lihat saldo dan riwayat mutasi rekening Anda.</p>
    </div>

    @if ($account)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="text-sm text-gray-500">Saldo Rekening</div>
                <div class="text-2xl font-semibold text-gray-900 mt-1">Rp {{ number_format($account->balance, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="text-sm text-gray-500">Nomor Rekening</div>
                <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $account->account_number }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="text-sm text-gray-500">Status Rekening</div>
                <div class="mt-1">
                    <span class="text-xs px-2 py-1 rounded-full {{ $account->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($account->status) }}
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="text-sm text-gray-500">Transaksi Terakhir</div>
                @if ($lastTransaction)
                    <div class="text-2xl font-semibold text-gray-900 mt-1">
                        @if ($lastTransaction->type === 'transfer')
                            {{ $lastTransaction->recipient_account_id === $account->id ? 'Transfer Masuk' : 'Transfer Keluar' }}
                        @else
                            {{ $lastTransaction->typeLabel() }}
                        @endif
                    </div>
                    <div class="text-lg font-medium text-gray-700 mt-1">Rp {{ number_format($lastTransaction->amount, 0, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $lastTransaction->transaction_at->format('d/m/Y H:i') }}</div>
                @else
                    <div class="text-gray-500 mt-1">Belum ada transaksi.</div>
                @endif
            </div>

            <div class="lg:col-span-2">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Kartu Nasabah Virtual</h2>
                @include('partials.virtual-card', ['account' => $account, 'customer' => $nasabah, 'cardPrintUrl' => route('nasabah.card.print')])
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('nasabah.mutasi') }}"
                class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Riwayat Mutasi
            </a>
            <a href="{{ route('nasabah.statement') }}"
                class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                Rekening Koran (e-Statement)
            </a>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded-xl p-5">
            Anda belum memiliki rekening aktif. Silakan hubungi administrator Bank Mini.
        </div>
    @endif
@endsection
