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
# A. Untuk Testing Sehari-hari
# Jalankan di terminal proyek:

bash
# Dengan konfirmasi interaktif:
php artisan bankmini:reset-nasabah
# Atau langsung eksekusi tanpa prompt (misal untuk alur cepat):
php artisan bankmini:reset-nasabah --force
# Atau sertakan nama operator di audit log:
php artisan bankmini:reset-nasabah --operator="Nama Anda"

#B. Untuk Persiapan Release ke Production
# Karena perintah ini memiliki Production Guard, lakukan langkah berikut:

# Jalankan pembersihan saat APP_ENV masih local atau staging:
bash
php artisan bankmini:reset-nasabah --operator="Admin Rilis"
# Pastikan di /admin/users hanya tersisa akun-akun resmi yang memang dibutuhkan untuk operasional sekolah.
# Setelah database bersih, ubah file .env ke mode produksi:
env
APP_ENV=production
APP_DEBUG=false
# Setelah berada di mode produksi, perintah ini otomatis terkunci dan tidak bisa menghapus data nasabah lagi.
# Catatan Operasional: Jika selama masa pengujian Anda sempat mencoba fitur penutupan rekening (account_closures), pastikan tabel # tersebut tidak menahan foreign key relasi rekening lama saat reset dijalankan.
```

### Menjalankan Pengujian (Testing)
```bash
# Jalankan seluruh unit & feature tests
php artisan test

# Atau jalankan feature test tertentu
php vendor/bin/phpunit tests/Feature/ForgotPasswordTest.php
php vendor/bin/phpunit tests/Feature/AuthRbacTest.php
```
