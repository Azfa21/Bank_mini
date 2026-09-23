@extends('layouts.app')

@section('title', 'Import Nasabah — Bank Mini')
@section('section', 'Administrator · Nasabah · Import')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-xl font-semibold text-stone-900 mb-1">Import Nasabah dari CSV</h1>
        <p class="text-sm text-stone-500 mb-6">Unggah daftar siswa untuk membuat nasabah dan rekening sekaligus.</p>

        <x-form-errors />

        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
        @endif

        @php($summary = session('import_summary'))

        @if ($summary)
            <div class="mb-6 bg-white rounded-xl border border-stone-200 p-5">
                <h2 class="text-sm font-semibold text-stone-900 mb-3">Hasil Import</h2>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="bg-olive-50 border border-olive-200 rounded-lg p-3">
                        <div class="text-xl font-semibold text-olive-700">{{ $summary['imported'] }}</div>
                        <div class="text-xs text-brand-800">Berhasil</div>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <div class="text-xl font-semibold text-amber-800">{{ $summary['skipped'] }}</div>
                        <div class="text-xs text-amber-700">Dilewati</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="text-xl font-semibold text-red-700">{{ $summary['failed'] }}</div>
                        <div class="text-xs text-red-600">Gagal</div>
                    </div>
                </div>

                @if (count($summary['issues']) > 0)
                    <div class="mt-4">
                        <h3 class="text-xs font-medium text-stone-500 uppercase mb-2">Rincian Masalah</h3>
                        <ul class="text-sm text-stone-700 space-y-1 max-h-64 overflow-y-auto">
                            @foreach ($summary['issues'] as $issue)
                                <li class="flex gap-2">
                                    <span class="text-stone-500 font-mono text-xs shrink-0 w-10">{{ $issue['row'] ? 'Baris '.$issue['row'] : '—' }}</span>
                                    <span>{{ $issue['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('admin.customers.import') }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-stone-200 p-6 space-y-4">
            @csrf
            <div>
                <x-input-label for="file">File CSV Daftar Siswa</x-input-label>
                <input id="file" type="file" name="file" accept=".csv,text/csv"
                    class="mt-1 block w-full text-sm border border-stone-300 rounded-lg file:mr-3 file:border-0 file:bg-brand-800 file:text-white file:text-sm file:px-3 file:py-1.5 file:rounded-md focus:outline-none focus:ring-2 focus:ring-brand-600" />
                @error('file')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <x-primary-button>Import</x-primary-button>
                <a href="{{ route('admin.customers.import.template') }}"
                    class="inline-flex items-center border border-stone-300 bg-white text-stone-700 text-sm px-4 py-2 rounded-lg hover:bg-stone-50 transition-colors">Unduh Template CSV</a>
                <x-secondary-button :href="route('admin.customers.index')">Kembali</x-secondary-button>
            </div>
        </form>

        <div class="mt-6 bg-white rounded-xl border border-stone-200 p-6">
            <h2 class="text-sm font-semibold text-stone-900 mb-3">Format File</h2>
            <ul class="text-sm text-stone-600 space-y-1.5 list-disc list-inside">
                <li>Baris pertama berisi header kolom: <code class="font-mono text-xs bg-stone-100 px-1 rounded">nis</code>, <code class="font-mono text-xs bg-stone-100 px-1 rounded">nama</code>, <code class="font-mono text-xs bg-stone-100 px-1 rounded">kelas</code>, <code class="font-mono text-xs bg-stone-100 px-1 rounded">no_hp</code>, <code class="font-mono text-xs bg-stone-100 px-1 rounded">email</code></li>
                <li>Kolom <strong>nis</strong>, <strong>nama</strong>, dan <strong>email</strong> wajib diisi; kelas dan no. HP opsional</li>
                <li>Pemisah kolom koma atau titik-koma didukung otomatis</li>
                <li>NIS atau email yang sudah terdaftar akan dilewati tanpa mengubah data lama</li>
                <li>PIN awal setiap nasabah baru adalah <strong>123456</strong></li>
                <li>Setiap nasabah baru otomatis mendapat rekening tabungan</li>
            </ul>
        </div>
    </div>
@endsection
