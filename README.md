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
# Hapus permanen seluruh data nasabah, transaksi, jurnal, laporan, dan audit log hasil testing.
# Akun staff (admin/teller/supervisor) beserta passwordnya dan konfigurasi sistem tetap utuh.
#
# Perintah ini hanya untuk membersihkan data testing SEBELUM peluncuran. Ia sengaja melanggar
# aturan aplikasi bahwa anggota yang sudah menabung tidak dapat dihapus permanen, sehingga
# histori yang terhapus TIDAK dapat dikembalikan.
# Perintah menolak berjalan saat APP_ENV=production dan menyisakan satu entri audit log.
php artisan bankmini:reset-nasabah

# Tanpa prompt konfirmasi (untuk skrip otomatisasi non-production)
php artisan bankmini:reset-nasabah --force

# Catat nama operator pada entri audit log
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
