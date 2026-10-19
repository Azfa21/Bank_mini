@extends('layouts.app')

@section('title', 'Periksa Penutupan Rekening | Bank Mini')
@section('section', 'Administrator · Penutupan Rekening')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">PENUTUPAN REKENING</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Permintaan #{{ $closure->id }}</h1>
            <p class="text-sm text-stone-600 mt-1">Periksa nominal dan data penabung sebelum memutuskan.</p>
        </div>
        <a href="{{ route('admin.closures.index') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Daftar penutupan</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <section aria-label="Rincian permintaan" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule overflow-hidden">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-stone-100">
                    <div class="p-5 sm:p-6">
                        <dt class="text-[13px] font-medium text-stone-600">Penabung</dt>
                        <dd class="text-lg font-bold text-stone-900 mt-1">{{ $closure->saver?->full_name ?? '-' }}</dd>
                        <dd class="text-[13px] text-stone-600 mt-2">No. siswa: <span class="tnum font-medium text-stone-800">{{ $closure->saver?->student_number ?? '-' }}</span></dd>
                        <dd class="text-[13px] text-stone-600 mt-1">No. rekening: <span class="tnum font-medium text-stone-800">{{ $closure->account?->account_number ?? '-' }}</span></dd>
                    </div>
                    <div class="p-5 sm:p-6 bg-stone-50/60">
                        <dt class="text-[13px] font-medium text-stone-600">Nominal dicairkan</dt>
                        <dd class="text-[28px] leading-tight font-bold text-stone-900 mt-1 tnum">Rp {{ number_format($closure->payout_amount, 0, ',', '.') }}</dd>
                        <dd class="text-[13px] text-stone-600 mt-1">Sudah diserahkan Teller di loket.</dd>
                    </div>
                </dl>

                <dl class="border-t border-stone-100 p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-[13px]">
                    <div>
                        <dt class="font-medium text-stone-600">Diajukan oleh</dt>
                        <dd class="text-stone-900 mt-0.5">{{ $closure->requester?->full_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-stone-600">Tanggal pengajuan</dt>
                        <dd class="text-stone-900 mt-0.5 tnum">{{ $closure->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-stone-600">Catatan Teller</dt>
                        <dd class="text-stone-900 mt-0.5">{{ $closure->notes ?: 'Tidak ada catatan.' }}</dd>
                    </div>
                </dl>
            </div>

            @if ($closure->status !== 'pending')
                <div class="bg-white rounded-2xl border border-stone-200 p-5 sm:p-6 mt-4">
                    <h2 class="text-base font-bold text-stone-900">{{ $closure->status === 'approved' ? 'Disetujui' : 'Ditolak' }}</h2>
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 text-[13px]">
                        <div>
                            <dt class="font-medium text-stone-600">Diputuskan oleh</dt>
                            <dd class="text-stone-900 mt-0.5">{{ $closure->decider?->full_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-stone-600">Tanggal keputusan</dt>
                            <dd class="text-stone-900 mt-0.5 tnum">{{ $closure->decided_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-stone-600">Catatan keputusan</dt>
                            <dd class="text-stone-900 mt-0.5">{{ $closure->decision_notes ?: 'Tidak ada catatan.' }}</dd>
                        </div>
                    </dl>
                </div>
            @endif
        </section>

        <aside aria-label="Keputusan" class="lg:col-span-1">
            @if ($closure->status === 'pending')
                <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4 space-y-5">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Setujui penutupan</h2>
                        <p class="text-[13px] text-stone-600 mt-1">Rekening ditutup dan penabung menjadi alumni. Riwayat transaksi tetap tersimpan.</p>
                        <form method="POST" action="{{ route('admin.closures.approve', $closure) }}" class="mt-3 space-y-3" data-confirm="Setujui penutupan ini? Rekening ditutup dan penabung tercatat sebagai alumni.">
                            @csrf
                            <div>
                                <x-input-label for="approve-notes">Catatan (opsional)</x-input-label>
                                <x-textarea id="approve-notes" name="decision_notes" rows="2" maxlength="500" />
                                <x-input-error :message="$errors->first('decision_notes')" />
                            </div>
                            <x-primary-button class="w-full">Setujui penutupan</x-primary-button>
                        </form>
                    </div>

                    <div class="border-t border-stone-100 pt-5">
                        <h2 class="text-base font-bold text-stone-900">Tolak permintaan</h2>
                        <p class="text-[13px] text-stone-600 mt-1">Saldo dikembalikan ke rekening dan penabung tetap aktif. Tuliskan alasannya.</p>
                        <form method="POST" action="{{ route('admin.closures.reject', $closure) }}" class="mt-3 space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="reject-notes">Alasan penolakan wajib</x-input-label>
                                <x-textarea id="reject-notes" name="rejection_reason" rows="2" maxlength="500" required />
                                <x-input-error :message="$errors->first('rejection_reason')" />
                            </div>
                            <x-primary-button variant="danger" class="w-full">Tolak dan kembalikan saldo</x-primary-button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-2xl border border-stone-200 p-5">
                    <h2 class="text-base font-bold text-stone-900">Permintaan sudah diputuskan</h2>
                    <p class="text-[13px] text-stone-600 mt-1">
                        {{ $closure->status === 'approved'
                            ? 'Rekening ditutup dan penabung tercatat sebagai alumni.'
                            : 'Permintaan ditolak; saldo sudah dikembalikan dan rekening tetap aktif.' }}
                    </p>
                </div>
            @endif
        </aside>
    </div>
@endsection
