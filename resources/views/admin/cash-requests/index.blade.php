@extends('layouts.app')

@section('title', 'Permintaan Kas | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">KEPUTUSAN KAS</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Permintaan kas teller</h1>
        <p class="text-sm text-stone-600 mt-1">Setuju berarti kas dianggap cair: jurnal langsung memindahkan tanggung jawab kas dari kas utama ke laci teller.</p>
    </div>

    <section aria-label="Saldo pos kas" class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 mb-4 sm:mb-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <div class="text-[13px] font-medium text-stone-600">Kas utama (akun 102)</div>
            <div class="tnum text-2xl font-bold text-stone-900 mt-1">Rp {{ number_format($mainCashBalance, 0, ',', '.') }}</div>
            <p class="text-xs text-stone-600 mt-1">Sisa kas akhir hari dari penyapuan laporan harian.</p>
        </div>
        <div>
            <div class="text-[13px] font-medium text-stone-600">Kas laci teller (akun 101)</div>
            <div class="tnum text-2xl font-bold text-stone-900 mt-1">Rp {{ number_format($drawerCashBalance, 0, ',', '.') }}</div>
            <p class="text-xs text-stone-600 mt-1">Kas yang sedang memikul tanggung jawab di loket.</p>
        </div>
    </section>

    <section aria-label="Permintaan menunggu keputusan" class="mb-4 sm:mb-5">
        <h2 class="text-[15px] font-bold text-stone-900 mb-3">Menunggu keputusan</h2>
        @if ($pending->isEmpty())
            <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
                Belum ada permintaan kas yang menunggu. Teller mengajukannya lewat menu Permintaan Kas.
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($pending as $cashRequest)
                    <li class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col lg:flex-row lg:items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="tnum font-bold text-stone-900">{{ $cashRequest->request_number }}</p>
                                <p class="tnum text-sm font-semibold text-brand-900">Rp {{ number_format($cashRequest->amount, 0, ',', '.') }}</p>
                            </div>
                            <p class="text-sm text-stone-700 mt-1 font-medium">{{ $cashRequest->teller->full_name }}</p>
                            <p class="tnum text-xs text-stone-500 mt-1">Diajukan {{ $cashRequest->created_at?->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:shrink-0">
                            <form method="POST" action="{{ route('admin.cash-requests.approve', $cashRequest) }}" class="w-full sm:w-72" data-confirm="Setujui permintaan kas ini? Jurnal pengiriman kas langsung dibuat.">
                                @csrf
                                <x-primary-button class="w-full">Setujui</x-primary-button>
                            </form>

                            <details class="w-full sm:w-72" @if ($errors->has('decision_notes')) open @endif>
                                <summary class="inline-flex items-center min-h-[44px] px-3 text-sm font-semibold text-red-800 cursor-pointer select-none">Tolak</summary>
                                <form method="POST" action="{{ route('admin.cash-requests.reject', $cashRequest) }}" class="mt-2 space-y-2">
                                    @csrf
                                    <x-input-label for="reject-{{ $cashRequest->id }}">Alasan penolakan wajib</x-input-label>
                                    <x-input id="reject-{{ $cashRequest->id }}" name="decision_notes" type="text" maxlength="500" required placeholder="Contoh: Kas utama belum mencukupi hari ini" />
                                    <x-primary-button variant="danger" class="w-full">Tolak permintaan</x-primary-button>
                                </form>
                            </details>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-label="Riwayat keputusan kas">
        <h2 class="text-[15px] font-bold text-stone-900 mb-3">Riwayat keputusan</h2>
        <x-data-table caption="Riwayat keputusan permintaan kas" :headers="[['label' => 'Nomor'], ['label' => 'Teller'], ['label' => 'Nominal', 'align' => 'right'], ['label' => 'Diajukan'], ['label' => 'Status'], ['label' => 'Catatan admin'], ['label' => 'Diputuskan']]">
                @forelse ($history as $cashRequest)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 tnum font-medium">{{ $cashRequest->request_number }}</td>
                        <td class="px-5 py-3">{{ $cashRequest->teller->full_name }}</td>
                        <td class="px-5 py-3 text-right tnum font-semibold">Rp {{ number_format($cashRequest->amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 tnum">{{ $cashRequest->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center text-[13px] font-semibold px-2.5 py-1 rounded-full border {{ $cashRequest->status === 'approved' ? 'bg-brand-50 border-brand-200 text-brand-900' : 'bg-red-50 border-red-200 text-red-800' }}">
                                {{ $cashRequest->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-stone-600">{{ $cashRequest->decision_notes ?? 'Tidak ada catatan' }}</td>
                        <td class="px-5 py-3 tnum">{{ $cashRequest->approved_by?->full_name ?? '-' }} · {{ $cashRequest->approved_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-sm text-stone-600">Belum ada keputusan permintaan kas.</td>
                    </tr>
                @endforelse
        </x-data-table>

        @if ($history->hasPages())
            <div class="mt-4">
                {{ $history->links() }}
            </div>
        @endif
    </section>
@endsection
