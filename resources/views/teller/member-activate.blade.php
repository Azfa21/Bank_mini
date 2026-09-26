@extends('layouts.app')

@section('title', 'Aktivasi Rekening Anggota — Bank Mini')
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

                <form method="POST" action="{{ route('teller.members.activate.store', $member) }}" class="mt-4 space-y-5" id="activation-form">
                    @csrf

                    <div>
                        <x-input-label for="amount">Nominal setoran awal (Rp) wajib</x-input-label>
                        <x-input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric" :value="old('amount')" placeholder="Minimal sesuai ketentuan" required />
                        <p class="text-[13px] text-stone-600 mt-1.5">Setoran harus mencapai batas minimal aktivasi.</p>
                        <x-input-error :message="$errors->first('amount')" />
                    </div>

                    <details class="border-t border-stone-100 pt-5">
                        <summary class="inline-flex items-center min-h-[44px] cursor-pointer text-[15px] font-bold text-stone-900">
                            Bantu hitung uang fisik per pecahan (opsional)
                        </summary>
                        <div class="mt-3">
                            <x-denomination-input />
                            <p role="status" data-denomination-sum class="text-[13px] font-medium text-stone-700 mt-2">Subtotal rincian: Rp 0</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" data-denomination-apply class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-semibold text-brand-900 border border-brand-800/30 bg-brand-50 rounded-xl hover:bg-brand-100 disabled:opacity-50">
                                    Pakai total ini
                                </button>
                                <button type="button" data-denomination-reset class="inline-flex items-center justify-center min-h-[44px] px-4 text-sm font-medium text-stone-600 hover:text-stone-900">
                                    Ulangi
                                </button>
                            </div>
                        </div>
                    </details>

                    <div>
                        <x-input-label for="physical_cash_amount">Total uang fisik diterima (Rp) wajib</x-input-label>
                        <x-input id="physical_cash_amount" name="physical_cash_amount" type="number" min="1" step="1" inputmode="numeric" :value="old('physical_cash_amount')" placeholder="Hasil hitung uang di tangan" required data-denomination-total />
                        <x-input-error :message="$errors->first('physical_cash_amount')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-stone-100 pt-5">
                        <div>
                            <x-input-label for="class_name">Kelas (opsional)</x-input-label>
                            <x-input id="class_name" name="class_name" :value="old('class_name')" placeholder="Contoh: VII A" />
                            <x-input-error :message="$errors->first('class_name')" />
                        </div>
                        <div>
                            <x-input-label for="phone_number">Nomor HP (opsional)</x-input-label>
                            <x-input id="phone_number" name="phone_number" :value="old('phone_number')" inputmode="tel" />
                            <x-input-error :message="$errors->first('phone_number')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email">Email (opsional)</x-input-label>
                        <x-input id="email" name="email" type="email" :value="old('email')" placeholder="Untuk verifikasi jika lupa PIN" />
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
@endsection
