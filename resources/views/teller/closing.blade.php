@extends('layouts.app')

@section('title', 'Penutupan Kas — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">TUTUP LOKET</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Penutupan kas</h1>
        <p class="text-sm text-stone-600 mt-1">Hitung uang laci, ketik totalnya, lalu serahkan ke Supervisor.</p>
    </div>

    <form method="GET" action="{{ route('teller.closing.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="flex-1 max-w-xs">
            <x-input-label for="report_date">Tanggal kerja</x-input-label>
            <x-input id="report_date" name="report_date" type="date" :value="$date" />
        </div>
        <x-primary-button>
            Tampilkan rekap
        </x-primary-button>
    </form>

    @if ($report)
        <div role="status" class="mb-4 p-4 rounded-2xl border text-sm flex items-start gap-2.5 {{ $report->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : ($report->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900') }}">
            <span aria-hidden="true" class="mt-0.5 font-bold">{{ $report->status === 'approved' ? '●' : ($report->status === 'rejected' ? '■' : '▲') }}</span>
            <p>
                @if ($report->status === 'approved')
                    Laporan <strong>{{ $report->report_number }}</strong> sudah disetujui dan kas pindah ke brankas utama.
                @elseif ($report->status === 'rejected')
                    Laporan <strong>{{ $report->report_number }}</strong> ditolak: {{ $report->approval_notes }}. Periksa ulang lalu kirim kembali.
                @else
                    Laporan <strong>{{ $report->report_number }}</strong> menunggu validasi Supervisor.
                    @if (($report->cash_difference ?? 0) !== 0)
                        <span class="block mt-1 font-semibold">Ada selisih {{ 'Rp '.number_format(abs($report->cash_difference ?? 0), 0, ',', '.') }} — cetak final terkunci sampai Supervisor memeriksa.</span>
                    @endif
                @endif
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <section aria-label="Rekap dan hitung kas" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule overflow-hidden">
                <div class="border-b border-stone-100 px-5 py-4">
                    <h2 class="text-[15px] font-bold text-stone-900">Rekap transaksi harian</h2>
                </div>
                <dl class="divide-y divide-stone-100 text-sm">
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="text-stone-600">Saldo awal (laporan disetujui sebelumnya)</dt>
                        <dd class="tnum font-medium text-stone-900">Rp {{ number_format($recap['opening_balance'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="text-stone-600">Total setoran</dt>
                        <dd class="tnum font-semibold text-brand-900">+ Rp {{ number_format($recap['total_deposit'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="text-stone-600">Total penarikan</dt>
                        <dd class="tnum font-semibold text-red-800">− Rp {{ number_format($recap['total_withdrawal'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 px-5 py-3 bg-stone-50 font-bold">
                        <dt class="text-stone-900">Saldo akhir sistem</dt>
                        <dd class="tnum text-stone-900">Rp {{ number_format($recap['closing_balance_system'], 0, ',', '.') }}</dd>
                    </div>
                </dl>

                <div class="border-t border-stone-100 px-5 py-5">
                    <h3 class="text-[15px] font-bold text-stone-900">Uang fisik di laci</h3>
                    <p class="text-[13px] text-stone-600 mt-1 mb-3">Hitung semua uang di laci, lalu ketik totalnya. Tidak perlu mengisi semua pecahan.</p>
                    <form method="POST" action="{{ route('teller.closing.store') }}" class="space-y-4" id="closing-form" data-system-balance="{{ (int) $recap['closing_balance_system'] }}">
                        @csrf
                        <input type="hidden" name="report_date" value="{{ $date }}">

                        <div>
                            <x-input-label for="physical_cash">Total uang fisik di laci (Rp) wajib</x-input-label>
                            <x-input id="physical_cash" name="physical_cash" type="number" min="0" step="1" inputmode="numeric"
                                :value="old('physical_cash', '')" placeholder="Contoh: {{ (int) $recap['closing_balance_system'] }}" required data-denomination-total
                                aria-describedby="closing-match" />
                            <x-input-error :message="$errors->first('physical_cash')" />
                            <p id="closing-match" role="status" class="text-[13px] font-semibold text-stone-600 mt-1.5">Ketik total uang laci. Rincian pecahan opsional di bawah.</p>
                        </div>

                        <div id="difference-note-wrap" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <x-input-label for="difference_note">Berita acara selisih (wajib bila ada selisih)</x-input-label>
                            <x-textarea id="difference_note" name="difference_note" rows="3"
                                placeholder="Contoh: Kurang Rp1 setelah hitung ulang 2x, kemungkinan pembulatan koin. Mohon pemeriksaan Supervisor.">{{ old('difference_note') }}</x-textarea>
                            <p class="text-[13px] text-amber-900 mt-1.5">Minimal 10 karakter. Jelaskan hasil hitung ulang agar Supervisor bisa menindaklanjuti. Uang pecahan Rp1 tidak beredar, jadi selisih kecil wajar dilaporkan, bukan dipaksakan cocok.</p>
                            <x-input-error :message="$errors->first('difference_note')" />
                        </div>

                        <details class="border border-stone-200 rounded-xl">
                            <summary class="inline-flex items-center min-h-[48px] px-4 cursor-pointer text-sm font-semibold text-stone-900 w-full">
                                Bantu hitung per pecahan (opsional)
                            </summary>
                            <div class="px-4 pb-4">
                                <p class="text-[13px] text-stone-600 mb-3">Isi yang ada saja. Tekan “Pakai total ini” untuk menyalin ke kolom total di atas.</p>
                                <x-denomination-input :selected="old('denominations', [])" />
                                <p role="status" data-denomination-sum class="text-[13px] font-medium text-stone-700 mt-3">Subtotal rincian: Rp 0</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button" data-denomination-apply class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-brand-900 border border-brand-800/30 bg-brand-50 rounded-xl hover:bg-brand-100 disabled:opacity-50">
                                        Pakai total ini
                                    </button>
                                    <button type="button" data-denomination-reset class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-medium text-stone-600 hover:text-stone-900">
                                        Ulangi
                                    </button>
                                </div>
                            </div>
                        </details>

                        <x-primary-button id="closing-submit" data-loading-text="Membuat laporan..." class="w-full sm:w-auto sm:min-w-[240px]">
                            Buat laporan harian
                        </x-primary-button>
                    </form>

                    @if (session('error'))
                        <div role="alert" class="mt-4 p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm">
                            <p class="font-semibold">Laporan belum tersimpan.</p>
                            <p class="mt-1">{{ session('error') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <aside aria-label="Daftar transaksi tanggal ini" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <h2 class="text-[15px] font-bold text-stone-900">Transaksi {{ date('d/m/Y', strtotime($date)) }}</h2>
                @if ($transactions->isEmpty())
                    <p class="text-sm text-stone-600 mt-2">Belum ada transaksi pada tanggal ini. Rekap bernilai nol, tetap bisa dibuat bila perlu.</p>
                @else
                    <ul class="mt-3 space-y-3">
                        @foreach ($transactions as $transaction)
                            <li class="flex items-center justify-between gap-3 text-sm border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="tnum text-xs text-stone-500">{{ $transaction->receipt_number }}</p>
                                    <p class="font-medium text-stone-900 truncate">{{ $transaction->typeLabel() }}</p>
                                </div>
                                <span class="tnum font-semibold shrink-0 {{ $transaction->isCreditFor($transaction->account_id) ? 'text-brand-900' : 'text-red-800' }}">
                                    {{ $transaction->isCreditFor($transaction->account_id) ? '+' : '−' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </aside>
    </div>
@endsection
