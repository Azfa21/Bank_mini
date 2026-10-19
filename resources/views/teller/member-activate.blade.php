@extends('layouts.app')

@section('title', 'Aktivasi Rekening Anggota | Bank Mini')
@section('section', 'Piket Teller')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-900">AKTIVASI REKENING</p>
            <h1 class="text-2xl font-bold text-stone-900 mt-1">Setoran awal anggota</h1>
            <p class="text-sm text-stone-600 mt-1">Setoran memenuhi batas minimal akan membuat rekening dan memindahkan anggota menjadi penabung.</p>
        </div>
        <a href="{{ route('teller.members.search') }}" class="inline-flex items-center min-h-[44px] text-sm font-semibold text-brand-900 hover:underline">&larr; Kembali ke pencarian</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <aside aria-label="Anggota yang diaktivasi" class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-4">
                <p class="text-[13px] font-medium text-stone-600">Anggota</p>
                <p class="text-base font-bold text-stone-900 mt-1">{{ $member->full_name }}</p>
                <p class="text-[13px] text-stone-600 mt-2">NIS: <span class="tnum font-medium text-stone-800">{{ $member->nis }}</span></p>
                <p class="text-[13px] text-stone-600 mt-1">Angkatan keluar: {{ $member->graduation_year ?? '-' }}</p>
                <p class="text-[13px] text-stone-600 mt-1">Status: <span class="font-semibold text-amber-800">Rekening tidak aktif</span></p>
            </div>
        </aside>

        <section aria-label="Formulir aktivasi" class="lg:col-span-2">
            <div class="bg-white rounded-2xl border border-stone-200 ledger-rule p-5 sm:p-6">
                <h2 class="text-base font-bold text-stone-900">Data aktivasi & setoran awal</h2>

                <x-form-errors />

                <form method="POST" action="{{ route('teller.members.activate.store', $member) }}" class="mt-4 space-y-5" id="activation-form" data-confirm="Aktifkan rekening ini? Rekening dan setoran awal langsung tersimpan dan tidak bisa diubah.">
                    @csrf

                    <div>
                        <x-input-label for="amount">Nominal setoran awal (Rp) wajib</x-input-label>
                        <x-input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric" :value="old('amount')" placeholder="Minimal sesuai ketentuan" required autofocus
                            aria-describedby="amount-help activation-preview" />
                        <p id="amount-help" class="text-[13px] text-stone-600 mt-1.5">Setoran harus mencapai batas minimal aktivasi.</p>
                        <p id="activation-preview" role="status" class="text-[13px] font-medium text-stone-800 mt-1">Terbaca: Rp 0</p>
                        <x-input-error :message="$errors->first('amount')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-stone-100 pt-5">
                        <div>
                            <x-input-label for="class_name">Kelas (opsional)</x-input-label>
                            <x-input id="class_name" name="class_name" :value="old('class_name', $member->class_name)" list="kelas-opsi" placeholder="Contoh: XI RPL 2" aria-describedby="class-name-help" />
                            <p id="class-name-help" class="text-[13px] text-stone-600 mt-1.5">
                                @if ($member->class_name)
                                    Terisi dari data anggota. Ubah kalau sudah naik kelas.
                                @else
                                    Belum ada di data anggota, isi kalau kelasnya diketahui.
                                @endif
                            </p>
                            <x-input-error :message="$errors->first('class_name')" />
                        </div>
                        <div>
                            <x-input-label for="phone_number">Nomor HP (opsional)</x-input-label>
                            <x-input id="phone_number" name="phone_number" :value="old('phone_number')" inputmode="tel" />
                            <x-input-error :message="$errors->first('phone_number')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email">Email Penabung</x-input-label>
                        <x-input id="email" name="email" type="email" :value="old('email')" required
                            aria-describedby="email-help"
                            placeholder="ani.wijaya@sekolah.sch.id" />
                        <p id="email-help" class="text-[13px] text-stone-600 mt-1.5">
                            Wajib. Dipakai untuk mengirim kode saat penabung lupa PIN. Sebaiknya alamat
                            orang tua atau wali, bukan email sekolah yang dipakai bersama.
                        </p>
                        <x-input-error :message="$errors->first('email')" />
                    </div>

                    <div>
                        <x-input-label for="pin">PIN penabung 6 digit wajib</x-input-label>
                        <x-input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" autocomplete="off" class="tracking-[0.3em] text-center" placeholder="••••••" required />
                        <p class="text-[13px] text-stone-600 mt-1.5">Minta anggota membuat sendiri dan menyimpannya.</p>
                        <x-input-error :message="$errors->first('pin')" />
                    </div>

                    <x-primary-button data-loading-text="Mengaktifkan rekening..." class="w-full">
                        Aktifkan rekening & simpan setoran
                    </x-primary-button>
                    <p class="text-[13px] text-stone-600 text-center">Rekening dan transaksi tersimpan tidak bisa diubah. Periksa dulu sebelum tekan.</p>
                </form>
            </div>
        </section>
    </div>

    <x-class-datalist />
@endsection
