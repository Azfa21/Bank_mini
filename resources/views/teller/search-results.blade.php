@extends('layouts.app')

@section('title', 'Hasil Pencarian — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">HASIL PENCARIAN</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">{{ $results->count() + $members->count() }} data cocok</h1>
            <p class="text-sm text-stone-600 mt-1">Kata kunci "{{ $q }}". Pilih baris yang datanya sama dengan buku tabungan.</p>
        </div>
        <a href="{{ route('teller.members.search') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Ubah pencarian</a>
    </div>

    @if ($results->isNotEmpty())
        <h2 class="text-sm font-bold text-stone-900 mb-2">Penabung</h2>
        <x-data-table caption="Hasil pencarian penabung untuk {{ $q }}" :headers="[['label' => 'Nomor rekening'], ['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Kelas'], ['label' => 'Saldo', 'align' => 'right'], ['label' => 'Pilih', 'align' => 'right']]">
            @foreach ($results as $account)
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3 tnum font-medium">{{ $account->account_number }}</td>
                    <td class="px-5 py-3 tnum">{{ $account->saver->student_number }}</td>
                    <td class="px-5 py-3 font-medium">{{ $account->saver->full_name }}</td>
                    <td class="px-5 py-3">{{ $account->saver->class_name }}</td>
                    <td class="px-5 py-3 text-right tnum">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('teller.members.show', $account) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">Pilih</a>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    @endif

    @if ($members->isNotEmpty())
        <h2 class="text-sm font-bold text-stone-900 mb-2 mt-6">Anggota (belum aktif)</h2>
        <x-data-table caption="Hasil pencarian anggota untuk {{ $q }}" :headers="[['label' => 'NIS'], ['label' => 'Nama'], ['label' => 'Angkatan keluar'], ['label' => 'Status'], ['label' => 'Aktivasi', 'align' => 'right']]">
            @foreach ($members as $member)
                <tr class="hover:bg-stone-50">
                    <td class="px-5 py-3 tnum font-medium">{{ $member->nis }}</td>
                    <td class="px-5 py-3 font-medium">{{ $member->full_name }}</td>
                    <td class="px-5 py-3">{{ $member->graduation_year ?? '-' }}</td>
                    <td class="px-5 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Rekening tidak aktif</span></td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('teller.members.activate', $member) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900 transition-colors">Aktivasi</a>
                    </td>
                </tr>
            @endforeach
        </x-data-table>
    @endif

    @if ($results->isEmpty() && $members->isEmpty())
        <div class="bg-white rounded-2xl border border-stone-200 p-8 text-center text-sm text-stone-600">
            Tidak ada yang cocok. Coba ejaan lain atau daftarkan sebagai anggota baru.
        </div>
    @endif
@endsection
