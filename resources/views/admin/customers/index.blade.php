@extends('layouts.app')

@section('title', 'Daftar Nasabah — Bank Mini')
@section('section', 'Administrator · Nasabah')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Daftar Nasabah</h1>
            <p class="text-sm text-gray-500 mt-1">Profil siswa dan rekening tabungan.</p>
        </div>
        <a href="{{ route('admin.customers.create') }}" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            + Tambah Nasabah
        </a>
    </div>

    <form method="GET" class="flex gap-2 mb-4">
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
        </select>
        <button type="submit" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">No. Siswa</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nama</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Kelas</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">No. Rekening</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Saldo</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $customer->student_number }}</td>
                        <td class="px-4 py-3">{{ $customer->full_name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $customer->class_name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            @if ($customer->account)
                                <a href="{{ route('admin.accounts.show', $customer->account) }}" class="text-blue-600 hover:underline">{{ $customer->account->account_number }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono">
                            Rp {{ number_format($customer->account->balance ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $customer->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $customer->status === 'active' ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="text-blue-600 hover:text-blue-800 text-xs">Ubah</a>
                                <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Hapus nasabah ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data nasabah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->withQueryString()->links() }}</div>
@endsection
