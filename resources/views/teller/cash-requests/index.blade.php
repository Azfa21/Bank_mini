@extends('layouts.app')

@section('title', 'Permintaan Kas | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">MODAL KAS</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Permintaan kas</h1>
        <p class="text-sm text-stone-600 mt-1">Ajukan modal awal untuk menyiapkan uang tunai penarikan. Bila uang laci menipis, ajukan lagi.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <section aria-label="Formulir permintaan kas" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                @if ($closedReport)
                    <h2 class="text-lg font-bold text-stone-900">Penutupan kas sudah dibuat</h2>
                    <div role="status" class="mt-3 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm">
                        <p>
                            Laporan <strong>{{ $closedReport->report_number }}</strong> berstatus {{ strtolower($closedReport->statusLabel()) }}.
                            Kas laci sudah dihitung untuk laporan hari ini, jadi permintaan kas baru dapat diajukan pada hari berikutnya.
                        </p>
                    </div>
                    <p class="text-[13px] text-stone-600 mt-3">
                        <a href="{{ route('teller.closing.index') }}" class="font-semibold text-brand-900 hover:underline">Lihat penutupan kas</a>
                        untuk memeriksa laporan atau menunggu keputusan Administrator.
                    </p>
                @else
                    <h2 class="text-lg font-bold text-stone-900">Ajukan permintaan</h2>
                    <p class="text-sm text-stone-600 mt-1">Setiap permintaan menunggu keputusan Administrator. Setelah disetujui, kas dianggap terkirim ke laci dan pembukuan jurnalnya tercatat otomatis.</p>

                    <form method="POST" action="{{ route('teller.cash-requests.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="amount">Nominal yang diminta (Rp)</x-input-label>
                            <x-input id="amount" name="amount" type="number" min="1" max="10000000" step="1" inputmode="numeric" required placeholder="Contoh: 500000" />
                            <x-input-error :message="$errors->first('amount')" />
                        </div>
                        <x-primary-button data-loading-text="Mengirim...">
                            Kirim permintaan
                        </x-primary-button>
                    </form>
                @endif
            </div>
        </section>

        <aside aria-label="Kas di laci hari ini" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <h2 class="text-[15px] font-bold text-stone-900">Kas laci hari ini</h2>
                <dl class="divide-y divide-stone-100 text-sm mt-3">
                    <div class="flex justify-between gap-3 pb-3">
                        <dt class="text-stone-600">Saldo laci saat ini</dt>
                        <dd class="tnum font-bold text-stone-900">Rp {{ number_format($recap['closing_balance_system'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-3">
                        <dt class="text-stone-600">Kas dari kas utama (hari ini)</dt>
                        <dd class="tnum text-brand-900">+ Rp {{ number_format($recap['total_cash_received'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-3">
                        <dt class="text-stone-600">Setoran tunai</dt>
                        <dd class="tnum text-brand-900">+ Rp {{ number_format($recap['total_deposit'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-3">
                        <dt class="text-stone-600">Penarikan tunai</dt>
                        <dd class="tnum text-red-800">− Rp {{ number_format($recap['total_withdrawal'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-stone-600 mt-3 pt-3 border-t border-stone-100">
                    Saldo mengikuti arus kas tercatat. Nominal yang masih menunggu keputusan belum dihitung.
                </p>
                <p class="text-[13px] mt-3">
                    <a href="{{ route('teller.closing.index') }}" class="font-semibold text-brand-900 hover:underline">Buka penutupan kas</a>
                    untuk laporan harian.
                </p>
            </div>
        </aside>
    </div>

    <section aria-label="Riwayat permintaan kas" class="mt-4 sm:mt-5">
        @if ($pendingCount > 0)
            <p class="mb-3 text-sm text-stone-600">{{ $pendingCount }} permintaan masih menunggu keputusan Administrator.</p>
        @endif

        <x-data-table caption="Riwayat permintaan kas" :headers="[['label' => 'Nomor'], ['label' => 'Diajukan'], ['label' => 'Nominal', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Catatan admin']]">
                @forelse ($cashRequests as $cashRequest)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 tnum font-medium">{{ $cashRequest->request_number }}</td>
                        <td class="px-5 py-3 tnum">{{ $cashRequest->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 text-right tnum font-semibold">Rp {{ number_format($cashRequest->amount, 0, ',', '.')}}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $cashRequest->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : ($cashRequest->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-900') }}">
                                {{ $cashRequest->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-stone-600">{{ $cashRequest->decision_notes ?? 'Belum ada keputusan' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada permintaan kas. Isi formulir di atas bila laci butuh modal tunai.</td>
                    </tr>
                @endforelse
        </x-data-table>
    </section>
@endsection
