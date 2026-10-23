@extends('layouts.app')

@section('title', 'Kenaikan Kelas | Bank Mini')
@section('section', 'Administrator · Kenaikan Kelas')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">AKHIR TAHUN AJARAN</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Kenaikan kelas</h1>
        <p class="text-sm text-stone-600 mt-1">Naikkan tingkat anggota dan penabung sekali setahun: X menjadi XI, XI menjadi XII. Jurusan dan nomor rombel mengikuti kelas asal, jadi tidak ada siswa pindah jurusan lewat halaman ini. Kalau hanya sebagian siswa yang tinggal kelas, buka daftar siswa pada baris kelasnya lalu centang satu per satu.</p>
    </div>

    <x-form-errors />

    @if ($alreadyRun)
        <div role="status" class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-xl">
            <p class="font-semibold">Kenaikan kelas tahun ajaran {{ $lastRun }} sudah pernah dijalankan.</p>
            <p class="mt-1">Jalankan lagi hanya kalau proses sebelumnya salah kelas. Anggota dan penabung yang sudah naik akan naik sekali lagi, jadi periksa dulu daftar kelas di bawah.</p>
        </div>
    @endif

    @php
        $finalYearParts = array_filter([
            $finalYear['members'] > 0 ? $finalYear['members'].' anggota' : null,
            $finalYear['savers'] > 0 ? $finalYear['savers'].' penabung' : null,
        ]);
    @endphp

    @if ($finalYearParts !== [])
        <div class="mb-4 p-4 bg-white border border-stone-200 rounded-2xl text-sm text-stone-700 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 sm:justify-between">
            <p>
                {{ implode(' dan ', $finalYearParts) }} kelas XII tidak ikut dinaikkan.
                Kelas XII diselesaikan lewat halaman Kelulusan.
            </p>
            <a href="{{ route('admin.graduations.index') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline shrink-0">Buka Kelulusan</a>
        </div>
    @endif

    @if ($groups->isEmpty())
        <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
            Belum ada data anggota atau penabung.
            Tambahkan siswa dulu lewat <a href="{{ route('admin.members.index') }}" class="font-medium text-brand-900 hover:underline">Kelola Anggota</a>,
            lalu isi kelasnya sebelum kenaikan kelas dijalankan.
        </div>
    @else
        @if ($ready->isNotEmpty())
            <form method="POST" action="{{ route('admin.class-promotions.store') }}" data-confirm="Proses kenaikan kelas sekarang? Periksa dulu tindakan tiap kelas.">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
        @endif

        <x-data-table caption="Kelas anggota dan penabung beserta tindakannya pada kenaikan kelas" :headers="[
            ['label' => 'Kelas sekarang'],
            ['label' => 'Anggota', 'align' => 'right'],
            ['label' => 'Penabung', 'align' => 'right'],
            ['label' => 'Diblokir', 'align' => 'right'],
            ['label' => 'Tindakan'],
        ]">
            @foreach ($groups as $group)
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3">
                        <div class="font-medium text-stone-900">{{ $group['class'] }}</div>
                        @if ($group['kind'] === 'unknown')
                            <div class="text-xs text-stone-600">Tingkat tidak dikenali.</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right tnum">{{ $group['members'] > 0 ? $group['members'] : '-' }}</td>
                    <td class="px-5 py-3 text-right tnum">{{ $group['savers'] > 0 ? $group['savers'] : '-' }}</td>
                    <td class="px-5 py-3 text-right tnum">{{ $group['blocked'] > 0 ? $group['blocked'] : '-' }}</td>

                    @if ($group['kind'] === 'final')
                        <td class="px-5 py-3 text-xs text-stone-600">
                            Tidak dinaikkan. Arsipkan angkatan ini di halaman Kelulusan.
                        </td>
                    @elseif ($group['kind'] === 'ready')
                        @php
                            $rowIndex = $loop->index;
                            $classStudents = $students[$group['class']] ?? [];
                            $retainedOld = old('rows.'.$rowIndex.'.retain', []);
                        @endphp
                        <td class="px-5 py-3">
                            <input type="hidden" name="rows[{{ $rowIndex }}][class]" value="{{ $group['class'] }}">
                            <div class="w-full sm:w-64">
                                <x-select name="rows[{{ $rowIndex }}][action]" aria-label="Tindakan untuk kelas {{ $group['class'] }}" data-promotion-action="1" data-promotion-retain-value="{{ \App\Services\ClassPromotionService::ACTION_RETAIN }}">
                                    <option value="{{ \App\Services\ClassPromotionService::ACTION_PROMOTE }}" @selected(old('rows.'.$rowIndex.'.action', \App\Services\ClassPromotionService::ACTION_PROMOTE) === \App\Services\ClassPromotionService::ACTION_PROMOTE)>Naik ke {{ $group['target'] }}</option>
                                    <option value="{{ \App\Services\ClassPromotionService::ACTION_RETAIN }}" @selected(old('rows.'.$rowIndex.'.action') === \App\Services\ClassPromotionService::ACTION_RETAIN)>Tinggal kelas semua</option>
                                </x-select>
                            </div>

                            @if ($classStudents !== [])
                                <div class="w-full sm:w-64 mt-2" data-promotion-picker="1">
                                    <details class="border border-stone-200 rounded-xl bg-white overflow-hidden">
                                        {{-- Alasan: display summary jangan diubah, penanda buka-tutup bawaan
                                             browser hanya muncul pada summary list-item. --}}
                                        <summary class="min-h-[44px] px-3 py-3 text-[13px] text-brand-900 cursor-pointer select-none">
                                            <span class="font-semibold">Pilih siswa tinggal kelas</span>
                                            <span class="tnum text-stone-600 font-normal">({{ count($classStudents) }} siswa)</span>
                                        </summary>
                                        <p class="px-3 pb-2 text-xs text-stone-600">Centang siswa yang tetap di {{ $group['class'] }}. Tanpa centang, seluruh {{ count($classStudents) }} siswa naik ke {{ $group['target'] }}.</p>
                                        <ul class="max-h-64 overflow-y-auto border-t border-stone-200 divide-y divide-stone-100 px-3">
                                            @foreach ($classStudents as $student)
                                                <li>
                                                    <label for="retain-{{ $rowIndex }}-{{ $loop->index }}" class="flex items-start gap-3 py-2 min-h-[44px] cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            id="retain-{{ $rowIndex }}-{{ $loop->index }}"
                                                            name="rows[{{ $rowIndex }}][retain][]"
                                                            value="{{ $student['key'] }}"
                                                            @checked(in_array($student['key'], $retainedOld, true))
                                                            class="mt-1 w-4 h-4 shrink-0 rounded border-stone-400 text-brand-800 focus:ring-brand-800"
                                                        >
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block text-sm text-stone-900">{{ $student['name'] }}</span>
                                                            <span class="block text-xs text-stone-600"><span class="font-mono">{{ $student['number'] }}</span> · {{ $student['type'] }}</span>
                                                        </span>
                                                        @if ($student['blocked'])
                                                            <span class="mt-0.5 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-900 shrink-0">Diblokir</span>
                                                        @endif
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </div>
                            @endif
                        </td>
                    @else
                        <td class="px-5 py-3 text-xs text-stone-600">
                            Tidak bisa diproses otomatis. Perbaiki nama kelasnya lewat
                            <a href="{{ route('admin.members.index') }}" class="font-medium text-brand-900 hover:underline">Kelola Anggota</a>
                            atau
                            <a href="{{ route('admin.savers.index') }}" class="font-medium text-brand-900 hover:underline">Kelola Penabung</a>.
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-data-table>

        @if ($ready->isNotEmpty())
            <div class="mt-4 flex flex-col sm:flex-row sm:items-start gap-3 sm:justify-between">
                @php
                    $readyParts = array_filter([
                        $readyMembers > 0 ? $readyMembers.' anggota' : null,
                        $readySavers > 0 ? $readySavers.' penabung' : null,
                    ]);
                @endphp
                <p class="text-[13px] text-stone-600 sm:flex-1">
                    {{ implode(' dan ', $readyParts) }} siap diproses.
                    Penabung yang diblokir ikut naik kelas dan tetap diblokir.
                    Pilih Tinggal kelas semua untuk satu kelas utuh; untuk siswa tertentu buka Pilih siswa tinggal kelas pada baris kelasnya.
                </p>
                <x-primary-button class="shrink-0" data-loading-text="Memproses kenaikan...">Proses kenaikan kelas</x-primary-button>
            </div>

            @if ($alreadyRun)
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-900">
                    <label class="flex items-start gap-2.5 min-h-[44px] cursor-pointer">
                        <input type="checkbox" name="confirm_repeat" value="1" @checked(old('confirm_repeat')) class="mt-0.5 w-4 h-4 rounded border-stone-400 text-brand-800 focus:ring-brand-800">
                        <span>Ya, jalankan lagi untuk tahun ajaran {{ $academicYear }}. Saya sudah memeriksa tindakan tiap kelas.</span>
                    </label>
                </div>
            @endif
        </form>
        @endif
    @endif

    @if ($withoutClass['members']->isNotEmpty() || $withoutClass['savers']->isNotEmpty())
        <div class="mt-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-stone-900">Anggota dan penabung yang kelasnya belum diisi</h2>
            <p class="text-[13px] text-stone-600 mt-1">Kelas kosong tidak bisa dinaikkan. Perbaiki lewat halaman masing-masing.</p>
            <ul class="mt-3 space-y-1 text-sm text-stone-700">
                @foreach ($withoutClass['members'] as $member)
                    <li class="flex flex-wrap items-baseline gap-x-2">
                        <a href="{{ route('admin.members.edit', $member) }}" class="font-medium text-brand-900 hover:underline">{{ $member->full_name }}</a>
                        <span class="font-mono text-xs text-stone-600">{{ $member->nis }}</span>
                        <span class="text-xs text-stone-500">Anggota</span>
                    </li>
                @endforeach
                @foreach ($withoutClass['savers'] as $saver)
                    <li class="flex flex-wrap items-baseline gap-x-2">
                        <a href="{{ route('admin.savers.edit', $saver) }}" class="font-medium text-brand-900 hover:underline">{{ $saver->full_name }}</a>
                        <span class="font-mono text-xs text-stone-600">{{ $saver->student_number }}</span>
                        <span class="text-xs text-stone-500">Penabung</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
