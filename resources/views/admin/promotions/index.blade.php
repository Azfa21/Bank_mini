@extends('layouts.app')

@section('title', 'Kenaikan Kelas | Bank Mini')
@section('section', 'Administrator · Kenaikan Kelas')

@section('content')
    <div class="mb-5">
        <p class="text-xs font-semibold text-brand-900">AKHIR TAHUN AJARAN</p>
        <h1 class="text-2xl font-bold text-stone-900 mt-1">Kenaikan kelas</h1>
        <p class="text-sm text-stone-600 mt-1">Naikkan tingkat penabung sekali setahun: X menjadi XI, XI menjadi XII. Jurusan dan nomor rombel mengikuti kelas asal dan bisa disesuaikan sebelum diproses.</p>
    </div>

    <x-form-errors />

    @if ($alreadyRun)
        <div role="status" class="mb-4 p-4 bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-xl">
            <p class="font-semibold">Kenaikan kelas tahun ajaran {{ $lastRun }} sudah pernah dijalankan.</p>
            <p class="mt-1">Jalankan lagi hanya kalau proses sebelumnya salah kelas. Penabung yang sudah naik akan naik sekali lagi, jadi periksa dulu daftar kelas di bawah.</p>
        </div>
    @endif

    @if ($finalYearTotal > 0)
        <div class="mb-4 p-4 bg-white border border-stone-200 rounded-2xl text-sm text-stone-700 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 sm:justify-between">
            <p>
                <span class="tnum font-semibold text-stone-900">{{ $finalYearTotal }}</span> penabung kelas XII tidak ikut dinaikkan.
                Kelas XII diselesaikan lewat halaman Kelulusan.
            </p>
            <a href="{{ route('admin.graduations.index') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline shrink-0">Buka Kelulusan</a>
        </div>
    @endif

    @php
        $workable = $groups->whereIn('kind', ['ready', 'unknown']);
        $promotableCount = (int) $workable->sum('total');
    @endphp

    @if ($workable->isEmpty())
        <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
            Belum ada kelas yang bisa dinaikkan tingkatnya. Pastikan kelas penabung sudah diisi dengan format tingkat, misalnya "X RPL 1", lewat halaman Kelola Penabung atau aktivasi di loket.
        </div>
    @else
        <form method="POST" action="{{ route('admin.class-promotions.store') }}" data-confirm="Proses kenaikan kelas sekarang? Pastikan kelas tujuan tiap kelas sudah benar.">
            @csrf
            <input type="hidden" name="academic_year" value="{{ $academicYear }}">

            <x-data-table caption="Kelas penabung dan kelas tujuan setelah naik tingkat" :headers="[
                ['label' => 'Kelas sekarang'],
                ['label' => 'Penabung', 'align' => 'right'],
                ['label' => 'Diblokir', 'align' => 'right'],
                ['label' => 'Tingkat tujuan'],
                ['label' => 'Jurusan tujuan'],
                ['label' => 'Rombel tujuan'],
            ]">
                @foreach ($groups as $group)
                    @continue ($group['kind'] === 'empty')

                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3">
                            <div class="font-medium text-stone-900">{{ $group['class'] }}</div>
                            @if ($group['kind'] === 'unknown')
                                <div class="text-xs text-stone-600">Tingkat tidak dikenali, pilih kelas tujuan dulu.</div>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right tnum">{{ $group['total'] }}</td>
                        <td class="px-5 py-3 text-right tnum">{{ $group['blocked'] > 0 ? $group['blocked'] : '-' }}</td>

                        @if ($group['kind'] === 'final')
                            <td colspan="3" class="px-5 py-3 text-xs text-stone-600">
                                Tidak dinaikkan. Arsipkan angkatan ini di halaman Kelulusan.
                            </td>
                        @else
                            <td class="px-5 py-3">
                                <div class="w-28">
                                    <x-select name="rows[{{ $loop->index }}][level]" aria-label="Tingkat tujuan untuk kelas {{ $group['class'] }}">
                                        <option value="">Pilih</option>
                                        @foreach ($levels as $level)
                                            <option value="{{ $level }}" @selected(old('rows.'.$loop->index.'.level', $group['next_level']) === $level)>{{ $level }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="w-36">
                                    <x-select name="rows[{{ $loop->index }}][major]" aria-label="Jurusan tujuan untuk kelas {{ $group['class'] }}">
                                        <option value="">Pilih</option>
                                        @foreach ($majors as $major)
                                            <option value="{{ $major }}" @selected(old('rows.'.$loop->index.'.major', $group['major']) === $major)>{{ $major }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                                <input type="hidden" name="rows[{{ $loop->index }}][class]" value="{{ $group['class'] }}">
                            </td>
                            <td class="px-5 py-3">
                                <div class="w-24">
                                    <x-input name="rows[{{ $loop->index }}][rombel]" :value="old('rows.'.$loop->index.'.rombel', $group['rombel'])" inputmode="numeric" maxlength="2" placeholder="1" aria-label="Nomor rombel tujuan untuk kelas {{ $group['class'] }}" />
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-data-table>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
                <p class="text-[13px] text-stone-600">
                    Penabung diblokir ikut naik kelas, blokirnya tetap berlaku. Baris yang tingkat atau jurusannya kosong tidak diproses. Siswa yang tinggal kelas cukup dibiarkan: baris dengan kelas tujuan sama dengan kelas asal otomatis dilewati.
                </p>
                <x-primary-button data-loading-text="Memproses kenaikan...">Naikkan {{ $promotableCount }} penabung</x-primary-button>
            </div>

            @if ($alreadyRun)
                <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-900">
                    <label class="flex items-start gap-2.5 min-h-[44px] cursor-pointer">
                        <input type="checkbox" name="confirm_repeat" value="1" @checked(old('confirm_repeat')) class="mt-0.5 w-4 h-4 rounded border-stone-400 text-brand-800 focus:ring-brand-800">
                        <span>Ya, jalankan lagi untuk tahun ajaran {{ $academicYear }}. Saya sudah memeriksa kelas tujuan tiap kelas.</span>
                    </label>
                </div>
            @endif
        </form>
    @endif

    @if ($withoutClass->isNotEmpty())
        <div class="mt-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5">
            <h2 class="text-sm font-semibold text-stone-900">Penabung yang kelasnya belum diisi</h2>
            <p class="text-[13px] text-stone-600 mt-1">Kelas kosong tidak bisa dinaikkan. Lengkapi lewat halaman Kelola Penabung.</p>
            <ul class="mt-3 space-y-1 text-sm text-stone-700">
                @foreach ($withoutClass as $saver)
                    <li class="flex flex-wrap items-baseline gap-x-2">
                        <a href="{{ route('admin.savers.edit', $saver) }}" class="font-medium text-brand-900 hover:underline">{{ $saver->full_name }}</a>
                        <span class="font-mono text-xs text-stone-600">{{ $saver->student_number }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-8" aria-labelledby="sesuaikan-rombel">
        <div class="mb-4">
            <h2 id="sesuaikan-rombel" class="text-lg font-bold text-stone-900">Sesuaikan rombel</h2>
            <p class="text-sm text-stone-600 mt-1">Pakai bagian ini kalau jumlah rombel berubah (misalnya dua kelas dibagi menjadi tiga) atau ada penabung pindah rombel. Bisa dipakai kapan saja, tidak menunggu akhir tahun.</p>
        </div>

        <form method="GET" action="{{ route('admin.class-promotions.index') }}" class="mb-4 bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
            <div class="sm:w-52">
                <x-input-label for="class">Kelas sekarang</x-input-label>
                <x-select id="class" name="class">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $option)
                        <option value="{{ $option }}" @selected(request('class') === $option)>{{ $option }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="sm:w-64">
                <x-input-label for="search">Cari penabung</x-input-label>
                <x-input id="search" name="search" type="search" :value="request('search')" placeholder="Nama atau nomor siswa" />
            </div>
            <x-primary-button>Tampilkan</x-primary-button>
            @if (request('class') || request('search'))
                <a href="{{ route('admin.class-promotions.index') }}" class="inline-flex items-center justify-center min-h-[48px] px-4 text-sm font-medium text-stone-700 border border-stone-300 rounded-xl hover:bg-stone-100 transition-colors">Reset</a>
            @endif
        </form>

        @if ($savers->isEmpty())
            <div class="bg-white rounded-2xl border border-stone-200 p-6 text-sm text-stone-600">
                @if (request('class') || request('search'))
                    Tidak ada penabung yang cocok dengan filter ini.
                @else
                    Belum ada penabung aktif atau diblokir yang bisa dipindahkan.
                @endif
            </div>
        @else
            <form method="POST" action="{{ route('admin.class-promotions.move') }}" data-confirm="Pindahkan penabung terpilih ke kelas tujuan?">
                @csrf

                <div class="bg-white rounded-2xl border border-stone-200 p-4 sm:p-5 mb-4">
                    <label class="flex items-center gap-2 min-h-[44px] text-sm text-stone-700 cursor-pointer">
                        <input type="checkbox" data-select-all class="w-4 h-4 rounded border-stone-400 text-brand-800 focus:ring-brand-800">
                        Pilih semua di halaman ini
                    </label>

                    <div class="mt-2 pt-4 border-t border-stone-100 flex flex-col sm:flex-row gap-3 sm:items-end sm:flex-wrap">
                        <div class="sm:w-28">
                            <x-input-label for="move-level">Tingkat tujuan</x-input-label>
                            <x-select id="move-level" name="level">
                                <option value="">Pilih</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level }}" @selected(old('level', $moveDefaults['level']) === $level)>{{ $level }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="sm:w-32">
                            <x-input-label for="move-major">Jurusan tujuan</x-input-label>
                            <x-select id="move-major" name="major">
                                <option value="">Pilih</option>
                                @foreach ($majors as $major)
                                    <option value="{{ $major }}" @selected(old('major', $moveDefaults['major']) === $major)>{{ $major }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="sm:w-24">
                            <x-input-label for="move-rombel">Rombel</x-input-label>
                            <x-input id="move-rombel" name="rombel" :value="old('rombel', $moveDefaults['rombel'])" inputmode="numeric" maxlength="2" placeholder="1" />
                        </div>
                        <x-primary-button data-select-submit data-loading-text="Memindahkan...">Pindahkan <span data-select-count class="tnum">0</span> penabung</x-primary-button>
                    </div>
                </div>

                <x-data-table caption="Daftar penabung yang bisa dipindah ke rombel lain" :headers="[
                    ['label' => 'Pilih'],
                    ['label' => 'No. siswa'],
                    ['label' => 'Nama'],
                    ['label' => 'Kelas sekarang'],
                    ['label' => 'Status'],
                ]">
                    @foreach ($savers as $saver)
                        @php
                            $statusStyles = [
                                'active' => 'bg-olive-100 text-olive-700',
                                'blocked' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <tr class="hover:bg-stone-50">
                            <td class="px-5 py-3">
                                <label class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] cursor-pointer">
                                    <input type="checkbox" name="saver_ids[]" value="{{ $saver->id }}" data-select-item
                                        class="w-4 h-4 rounded border-stone-400 text-brand-800 focus:ring-brand-800"
                                        aria-label="Pilih {{ $saver->full_name }}">
                                </label>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs">{{ $saver->student_number }}</td>
                            <td class="px-5 py-3">{{ $saver->full_name }}</td>
                            <td class="px-5 py-3 {{ $saver->class_name ? 'text-stone-700' : 'text-stone-500' }}">{{ $saver->class_name ?: 'Belum diisi' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$saver->status] ?? 'bg-stone-100 text-stone-700' }}">
                                    {{ $saver->statusLabel() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
            </form>

            <div class="mt-4">{{ $savers->links() }}</div>
        @endif
    </section>
@endsection
