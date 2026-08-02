@extends('layouts.app')

@section('title', 'Tambah Nasabah — Bank Mini')
@section('section', 'Administrator · Tambah Nasabah')

@section('content')
    <div class="max-w-lg">
        <h1 class="text-xl font-semibold text-gray-900 mb-1">Tambah Nasabah Baru</h1>
        <p class="text-sm text-gray-500 mb-6">Profil siswa akan otomatis mendapat rekening tabungan.</p>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.customers.store') }}" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Siswa</label>
                <input type="text" name="student_number" value="{{ old('student_number') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                    <input type="text" name="class_name" value="{{ old('class_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PIN (6 digit)</label>
                <input type="password" name="pin" required pattern="\d{6}" maxlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">Simpan</button>
                <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-600 border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50">Batal</a>
            </div>
        </form>
    </div>
@endsection
