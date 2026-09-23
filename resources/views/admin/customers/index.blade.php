@extends('layouts.app')

@section('title', 'Daftar Nasabah — Bank Mini')
@section('section', 'Administrator · Nasabah')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-stone-900">Daftar Nasabah</h1>
            <p class="text-sm text-stone-500 mt-1">Profil siswa dan rekening tabungan.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.customers.import.form') }}" class="bg-white border border-stone-300 text-stone-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-stone-50 transition-colors">
                Import CSV
            </a>
            <a href="{{ route('admin.customers.create') }}" class="bg-brand-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-brand-900 transition-colors">
                + Tambah Nasabah
            </a>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, no. siswa, atau kelas..."
            class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 flex-1 min-w-[200px] focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
        <select name="status" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Diblokir</option>
        </select>
        <button type="submit" class="bg-brand-800 text-white text-sm font-medium px-4 py-1.5 rounded-lg hover:bg-brand-900 transition-colors">Filter</button>
        @if (request('search') || request('status'))
            <a href="{{ route('admin.customers.index') }}" class="text-sm border border-stone-300 rounded-lg px-3 py-1.5 hover:bg-stone-50">Reset</a>
        @endif
    </form>

    <x-data-table :headers="[['label' => 'No. Siswa'], ['label' => 'Nama'], ['label' => 'Kelas'], ['label' => 'Email'], ['label' => 'No. Rekening'], ['label' => 'Saldo', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Aksi', 'align' => 'right']]">
                @forelse ($customers as $customer)
                    <tr class="hover:bg-stone-50">
                        <td class="px-5 py-3 font-mono text-xs">{{ $customer->student_number }}</td>
                        <td class="px-5 py-3">{{ $customer->full_name }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $customer->class_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $customer->email ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs">
                            @if ($customer->account)
                                <a href="{{ route('admin.accounts.show', $customer->account) }}" class="text-brand-900 hover:underline">{{ $customer->account->account_number }}</a>
                            @else
                                <span class="text-stone-500">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-mono">
                            Rp {{ number_format($customer->account->balance ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $customer->status === 'active' ? 'bg-olive-100 text-olive-700' : 'bg-red-100 text-red-700' }}">
                                {{ $customer->status === 'active' ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($customer->account)
                                    <a href="{{ route('admin.accounts.card-print', $customer->account) }}" class="text-brand-700 hover:text-brand-800 text-xs">Kartu</a>
                                @endif
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="text-brand-900 hover:text-brand-800 text-xs">Ubah</a>
                                <form method="POST" action="{{ route('admin.customers.toggle-status', $customer) }}" class="inline" data-confirm="{{ $customer->status === 'active' ? 'Blokir nasabah ini?' : 'Aktifkan kembali nasabah ini?' }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs {{ $customer->status === 'active' ? 'text-amber-700 hover:text-amber-900' : 'text-brand-800 hover:text-olive-800' }}">
                                        {{ $customer->status === 'active' ? 'Blokir' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-stone-500">
                        @if (request('search'))
                            Tidak ada nasabah yang cocok dengan pencarian "{{ request('search') }}".
                        @else
                            Belum ada data nasabah.
                        @endif
                    </td></tr>
                @endforelse
</x-data-table>

    <div class="mt-4">{{ $customers->withQueryString()->links() }}</div>
@endsection
