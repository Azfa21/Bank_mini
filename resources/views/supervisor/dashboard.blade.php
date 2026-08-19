@extends('layouts.app')

@section('title', 'Dashboard Supervisor — Bank Mini')
@section('section', 'Supervisor')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard Supervisor</h1>
        <p class="text-sm text-gray-500 mt-1">Validasi laporan, pantau likuiditas, dan approve penutupan kas.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Menunggu Approval</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $waiting }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Laporan Disetujui</div>
            <div class="text-2xl font-semibold text-green-600 mt-1">{{ $approved }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Transaksi Hari Ini</div>
            <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $todayTransactions }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">Rasio Likuiditas</div>
            <div class="text-xl font-semibold {{ $summary['liquidity_ratio_percent'] >= 100 ? 'text-green-600' : 'text-red-600' }} mt-1">
                {{ number_format($summary['liquidity_ratio_percent'], 2, ',', '.') }}%
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Tren Setoran vs Penarikan (7 Hari Terakhir)</h2>
            @php
                $maxVal = max(1, $trend->max(fn ($r) => max($r['deposit'], $r['withdrawal'])));
                $chartW = 640;
                $chartH = 160;
                $barW = 34;
                $gap = 44;
                $baseY = $chartH - 12;
            @endphp
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full" role="img" aria-label="Grafik tren setoran dan penarikan">
                <line x1="0" y1="{{ $baseY }}" x2="{{ $chartW }}" y2="{{ $baseY }}" stroke="#e5e7eb" stroke-width="1"/>
                @foreach ($trend as $i => $row)
                    @php
                        $cx = 40 + $i * $gap;
                        $depH = max(2, round($row['deposit'] / $maxVal * ($chartH - 30)));
                        $wdH = max(2, round($row['withdrawal'] / $maxVal * ($chartH - 30)));
                    @endphp
                    <rect x="{{ $cx - $barW }}" y="{{ $baseY - $depH }}" width="{{ $barW }}" height="{{ $depH }}" fill="#22c55e"/>
                    <rect x="{{ $cx }}" y="{{ $baseY - $wdH }}" width="{{ $barW }}" height="{{ $wdH }}" fill="#ef4444"/>
                    <text x="{{ $cx + 1 }}" y="{{ $chartH }}" text-anchor="middle" font-size="10" fill="#6b7280">{{ $row['label'] }}</text>
                @endforeach
            </svg>
            <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-green-500 rounded"></span> Setoran</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-red-500 rounded"></span> Penarikan</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Posisi Kas</h2>
            <div class="space-y-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Kas Teller (Laci)</span>
                    <span class="font-mono">Rp {{ number_format($summary['cash_teller'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Kas Brankas (102)</span>
                    <span class="font-mono">Rp {{ number_format($summary['cash_vault'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t border-gray-100 pt-3">
                    <span class="text-gray-900">Total Kas Fisik</span>
                    <span class="font-mono text-blue-700">Rp {{ number_format($summary['total_cash'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Kewajiban Simpanan</span>
                    <span class="font-mono">Rp {{ number_format($summary['total_savings'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h2 class="text-sm font-medium text-gray-900">Akses Cepat</h2>
            <a href="{{ route('supervisor.reports.index') }}" class="block rounded-lg border border-gray-200 p-4 hover:border-blue-300 transition-colors">
                <div class="text-sm font-medium text-gray-900">Laporan Harian Teller</div>
                <div class="text-xs text-gray-500 mt-1">Periksa, setujui, atau tolak laporan penutupan kas.</div>
            </a>
            <a href="{{ route('supervisor.reports.trial-balance') }}" class="block rounded-lg border border-gray-200 p-4 hover:border-blue-300 transition-colors">
                <div class="text-sm font-medium text-gray-900">Neraca Saldo</div>
                <div class="text-xs text-gray-500 mt-1">Periksa keseimbangan jurnal dan likuiditas.</div>
            </a>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-medium text-gray-900 mb-4">Aktivitas Terbaru</h2>
            <div class="space-y-3">
                @forelse ($recentLogs as $log)
                    <div class="flex items-start justify-between text-sm">
                        <div>
                            <p class="font-mono text-xs text-gray-500">{{ $log->action }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if ($log->metadata)
                                    {{ collect($log->metadata)->filter(fn ($v) => is_scalar($v))->take(2)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ') }}
                                @endif
                            </p>
                        </div>
                        <p class="text-xs text-gray-400 shrink-0">{{ $log->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection