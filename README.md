# Sistem Informasi E-Teller Bank Mini Sekolah

Aplikasi web (Laravel, Blade + Tailwind CSS v4) untuk operasional tabungan siswa:
setoran, penarikan, transfer antar rekening, penutupan kas harian, jurnal akuntansi,
dan laporan keuangan. Co-located application di `mobile/` adalah aplikasi nasabah.

## Persyaratan Sistem
- PHP >= 8.3 (dengan ekstensi `pdo_mysql`, `pdo_sqlite`, `bcmath`, `mbstring`)
- Composer
- Node.js & NPM
- Flutter (hanya untuk aplikasi nasabah di `mobile/`)

## Instalasi
```bash
# Masuk ke folder project
cd Bank_Mini

# Install dependensi backend
composer install

# Salin environment file dan generate app key
cp .env.example .env
php artisan key:generate

# Jalankan migrasi database (isi DB_CONNECTION/DB_* pada .env terlebih dahulu)
php artisan migrate

# Install dependensi frontend dan kompilasi aset
npm install
npm run build
```

## Menjalankan Server
```bash
# Web + antrean, log request, dan watcher aset dalam satu perintah (development)
composer dev

# Atau web saja
php artisan serve

# Atau watcher aset saat mengubah frontend
npm run dev
```

### Menjalankan Aplikasi Mobile (Nasabah)
```bash
cd mobile
flutter pub get
flutter analyze   # analisis statis
flutter test      # test aplikasi
# Jalankan di emulator/perangkat. Alamat API diatur lewat --dart-define,
# default untuk emulator Android mengarah ke http://10.0.2.2:8000/api
flutter run --dart-define=API_BASE_URL=http://<host>:<port>/api
```

### Tautan APK di Halaman Awal
Halaman awal menampilkan tombol unduh aplikasi nasabah hanya jika tautannya tersedia.
Letakkan berkas APK di `public/unduhan/bank-mini-nasabah.apk`, atau isi
`BANKMINI_APK_URL` di `.env` dengan URL unduhan eksternal. Bila keduanya kosong,
halaman menampilkan keterangan bahwa APK dapat diminta ke petugas loket, bukan tombol mati.

### Perintah Artisan Khusus Bank Mini
```bash
# Menyiapkan arsip satu angkatan siswa untuk kelulusan (ada konfirmasi; --dry-run hanya melihat rencana)
php artisan bankmini:graduate --year=<Tahun> [--dry-run] [--operator=<username>]
```

## Membersihkan Data Testing (Peluncuran)
```bash
# Hapus permanen seluruh data hasil testing: member, penabung, rekening, transaksi, jurnal,
# laporan kas, permintaan penutupan rekening, OTP lupa password, token aplikasi mobile, audit log,
# sesi login, cache, dan antrean. Akun staff (halaman Pengguna, beserta password) dan
# konfigurasi sistem tetap utuh.
#
# Perintah ini sengaja melanggar aturan aplikasi bahwa anggota yang sudah menabung tidak dapat
# dihapus permanen, sehingga data yang terhapus TIDAK dapat dikembalikan. Semua sesi login
# berakhir; nomor dokumen (NIS, no. rekening, no. bukti) mulai dari awal kembali.
#
# Lihat dulu apa yang akan dihapus, tanpa menghapus apa pun (boleh di production):
php artisan bankmini:reset-nasabah --dry-run

# Non-production: prompt konfirmasi seluruh tabel, atau --force untuk skrip otomatis
php artisan bankmini:reset-nasabah
php artisan bankmini:reset-nasabah --force

# Catat nama operator pada entri audit log (opsional)
php artisan bankmini:reset-nasabah --operator="Nama Operator"

# Production: perintah reset ini ditolak, tanpa flag yang bisa melewatkannya.
# Bersih-bersih data testing wajib dilakukan saat environment masih lokal atau
# staging, sebelum APP_ENV diubah menjadi production. Karena ini bank, setelah
# peluncuran tidak ada jalur penghapusan data nasabah lewat perintah ini.
```

## Menjalankan Pengujian (Testing)
```bash
# Jalankan seluruh unit & feature tests
php artisan test

# Atau pemeriksaan kualitas lainnya
composer lint      # pint (check)
composer analyse   # phpstan
composer ci        # lint + analyse + test
```
