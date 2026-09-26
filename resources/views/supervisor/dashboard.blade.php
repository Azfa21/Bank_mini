@extends('layouts.app')

@section('title', 'Dashboard Supervisor — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">PENGAWASAN LOKET</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Validasi laporan dan kas</h1>
        <p class="text-sm text-stone-600 mt-1">Fokus ke laporan yang menunggu, baru pantau likuiditas.</p>
    </div>

    <section aria-label="Antrean validasi" class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-stone-900">{{ $waiting }} laporan menunggu approval</h2>
                <p class="text-sm text-stone-600 mt-1">Setiap laporan yang disetujui memindahkan kas laci ke brankas utama.</p>
            </div>
            <a href="{{ route('supervisor.reports.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors sm:shrink-0">
                Periksa laporan
            </a>
        </div>
    </section>

    <section aria-label="Ringkasan pengawasan" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Menunggu approval</dt>
            <dd class="text-2xl font-bold text-stone-900 mt-1 tnum">{{ $waiting }}</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Disetujui</dt>
            <dd class="text-2xl font-bold text-brand-900 mt-1 tnum">{{ $approved }}</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Transaksi hari ini</dt>
            <dd class="text-2xl font-bold text-stone-900 mt-1 tnum">{{ $todayTransactions }}</dd>
        </dl>
        <dl class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <dt class="text-[13px] font-medium text-stone-600">Rasio likuiditas</dt>
            <dd class="text-xl font-bold mt-1 tnum {{ $summary['liquidity_ratio_percent'] >= 100 ? 'text-brand-900' : 'text-red-800' }}">
                {{ number_format($summary['liquidity_ratio_percent'], 2, ',', '.') }}%
            </dd>
        </dl>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 mb-4">
        <section aria-label="Tren tujuh hari" class="lg:col-span-2 bg-white rounded-2xl border border-stone-200 p-5">
            <h2 class="text-[15px] font-bold text-stone-900 mb-4">Setoran vs penarikan, 7 hari terakhir</h2>
            @php
                $maxVal = max(1, $trend->max(fn ($r) => max($r['deposit'], $r['withdrawal'])));
                $chartW = 640;
                $chartH = 160;
                $barW = 34;
                $gap = 44;
                $baseY = $chartH - 12;
            @endphp
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full" role="img" aria-label="Grafik tren setoran dan penarikan tujuh hari terakhir">
                <line x1="0" y1="{{ $baseY }}" x2="{{ $chartW }}" y2="{{ $baseY }}" stroke="#e7e5e4" stroke-width="1"/>
                @foreach ($trend as $i => $row)
                    @php
                        $cx = 40 + $i * $gap;
                        $depH = max(2, round($row['deposit'] / $maxVal * ($chartH - 30)));
                        $wdH = max(2, round($row['withdrawal'] / $maxVal * ($chartH - 30)));
                    @endphp
                    <rect x="{{ $cx - $barW }}" y="{{ $baseY - $depH }}" width="{{ $barW }}" height="{{ $depH }}" fill="#743722" rx="3"/>
                    <rect x="{{ $cx }}" y="{{ $baseY - $wdH }}" width="{{ $barW }}" height="{{ $wdH }}" fill="#b91c1c" rx="3"/>
                    <text x="{{ $cx + 1 }}" y="{{ $chartH }}" text-anchor="middle" font-size="10" fill="#57534e">{{ $row['label'] }}</text>
                @endforeach
            </svg>
            <div class="flex items-center gap-4 mt-3 text-[13px] text-stone-600">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:#743722" aria-hidden="true"></span> Setoran</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:#b91c1c" aria-hidden="true"></span> Penarikan</span>
            </div>
        </section>

        <section aria-label="Posisi kas" class="bg-white rounded-2xl border border-stone-200 p-5">
            <h2 class="text-[15px] font-bold text-stone-900 mb-3">Posisi kas</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Kas laci Teller</dt>
                    <dd class="tnum font-medium text-stone-900">Rp {{ number_format($summary['cash_teller'], 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Kas brankas 102</dt>
                    <dd class="tnum font-medium text-stone-900">Rp {{ number_format($summary['cash_vault'], 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3 font-bold border-t border-stone-100 pt-3">
                    <dt class="text-stone-900">Total kas fisik</dt>
                    <dd class="tnum text-stone-900">Rp {{ number_format($summary['total_cash'], 0, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-stone-600">Kewajiban simpanan</dt>
                    <dd class="tnum text-stone-800">Rp {{ number_format($summary['total_savings'], 0, ',', '.') }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <nav aria-label="Akses cepat supervisor" class="bg-white rounded-2xl border border-stone-200 p-5 space-y-2.5">
            <h2 class="text-[15px] font-bold text-stone-900">Akses cepat</h2>
            <a href="{{ route('supervisor.reports.index') }}" class="block rounded-xl border border-stone-200 p-4 hover:border-brand-800 transition-colors">
                <p class="text-sm font-semibold text-stone-900">Laporan harian Teller</p>
                <p class="text-[13px] text-stone-600 mt-1">Periksa, setujui, atau tolak penutupan kas.</p>
            </a>
            <a href="{{ route('supervisor.reports.trial-balance') }}" class="block rounded-xl border border-stone-200 p-4 hover:border-brand-800 transition-colors">
                <p class="text-sm font-semibold text-stone-900">Neraca saldo</p>
                <p class="text-[13px] text-stone-600 mt-1">Periksa keseimbangan jurnal dan likuiditas.</p>
            </a>
        </nav>

        <section aria-label="Aktivitas terbaru" class="lg:col-span-2 bg-white rounded-2xl border border-stone-200 p-5">
            <h2 class="text-[15px] font-bold text-stone-900 mb-3">Aktivitas terbaru</h2>
            <div class="space-y-3">
                @forelse ($recentLogs as $log)
                    <div class="flex items-start justify-between gap-3 text-sm border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="tnum text-[13px] font-medium text-stone-800">{{ $log->action }}</p>
                            <p class="text-[13px] text-stone-600 mt-0.5">
                                @if ($log->metadata)
                                    {{ collect($log->metadata)->filter(fn ($v) => is_scalar($v))->take(2)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ') }}
                                @endif
                            </p>
                        </div>
                        <p class="text-[13px] text-stone-500 shrink-0 tnum">{{ $log->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-600">Belum ada aktivitas tercatat. Aktivitas approval dan koreksi tampil di sini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
