@extends('layouts.app')

@section('title', 'Hasil Pencarian Nasabah — Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Hasil Pencarian</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $results->count() }} nasabah cocok dengan kata kunci "{{ $q }}".</p>
        </div>
        <a href="{{ route('teller.customers.search') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Pencarian baru</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3">No. Rekening</th>
                    <th class="px-5 py-3">NIS</th>
                    <th class="px-5 py-3">Nama</th>
                    <th class="px-5 py-3">Kelas</th>
                    <th class="px-5 py-3 text-right">Saldo</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($results as $account)
                    <tr>
                        <td class="px-5 py-3 font-mono">{{ $account->account_number }}</td>
                        <td class="px-5 py-3 font-mono">{{ $account->customer->student_number }}</td>
                        <td class="px-5 py-3">{{ $account->customer->full_name }}</td>
                        <td class="px-5 py-3">{{ $account->customer->class_name }}</td>
                        <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($account->balance, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('teller.customers.show', $account) }}" class="text-blue-600 hover:text-blue-800 font-medium">Pilih</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
