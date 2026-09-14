@extends('layouts.app')

@section('title', 'Cetak Buku Tabungan — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Format Cetak Buku Tabungan</h1>
            <p class="text-sm text-gray-500 mt-1">Posisikan kertas buku tabungan di printer, lalu cetak.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Cetak Sekarang
            </button>
            <a href="{{ route('teller.passbook.show', $account) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                Kembali
            </a>
        </div>
    </div>

    <div class="print-sheet max-w-3xl mx-auto bg-white rounded-xl border-2 border-gray-800 p-6 text-gray-900" id="passbook">
        <div class="text-center border-b-2 border-gray-800 pb-3">
            <p class="font-bold tracking-wide text-base">BANK MINI SEKOLAH</p>
            <p class="text-xs mt-0.5">Buku Tabungan Siswa — Tahun Ajaran {{ $account->academic_year }}</p>
        </div>

        <div class="flex items-center justify-between text-xs py-3 border-b border-gray-400">
            <div class="space-y-0.5">
                <p>Pemegang : <span class="font-semibold">{{ $account->customer->full_name }}</span></p>
                <p>No. Siswa : <span class="font-mono">{{ $account->customer->student_number }}</span></p>
            </div>
            <div class="space-y-0.5 text-right">
                <p>No. Rekening : <span class="font-mono font-semibold">{{ $account->account_number }}</span></p>
                <p>Saldo : <span class="font-mono font-semibold">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</span></p>
            </div>
        </div>

        <table class="w-full text-xs mt-2">
            <thead>
                <tr class="border-b-2 border-gray-800">
                    <th class="py-1.5 text-left w-8">No</th>
                    <th class="py-1.5 text-left">Tanggal</th>
                    <th class="py-1.5 text-left">Transaksi / Referensi</th>
                    <th class="py-1.5 text-right">Debet</th>
                    <th class="py-1.5 text-right">Kredit</th>
                    <th class="py-1.5 text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @for ($line = $startLine; $line <= 20; $line++)
                    @php
                        $row = $transactions[$line - $startLine] ?? null;
                    @endphp
                    <tr class="border-b border-gray-300 h-8 align-top">
                        @if ($row)
                            <td class="py-1 pr-1 text-gray-400">{{ $line }}</td>
                            <td class="py-1 pr-1">{{ $row->transaction_at?->format('d/m/Y') }}</td>
                            <td class="py-1 pr-1">
                                {{ $row->typeLabel() }}
                                <span class="text-gray-500">{{ $row->receipt_number }}</span>
                                @if ($row->type === 'transfer' && $row->recipient_account_id === $account->id)
                                    <span class="text-gray-500">(masuk)</span>
                                @elseif ($row->type === 'transfer')
                                    <span class="text-gray-500">(keluar)</span>
                                @endif
                            </td>
                            <td class="py-1 text-right font-mono">
                                @if ($row->isCreditFor($account->id))
                                    {{ number_format($row->amount, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-1 text-right font-mono">
                                @if (! $row->isCreditFor($account->id))
                                    {{ number_format($row->amount, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-1 text-right font-mono">{{ number_format($row->balance_after, 0, ',', '.') }}</td>
                        @else
                            <td class="py-1 pr-1 text-gray-300">{{ $line }}</td>
                            <td colspan="5" class="py-1"></td>
                        @endif
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="flex justify-between text-xs mt-4 pt-3 border-t border-gray-400">
            <p class="text-gray-600">Baris cetak: {{ $startLine }}–{{ $startLine + count($transactions) - 1 }}</p>
            <p class="text-gray-600">Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->full_name }}</p>
        </div>
    </div>
@endsection
