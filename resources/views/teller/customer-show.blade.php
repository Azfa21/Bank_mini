@extends('layouts.app')

@section('title', 'Profil Nasabah — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Profil Nasabah</h1>
            <p class="text-sm text-gray-500 mt-1">Konfirmasi identitas sebelum melakukan transaksi.</p>
        </div>
        <a href="{{ route('teller.customers.search') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Pencarian baru</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-y md:divide-y-0 md:divide-x divide-gray-100">
            <div class="p-6">
                <p class="text-xs text-gray-500">Nama Nasabah</p>
                <p class="text-lg font-semibold text-gray-900 mt-1">{{ $account->customer->full_name }}</p>
                <p class="text-xs text-gray-500 mt-2">No. Siswa: <span class="font-mono">{{ $account->customer->student_number }}</span></p>
                <p class="text-xs text-gray-500 mt-1">Kelas: {{ $account->customer->class_name ?? '—' }}</p>
            </div>
            <div class="p-6">
                <p class="text-xs text-gray-500">Nomor Rekening</p>
                <p class="text-lg font-mono font-semibold text-gray-900 mt-1">{{ $account->account_number }}</p>
                <p class="text-xs text-gray-500 mt-2">Tahun Ajaran: {{ $account->academic_year ?? '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    Status:
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $account->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $account->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </p>
            </div>
            <div class="p-6 flex flex-col justify-center">
                <p class="text-xs text-gray-500">Saldo Berjalan</p>
                <p class="text-2xl font-semibold text-blue-600 mt-1">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="border-t border-gray-100 p-6">
            @if ($account->status === 'active')
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('teller.deposits.create', $account) }}"
                        class="inline-flex items-center gap-2 bg-green-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>
                        Transaksi Setoran
                    </a>
                    <a href="{{ route('teller.withdrawals.create', $account) }}"
                        class="inline-flex items-center gap-2 bg-red-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                        </svg>
                        Transaksi Penarikan
                    </a>
                    <a href="{{ route('teller.transfers.create', $account) }}"
                        class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H8M17 7v9"/>
                        </svg>
                        Transfer Antar Rekening
                    </a>
                    <a href="{{ route('teller.passbook.show', $account) }}"
                        class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4zM4 9h16"/>
                        </svg>
                        Buku Tabungan
                    </a>
                </div>
            @else
                <p class="text-sm text-red-600">Rekening tidak aktif — transaksi tidak dapat diproses.</p>
            @endif
        </div>
    </div>
@endsection
