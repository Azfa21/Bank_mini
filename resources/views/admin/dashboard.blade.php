@extends('layouts.app')

@section('title', 'Dashboard Admin | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-stone-900">Dashboard Administrator</h1>
        <p class="text-sm text-stone-500 mt-1">Ringkasan operasional.</p>
    </div>

    <section aria-label="Antrean validasi laporan" class="bg-white rounded-xl border {{ $waiting > 0 ? 'border-amber-300' : 'border-stone-200' }} ledger-rule p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div>
                @if ($waiting > 0)
                    <h2 class="text-lg font-bold text-stone-900">{{ $waiting }} laporan menunggu validasi</h2>
                    <p class="text-sm text-stone-600 mt-1">Periksa arus kas laporan sebelum menyetujui. Persetujuan memindahkan kas laci ke kas utama.</p>
                @else
                    <h2 class="text-lg font-bold text-stone-900">Tidak ada laporan menunggu</h2>
                    <p class="text-sm text-stone-600 mt-1">Semua laporan harian teller sudah divalidasi. Antrean baru muncul setiap penutupan kas.</p>
                @endif
            </div>
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-5 text-[15px] font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors sm:shrink-0">
                {{ $waiting > 0 ? 'Periksa laporan' : 'Lihat semua laporan' }}
            </a>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 pt-4 border-t border-stone-100">
            <div class="flex sm:flex-col justify-between sm:justify-start gap-3">
                <dt class="text-[13px] font-medium text-stone-600">Menunggu validasi</dt>
                <dd class="text-xl font-bold text-amber-800 tnum sm:mt-1">{{ $waiting }}</dd>
            </div>
            <div class="flex sm:flex-col justify-between sm:justify-start gap-3">
                <dt class="text-[13px] font-medium text-stone-600">Sudah disetujui</dt>
                <dd class="text-xl font-bold text-brand-900 tnum sm:mt-1">{{ $approved }}</dd>
            </div>
            <div class="flex sm:flex-col justify-between sm:justify-start gap-3">
                <dt class="text-[13px] font-medium text-stone-600">Transaksi hari ini</dt>
                <dd class="text-xl font-bold text-stone-900 tnum sm:mt-1">{{ $todayTransactions }}</dd>
            </div>
        </dl>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('admin.users.index') }}" class="bg-white rounded-xl border border-stone-200 p-5 hover:border-brand-300 transition-colors">
            <div class="text-sm text-stone-500">Pengguna internal aktif</div>
            <div class="text-2xl font-semibold text-stone-900 mt-1">{{ $summary['total_users'] }}</div>
        </a>
        <div class="bg-white rounded-xl border border-stone-200 p-5">
            <a href="{{ route('admin.savers.index') }}" class="block hover:opacity-80">
                <div class="text-sm text-stone-500">Penabung aktif</div>
                <div class="text-2xl font-semibold text-stone-900 mt-1">{{ $summary['total_savers'] }}</div>
            </a>
            <a href="{{ route('admin.graduations.index') }}" class="text-xs font-medium text-brand-900 hover:underline mt-1.5 inline-block">
                {{ $summary['total_alumni'] }} alumni diarsipkan
            </a>
        </div>
        <a href="{{ route('admin.savers.index') }}" class="bg-white rounded-xl border border-stone-200 p-5 hover:border-brand-300 transition-colors">
            <div class="text-sm text-stone-500">Rekening aktif</div>
            <div class="text-2xl font-semibold text-stone-900 mt-1">{{ $summary['total_accounts'] }}</div>
        </a>
        <a href="{{ route('admin.config.edit') }}" class="bg-white rounded-xl border border-stone-200 p-5 hover:border-brand-300 transition-colors">
            <div class="text-sm text-stone-500">Saldo minimum</div>
            <div class="text-2xl font-semibold text-stone-900 mt-1">Rp {{ number_format((int) (\App\Models\SystemConfig::find('minimum_balance')->config_value ?? 10000), 0, ',', '.') }}</div>
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-stone-200 p-5">
            <div class="text-sm text-stone-500">Kas teller (laci)</div>
            <div class="text-xl font-semibold text-stone-900 mt-1">Rp {{ number_format($summary['cash_teller'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-5">
            <div class="text-sm text-stone-500">Kas utama</div>
            <div class="text-xl font-semibold text-stone-900 mt-1">Rp {{ number_format($summary['cash_vault'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-5">
            <div class="text-sm text-stone-500">Total kas (laci + kas utama)</div>
            <div class="text-xl font-semibold text-brand-900 mt-1">Rp {{ number_format($summary['total_cash'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 p-5">
            <h2 class="text-sm font-medium text-stone-900 mb-4">Tren setoran vs penarikan (7 hari terakhir)</h2>
            @php
                $maxVal = max(1, $trend->max(fn ($r) => max($r['deposit'], $r['withdrawal'])));
                $chartW = 640;
                $chartH = 160;
                $barW = 34;
                $gap = 44;
                $baseY = $chartH - 12;
            @endphp
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full" role="img" aria-label="Grafik tren setoran dan penarikan">
                <line x1="0" y1="{{ $baseY }}" x2="{{ $chartW }}" y2="{{ $baseY }}" stroke="#e7e5e4" stroke-width="1"/>
                @foreach ($trend as $i => $row)
                    @php
                        $cx = 40 + $i * $gap;
                        $depH = max(2, round($row['deposit'] / $maxVal * ($chartH - 30)));
                        $wdH = max(2, round($row['withdrawal'] / $maxVal * ($chartH - 30)));
                    @endphp
                    <rect x="{{ $cx - $barW }}" y="{{ $baseY - $depH }}" width="{{ $barW }}" height="{{ $depH }}" fill="#697540"/>
                    <rect x="{{ $cx }}" y="{{ $baseY - $wdH }}" width="{{ $barW }}" height="{{ $wdH }}" fill="#ef4444"/>
                    <text x="{{ $cx + 1 }}" y="{{ $chartH }}" text-anchor="middle" font-size="10" fill="#78716c">{{ $row['label'] }}</text>
                @endforeach
            </svg>
            <div class="flex items-center gap-4 mt-3 text-xs text-stone-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-olive-500 rounded"></span> Setoran</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-red-500 rounded"></span> Penarikan</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-stone-200 p-5">
            <h2 class="text-sm font-medium text-stone-900 mb-4">Setoran terbesar (kumulatif)</h2>
            <div class="space-y-3">
                @forelse ($topDepositors as $item)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-stone-900">{{ $item['name'] }}</p>
                            <p class="text-xs text-stone-500 font-mono">{{ $item['account_number'] }}</p>
                        </div>
                        <p class="font-mono text-olive-700">Rp {{ number_format($item['total'], 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Belum ada data setoran.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 p-5">
            <h2 class="text-sm font-medium text-stone-900 mb-4">Performa Teller</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 text-left">
                        <tr>
                            <th class="px-4 py-2.5">Teller</th>
                            <th class="px-4 py-2.5 text-right">Jumlah Transaksi</th>
                            <th class="px-4 py-2.5 text-right">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($tellerPerformance as $row)
                            <tr>
                                <td class="px-4 py-2.5 font-medium">{{ $row['name'] }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $row['total_transactions'] }}</td>
                                <td class="px-4 py-2.5 text-right font-mono">Rp {{ number_format($row['total_amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-stone-500">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-stone-200 p-5 space-y-4">
            <h2 class="text-sm font-medium text-stone-900">Akses Cepat</h2>
            <a href="{{ route('admin.users.index') }}" class="block rounded-lg border border-stone-200 p-4 hover:border-brand-300 transition-colors">
                <div class="text-sm font-medium text-stone-900">Kelola Pengguna</div>
                <div class="text-xs text-stone-500 mt-1">Tambah, ubah, blokir akun internal.</div>
            </a>
            <a href="{{ route('admin.savers.index') }}" class="block rounded-lg border border-stone-200 p-4 hover:border-brand-300 transition-colors">
                <div class="text-sm font-medium text-stone-900">Kelola Penabung</div>
                <div class="text-xs text-stone-500 mt-1">Profil siswa, rekening, dan saldo.</div>
            </a>
            <a href="{{ route('admin.reports.general-ledger') }}" class="block rounded-lg border border-stone-200 p-4 hover:border-brand-300 transition-colors">
                <div class="text-sm font-medium text-stone-900">Buku Besar &amp; Mutasi Kas</div>
                <div class="text-xs text-stone-500 mt-1">Saldo akun kas dan mutasi per akun.</div>
            </a>
            <a href="{{ route('admin.config.edit') }}" class="block rounded-lg border border-stone-200 p-4 hover:border-brand-300 transition-colors">
                <div class="text-sm font-medium text-stone-900">Konfigurasi Sistem</div>
                <div class="text-xs text-stone-500 mt-1">Saldo minimum dan pengaturan QR Code.</div>
            </a>
        </div>
    </div>

    <section aria-label="Aktivitas terbaru" class="mt-6 bg-white rounded-xl border border-stone-200 p-5">
        <h2 class="text-sm font-medium text-stone-900 mb-4">Aktivitas terbaru (5 terakhir)</h2>
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
                <p class="text-sm text-stone-600">Belum ada aktivitas tercatat. Persetujuan laporan dan koreksi transaksi tampil di sini.</p>
            @endforelse
        </div>
    </section>
@endsection
