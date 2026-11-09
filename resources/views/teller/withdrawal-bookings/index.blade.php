@extends('layouts.app')

@section('title', 'Booking Penarikan | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">PERSETUJUAN TELLER</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Booking penarikan</h1>
        <p class="text-sm text-stone-600 mt-1">Tinjau rencana penarikan harian di atas Rp 1.000.000. Persetujuan menaikkan batas total hanya pada tanggal booking.</p>
    </div>

    @if ($bookings->isEmpty())
        <div class="bg-white rounded-2xl border border-stone-200 p-6 sm:p-8 text-center">
            <h2 class="text-base font-bold text-stone-900">Belum ada booking penarikan</h2>
            <p class="text-sm text-stone-600 mt-2">Pengajuan nasabah akan muncul di sini setelah dikirim dari aplikasi mobile.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($bookings as $booking)
                @php
                    $statusStyle = match ($booking->status) {
                        'pending' => 'border-amber-200 bg-amber-50 text-amber-900',
                        'approved' => 'border-olive-200 bg-olive-50 text-olive-700',
                        default => 'border-red-200 bg-red-50 text-red-800',
                    };
                    $accountBalance = (int) $booking->account->balance;
                    $minBalance = $booking->account->effectiveMinimumBalance();
                    $isBalanceSufficient = ($accountBalance >= (int) $booking->amount) && (($accountBalance - (int) $booking->amount) >= $minBalance);
                @endphp
                <article class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-stone-900">{{ $booking->account->saver?->full_name ?? 'Nasabah' }}</h2>
                            <p class="text-[13px] text-stone-600 mt-1">No. rekening <span class="tnum">{{ $booking->account->account_number }}</span></p>
                        </div>
                        <span class="inline-flex self-start rounded-full border px-3 py-1 text-[13px] font-semibold {{ $statusStyle }}">{{ $booking->statusLabel() }}</span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-4 gap-3 mt-4 border-t border-stone-100 pt-4">
                        <div>
                            <dt class="text-[13px] text-stone-600">Tanggal penarikan</dt>
                            <dd class="tnum font-semibold text-stone-900 mt-1">{{ $booking->withdrawal_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[13px] text-stone-600">Total rencana harian</dt>
                            <dd class="tnum font-semibold text-stone-900 mt-1">Rp {{ number_format($booking->amount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[13px] text-stone-600">Saldo saat ini</dt>
                            <dd class="tnum font-semibold {{ $isBalanceSufficient ? 'text-stone-900' : 'text-red-700' }} mt-1">
                                Rp {{ number_format($accountBalance, 0, ',', '.') }}
                            </dd>
                            <p class="text-xs text-stone-500 mt-0.5">Mengendap Rp {{ number_format($minBalance, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <dt class="text-[13px] text-stone-600">Diajukan</dt>
                            <dd class="tnum font-semibold text-stone-900 mt-1">{{ $booking->created_at?->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>

                    @if ($booking->notes)
                        <p class="mt-4 text-sm text-stone-700"><span class="font-semibold">Keterangan nasabah:</span> {{ $booking->notes }}</p>
                    @endif

                    @if ($booking->status === 'pending' && ! $isBalanceSufficient)
                        <p class="mt-3 text-[13px] font-medium text-red-700">Perhatian: Saldo rekening saat ini tidak mencukupi untuk memenuhi nominal penarikan dan saldo mengendap.</p>
                    @endif

                    @if ($booking->status === 'pending')
                        <div class="mt-5 flex flex-col sm:flex-row gap-3 border-t border-stone-100 pt-4">
                            <form method="POST" action="{{ route('teller.withdrawal-bookings.approve', $booking) }}">
                                @csrf
                                <x-primary-button class="cursor-pointer" data-loading-text="Menyetujui...">Setujui booking</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('teller.withdrawal-bookings.reject', $booking) }}" class="flex flex-col sm:flex-row gap-2 flex-1">
                                @csrf
                                <div class="flex-1">
                                    <label class="mb-1 block text-[13px] font-medium text-stone-700" for="decision-note-{{ $booking->id }}">Alasan penolakan (opsional)</label>
                                    <input id="decision-note-{{ $booking->id }}" name="decision_note" maxlength="500" class="min-h-[44px] w-full rounded-lg border border-stone-400 px-3 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200" placeholder="Tulis alasan untuk nasabah">
                                </div>
                                <button type="submit" class="min-h-[44px] cursor-pointer rounded-lg border border-red-800 px-4 text-sm font-semibold text-red-800 hover:bg-red-50 active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-300">Tolak booking</button>
                            </form>
                        </div>
                    @else
                        <div class="mt-4 border-t border-stone-100 pt-4 text-[13px] text-stone-600">
                            <p>{{ $booking->statusLabel() }}{{ $booking->decider ? ' oleh '.$booking->decider->full_name : '' }}{{ $booking->decided_at ? ' pada '.$booking->decided_at->format('d/m/Y H:i') : '' }}.</p>
                            @if ($booking->decision_note)
                                <p class="mt-1"><span class="font-semibold">Catatan teller:</span> {{ $booking->decision_note }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-5">{{ $bookings->links() }}</div>
    @endif
@endsection
