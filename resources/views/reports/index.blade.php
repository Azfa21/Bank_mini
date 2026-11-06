@extends('layouts.app')

@php
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $isMonthly = $period === 'monthly';
    $title = $isMonthly
        ? 'Laporan bulanan '.$months[(int) $start->format('n')].' '.$start->format('Y')
        : 'Laporan harian Teller';
@endphp

@section('title', 'Laporan | Bank Mini')
@section('section', 'Administrator')

@section('content')
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">{{ $title }}</h1>
            <p class="text-sm text-stone-600 mt-1">
                {{ $isMonthly
                    ? 'Rekap setoran, penarikan, dan status laporan harian.'
                    : 'Dahulukan status menunggu. Periksa arus kas sebelum menyetujui.' }}
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div class="sm:w-44">
                    <x-input-label for="report-period">Periode laporan</x-input-label>
                    <x-select id="report-period" name="period">
                        <option value="daily" @selected(! $isMonthly)>Harian</option>
                        <option value="monthly" @selected($isMonthly)>Bulanan</option>
                    </x-select>
                </div>
                <x-primary-button>Tampilkan</x-primary-button>
            </form>

            @if ($isMonthly)
                <a href="{{ route('admin.reports.monthly.print', ['month' => $month]) }}" class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-white bg-brand-800 rounded-xl hover:bg-brand-900">
                    Cetak laporan
                </a>
            @endif
        </div>
    </div>

    @if ($isMonthly)
        @include('reports.monthly')
    @else
        @include('admin.daily-reports.index')
    @endif
@endsection
