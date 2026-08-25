@extends('layouts.app')

@section('title', 'Rekening Koran — Bank Mini')
@section('section', 'Nasabah')

@section('content')
    <div class="mb-6 flex items-center justify-between print:hidden">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Rekening Koran (e-Statement)</h1>
            <p class="text-sm text-gray-500 mt-1">Laporan mutasi rekening resmi untuk periode tertentu.</p>
        </div>
        <button onclick="printStatement()" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Cetak / Simpan PDF
        </button>
    </div>

@push('scripts')
<script>
    function printStatement() {
        // Ensure all styles are loaded before printing
        setTimeout(() => {
            window.print();
        }, 200);
    }
    
    // Handle potential print dialog issues
    window.addEventListener('afterprint', () => {
        // Cleanup or feedback if needed
    });
</script>
@endpush

    <form method="GET" action="{{ route('nasabah.statement') }}" class="mb-6 bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap gap-3 items-end print:hidden">
        <div>
            <x-input-label for="date_from" size="xs">Dari Tanggal</x-input-label>
            <x-input id="date_from" name="date_from" type="date" :value="$dateFrom" :block="false" />
        </div>
        <div>
            <x-input-label for="date_to" size="xs">Sampai Tanggal</x-input-label>
            <x-input id="date_to" name="date_to" type="date" :value="$dateTo" :block="false" />
        </div>
        <x-primary-button>
            Tampilkan
        </x-primary-button>
    </form>

    <div class="relative max-w-3xl mx-auto bg-white p-6 print:border-none print:shadow-none" id="statement">
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden opacity-[0.06]">
            <span class="text-5xl font-bold text-gray-900 whitespace-nowrap select-none">BANK MINI SEKOLAH</span>
        </div>

        <div class="relative">
            <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4">
                <div>
                    <p class="font-bold text-base">BANK MINI SEKOLAH</p>
                    <p class="text-xs mt-0.5">Rekening Koran Nasabah (e-Statement)</p>
                </div>
                <div class="text-right text-xs space-y-0.5">
                    <p>Nomor Rekening: <span class="font-mono font-semibold">{{ $account->account_number }}</span></p>
                    <p>Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
                    <p>Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="py-4 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <p class="text-gray-500">Pemegang Rekening</p>
                    <p class="font-semibold text-gray-900">{{ $account->customer->full_name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Kelas / No. Siswa</p>
                    <p class="font-semibold text-gray-900">{{ $account->customer->class_name }} / {{ $account->customer->student_number }}</p>
                </div>
            </div>

            <table class="w-full text-xs print:table-fixed print:w-full">
                <thead>
                         <tr class="border-b-2 border-gray-800 print:border-black print:border-2">
                         <th class="py-2 text-left print:w-20">Tanggal</th>
                         <th class="py-2 text-left print:w-40">Keterangan</th>
                         <th class="py-2 text-right print:w-24">Debet</th>
                         <th class="py-2 text-right print:w-24">Kredit</th>
                         <th class="py-2 text-right print:w-28">Saldo</th>
                     </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($transactions as $transaction)
                         <tr class="print:page-break-inside-avoid">
                             <td class="py-2 print:border print:border-gray-300">{{ $transaction->transaction_at->format('d/m/Y') }}</td>
                             <td class="py-2 print:border print:border-gray-300">
                                 @if ($transaction->type === 'deposit')
                                     Setoran tunai
                                 @elseif ($transaction->type === 'withdrawal')
                                     Penarikan tunai
                                 @elseif ($transaction->recipient_account_id === $account->id)
                                     Transfer masuk {{ $transaction->notes ? '('.$transaction->notes.')' : '' }}
                                 @else
                                     Transfer keluar {{ $transaction->notes ? '('.$transaction->notes.')' : '' }}
                                 @endif
                                 <span class="block text-gray-400 font-mono">{{ $transaction->receipt_number }}</span>
                             </td>
                             <td class="py-2 text-right font-mono print:border print:border-gray-300">
                                 @if ($transaction->type === 'deposit' || ($transaction->type === 'transfer' && $transaction->recipient_account_id === $account->id))
                                     {{ number_format($transaction->amount, 0, ',', '.') }}
                                 @else
                                     &nbsp;
                                 @endif
                             </td>
                             <td class="py-2 text-right font-mono print:border print:border-gray-300">
                                 @if ($transaction->type === 'withdrawal' || ($transaction->type === 'transfer' && $transaction->account_id === $account->id))
                                     {{ number_format($transaction->amount, 0, ',', '.') }}
                                 @else
                                     &nbsp;
                                 @endif
                             </td>
                             <td class="py-2 text-right font-mono print:border print:border-gray-300">{{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                         </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500">Tidak ada mutasi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                     <tr class="border-t-2 border-gray-800 font-semibold print:border-black print:border-2">
                         <td colspan="2" class="py-2 print:border print:border-gray-300">Total</td>
                         <td class="py-2 text-right font-mono print:border print:border-gray-300">{{ number_format($totalDeposit + $totalTransferIn, 0, ',', '.') }}</td>
                         <td class="py-2 text-right font-mono print:border print:border-gray-300">{{ number_format($totalWithdrawal + $totalTransferOut, 0, ',', '.') }}</td>
                         <td class="py-2 text-right font-mono print:border print:border-gray-300">Rp {{ number_format($account->current_balance, 0, ',', '.') }}</td>
                     </tr>
                </tfoot>
            </table>

            <div class="flex items-end justify-between mt-6 pt-4 border-t border-gray-300">
                <div class="text-center">
                    <img src="{{ route('qr.show', ['data' => 'BMS:'.urlencode($verificationCode), 'size' => 120]) }}"
                        alt="Kode verifikasi rekening koran" class="mx-auto w-24 h-24">
                    <p class="text-[10px] text-gray-500 mt-1">Kode Verifikasi: {{ $verificationCode }}</p>
                </div>
                <div class="text-xs text-gray-500 space-y-1">
                    <p>Total Setoran: Rp {{ number_format($totalDeposit, 0, ',', '.') }}</p>
                    <p>Total Penarikan: Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</p>
                    <p>Total Transfer Keluar: Rp {{ number_format($totalTransferOut, 0, ',', '.') }}</p>
                    <p>Total Transfer Masuk: Rp {{ number_format($totalTransferIn, 0, ',', '.') }}</p>
                </div>
            </div>

            <p class="text-center text-[10px] text-gray-400 mt-4">Dokumen ini dihasilkan oleh sistem Bank Mini Sekolah dan dapat diverifikasi melalui kode verifikasi di atas.</p>
        </div>
    </div>
@endsection
