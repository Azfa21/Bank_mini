# Sistem Informasi E-Teller Bank Mini Sekolah

## 🛠️ Panduan Menjalankan Aplikasi

### Persyaratan Sistem
- PHP >= 8.2 (dengan ekstensi `pdo_mysql`, `pdo_sqlite`, `bcmath`, `mbstring`)
- Composer
- Node.js & NPM

### Instalasi
```bash
# Clone repository dan masuk ke folder project
cd Bank_Mini

# Salin environment file dan generate app key
cp .env.example .env
php artisan key:generate

# Jalankan migrasi database
php artisan migrate

# Install dependensi frontend & backend
composer install
npm install
npm run build
```

### Menjalankan Server
```bash
php artisan serve
```

### Membersihkan Data Testing (Peluncuran)
```bash
# Hapus permanen seluruh data hasil testing: member, penabung, rekening, transaksi, jurnal,
# laporan kas, permintaan penutupan rekening, OTP lupa PIN, token aplikasi mobile, audit log,
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

# Production (hanya untuk peluncuran): perlu --allow-production dan --operator,
# lalu ketik HAPUS saat diminta. Konfirmasi ketik ini TIDAK bisa dilewati --force.
php artisan bankmini:reset-nasabah --allow-production --force --operator="Nama Anda"

# Catat nama operator pada entri audit log (opsional, berlaku di semua environment)
php artisan bankmini:reset-nasabah --operator="Nama Operator"
```

### Menjalankan Pengujian (Testing)
```bash
# Jalankan seluruh unit & feature tests
php artisan test

# Atau jalankan feature test tertentu
php vendor/bin/phpunit tests/Feature/ForgotPasswordTest.php
php vendor/bin/phpunit tests/Feature/AuthRbacTest.php
```
