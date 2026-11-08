@extends('layouts.app')

@section('title', 'Kenaikan & Kelulusan Siswa | Bank Mini')
@section('section', 'Administrator · Data Siswa')

@section('content')
    @include('admin.student-lifecycle.switcher', ['activeSection' => 'graduations'])

    @php
        $pendingTotal = $pendingByYear->sum() + $unassignedClosures->count();
    @endphp
    @if ($pendingTotal > 0)
        <p class="inline-flex flex-wrap items-center gap-1.5 mb-4 text-sm font-medium text-amber-900 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            {{ $pendingTotal }} penutupan rekening menunggu keputusan.
        </p>
    @endif

    @if ($alumniCount > 0)
        <p class="mb-4">
            <a href="{{ route('admin.graduations.export') }}" class="inline-flex items-center justify-center min-h-[44px] px-3 -ml-3 text-sm font-semibold text-brand-900 hover:underline">Unduh arsip CSV seluruh alumni ({{ $alumniCount }})</a>
        </p>
    @endif

    <x-form-errors />

    @if ($unassignedClosures->isNotEmpty())
        <section aria-label="Penutupan tanpa angkatan" class="mb-5 bg-white rounded-2xl border border-amber-300 p-4 sm:p-5">
            <h2 class="text-base font-bold text-stone-900">Penutupan menunggu keputusan tanpa angkatan</h2>
            <p class="text-sm text-stone-600 mt-1">Penabung ini belum punya angkatan keluar, jadi tidak muncul di daftar angkatan. Putuskan di sini supaya saldo yang sudah dicairkan tidak menggantung.</p>

            <ul class="mt-4 divide-y divide-stone-100">
                @foreach ($unassignedClosures as $closure)
                    <li class="py-4 first:pt-0 last:pb-0">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900">{{ $closure->saver?->full_name ?? '-' }}</p>
                                <p class="text-xs text-stone-600 font-mono">{{ $closure->saver?->student_number ?? '-' }} · {{ $closure->account?->account_number ?? '-' }}</p>
                                <p class="text-xs text-stone-700 mt-1 tnum">
                                    @if ((int) $closure->payout_amount > 0)
                                        Cair Rp {{ number_format((int) $closure->payout_amount, 0, ',', '.') }}
                                    @else
                                        Tanpa pencairan tunai
                                    @endif
                                    · {{ $closure->requester?->full_name ?? 'Teller' }}
                                    · {{ $closure->created_at?->format('d/m/Y H:i') }}
                                </p>
                                @if ($closure->notes)
                                    <p class="text-xs text-stone-600 mt-1">Catatan Teller: {{ $closure->notes }}</p>
                                @endif
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:shrink-0">
                                <form method="POST" action="{{ route('admin.graduations.closures.approve', $closure) }}" data-confirm="Setujui penutupan rekening ini? Rekening ditutup dan penabung tercatat sebagai alumni.">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-brand-900 hover:underline">Setujui</button>
                                </form>

                                <details class="w-full sm:w-72" @if ($errors->has('rejection_reason')) open @endif>
                                    <summary class="inline-flex items-center min-h-[44px] px-3 text-sm font-semibold text-red-800 cursor-pointer select-none">Tolak</summary>
                                    <form method="POST" action="{{ route('admin.graduations.closures.reject', $closure) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <x-input-label for="rejection-{{ $closure->id }}">Alasan penolakan wajib</x-input-label>
                                        <x-input id="rejection-{{ $closure->id }}" name="rejection_reason" type="text" maxlength="500" required placeholder="Misalnya siswa belum lulus" />
                                        <x-primary-button variant="danger" class="w-full">Tolak dan kembalikan saldo</x-primary-button>
                                    </form>
                                </details>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <form method="GET" action="{{ route('admin.student-lifecycle.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end">
        <input type="hidden" name="section" value="graduations">
        <div class="sm:w-60">
            <x-input-label for="year">Angkatan keluar</x-input-label>
            <x-select id="year" name="year">
                <option value="">Pilih angkatan</option>
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}@if (($pendingByYear[$option] ?? 0) > 0) · {{ $pendingByYear[$option] }} menunggu keputusan @endif</option>
                @endforeach
            </x-select>
        </div>
        <x-primary-button>Tampilkan angkatan</x-primary-button>
    </form>

    @if ($year === null || $year === '')
        <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
            @if ($years->isEmpty())
                Belum ada data angkatan keluar. Isi kolom angkatan pada data anggota atau impor CSV agar kelulusan bisa diproses per angkatan.
            @else
                Pilih angkatan keluar untuk melihat daftar siswa dan status kelulusannya.
            @endif
        </div>
    @else
        @php
            $pendingApproval = $rows->where('state', 'pending_approval');
            $pendingCounter = $rows->where('state', 'pending_counter');
            $ready = $rows->whereIn('state', ['ready', 'roster_only']);
            $alumniInYear = $rows->where('state', 'alumni')->count();
        @endphp

        <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 mb-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div class="text-sm text-stone-700">
                Angkatan <span class="font-semibold text-stone-900">{{ $year }}</span>:
                <span class="tnum font-semibold text-stone-900">{{ $rows->count() }}</span> siswa,
                <span class="tnum font-semibold text-stone-900">{{ $ready->count() }}</span> siap diarsipkan,
                <span class="tnum font-semibold text-stone-900">{{ $pendingApproval->count() }}</span> menunggu keputusan,
                <span class="tnum font-semibold text-stone-900">{{ $pendingCounter->count() }}</span> menunggu loket.
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                @if ($ready->isNotEmpty())
                    <form method="POST" action="{{ route('admin.graduations.store') }}" data-confirm="Proses kelulusan angkatan {{ $year }}? Anggota tanpa rekening dan penabung yang saldonya tinggal saldo mengendap akan diarsipkan. Siswa yang ditandai tidak lulus tidak ikut diproses.">
                        @csrf
                        <input type="hidden" name="graduation_year" value="{{ $year }}">
                        <x-primary-button>Proses kelulusan angkatan</x-primary-button>
                    </form>
                @elseif ($rows->isEmpty())
                    <span class="text-sm text-stone-500">Tidak ada siswa pada angkatan ini.</span>
                @else
                    <span class="text-sm text-stone-500">Semua siswa sudah diproses, menunggu keputusan, atau menunggu loket.</span>
                @endif

                @if ($alumniInYear > 0)
                    <a href="{{ route('admin.graduations.export', ['year' => $year]) }}" class="inline-flex items-center justify-center min-h-[44px] px-3 text-sm font-semibold text-brand-900 hover:underline">Unduh CSV alumni angkatan ini ({{ $alumniInYear }})</a>
                @endif
            </div>
        </div>

        <x-data-table caption="Daftar siswa per angkatan dan status kelulusannya" :headers="[['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Rekening'], ['label' => 'Saldo', 'align' => 'right'], ['label' => 'Status kelulusan'], ['label' => 'Tindakan', 'align' => 'right', 'sticky' => true]]">
            @forelse ($rows as $row)
                @php
                    $state = $row['state'];
                    $closure = $row['closure'];
                    $payout = (int) ($closure?->payout_amount ?? 0);
                    $labels = [
                        'alumni' => ['Alumni', 'bg-stone-200 text-stone-700'],
                        'roster_only' => ['Siap diarsipkan', 'bg-brand-50 text-brand-900'],
                        'ready' => ['Siap ditutup', 'bg-brand-50 text-brand-900'],
                        'pending_approval' => ['Menunggu keputusan', 'bg-amber-50 text-amber-900'],
                        'pending_counter' => ['Menunggu loket', 'bg-amber-50 text-amber-900'],
                    ];
                    [$stateLabel, $stateStyle] = $labels[$state] ?? [$state, 'bg-stone-100 text-stone-700'];
                    $keterangan = match ($state) {
                        'roster_only' => 'Belum punya rekening penabung.',
                        'ready' => 'Sisa saldo tinggal saldo mengendap, aman ditutup dan diarsipkan.',
                        'pending_approval' => 'Pencairan di loket menunggu keputusan Administrator.',
                        'pending_counter' => 'Sisa saldo Rp '.number_format((int) $row['account']->balance, 0, ',', '.').', selesaikan di loket Teller.',
                        'alumni' => 'Sudah diarsipkan sebagai alumni.',
                        default => '-',
                    };
                    $notGraduateConfirm = $state === 'pending_approval' && $payout > 0
                        ? 'Tandai '.$row['member']->full_name.' tidak lulus? Pencairan Rp '.number_format($payout, 0, ',', '.').' dikembalikan ke rekening, rekening tetap aktif, dan siswa masuk angkatan berikutnya.'
                        : 'Tandai '.$row['member']->full_name.' tidak lulus? Rekening tetap aktif dan siswa masuk angkatan berikutnya.';
                    $approveConfirm = $payout > 0
                        ? 'Setujui pencairan Rp '.number_format($payout, 0, ',', '.').'? Rekening ditutup dan '.$row['member']->full_name.' tercatat sebagai alumni.'
                        : 'Setujui penutupan rekening ini? Rekening ditutup dan '.$row['member']->full_name.' tercatat sebagai alumni.';
                @endphp
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3 font-mono text-xs">{{ $row['member']->nis }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-stone-900">{{ $row['member']->full_name }}</div>
                        <div class="text-xs text-stone-500">{{ $keterangan }}</div>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs">{{ $row['account']?->account_number ?? '-' }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format((int) ($row['account']?->balance ?? 0), 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[13px] font-semibold {{ $stateStyle }}">{{ $stateLabel }}</span>
                        @if ($state === 'pending_approval' && $closure)
                            <div class="text-xs text-stone-700 mt-1 tnum">
                                @if ($payout > 0)
                                    Cair Rp {{ number_format($payout, 0, ',', '.') }}
                                @else
                                    Tanpa pencairan tunai
                                @endif
                                · {{ $closure->requester?->full_name ?? 'Teller' }}
                                · {{ $closure->created_at?->format('d/m/Y H:i') }}
                            </div>
                            @if ($closure->notes)
                                <div class="text-xs text-stone-600 mt-1">Catatan Teller: {{ $closure->notes }}</div>
                            @endif
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right sticky right-0 bg-white border-l border-stone-200">
                        <div class="flex flex-wrap items-center justify-end gap-x-2 gap-y-1">
                            @if (in_array($state, ['roster_only', 'ready'], true))
                                <form method="POST" action="{{ route('admin.graduations.member', $row['member']) }}" data-confirm="Arsipkan {{ $row['member']->full_name }} sebagai alumni sekarang? Rekening dan saldo mengendap ikut ditutup.">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-2 text-sm font-semibold text-brand-900 hover:underline">Luluskan</button>
                                </form>
                            @elseif ($state === 'pending_approval' && $closure)
                                <form method="POST" action="{{ route('admin.graduations.closures.approve', $closure) }}" data-confirm="{{ $approveConfirm }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-2 text-sm font-semibold text-brand-900 hover:underline">Setujui</button>
                                </form>
                            @endif

                            @if ($state !== 'alumni')
                                <form method="POST" action="{{ route('admin.graduations.not-graduate', $row['member']) }}" data-confirm="{{ $notGraduateConfirm }}">
                                    @csrf
                                    <input type="hidden" name="graduation_year" value="{{ $row['member']->graduation_year }}">
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-2 text-sm font-semibold text-red-800 hover:underline">Tidak lulus</button>
                                </form>
                            @else
                                <span class="text-xs text-stone-600">-</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm text-stone-600">Tidak ada siswa pada angkatan ini.</td>
                </tr>
            @endforelse
        </x-data-table>
    @endif
@endsection
