# Sistem Informasi E-Teller Bank Mini Sekolah

Aplikasi client-server untuk operasional Bank Mini Sekolah yang mencakup pengelolaan rekening nasabah, transaksi loket teller (setoran & penarikan), rekonsiliasi kas harian, approval supervisor, pembukuan jurnal akuntansi otomatis, dan pemulihan kredensial (lupa password / reset PIN).

---

## 🚀 Fitur Utama

### 1. Autentikasi, RBAC & Pemulihan Kredensial (FASE 1)
- **Multi-Role RBAC**: Administrator, Teller, Supervisor, dan Nasabah.
- **Password & PIN Hashing**: Hashing aman menggunakan `bcrypt` (`password_hash` & `pin_hash`).
- **Security Headers & Anti Cache**: Proteksi CSP, X-Frame-Options, dan `Cache-Control: no-store`.
- **Rate Limiting**: Pembatasan 5 request/menit untuk mencegah serangan brute force pada login & pemulihan.
- **Fitur Lupa Password & Reset PIN (Self-Service Identity Verification)**:
  - **Pegawai (Staff)**: Verifikasi `username` + `full_name` terdaftar, penetapan password baru minimal 8 karakter.
  - **Nasabah (Siswa)**: Verifikasi `student_number` (NIS) + `class_name` terdaftar, penetapan PIN baru tepat 6 digit angka.
  - **Audit Logging**: Mencatat setiap aktivitas sukses, gagal, atau percobaan akun terblokir ke tabel `audit_logs`.

### 2. Manajemen Pengguna & Rekening (FASE 2)
- CRUD akun pegawai internal (Admin, Teller, Supervisor).
- CRUD profil nasabah/siswa lengkap dengan nomor rekening otomatis format tahun ajaran.
- Pengaturan konfigurasi sistem (saldo mengendap minimum, scanner QR).

### 3. Operasional Transaksi Loket (FASE 3)
- Pencarian profil nasabah melalui nomor rekening atau pemindaian QR Code kamera.
- Validasi slip dan uang fisik setoran tunai.
- Validasi saldo, saldo mengendap minimum Rp10.000, dan PIN nasabah 6 digit pada penarikan.
- Pencetakan struk transaksi otomatis dan transaksi berstatus final (read-only).

### 4. Penutupan Kas & Approval Supervisor (FASE 4)
- Rekap transaksi harian teller dan rekonsiliasi saldo fisik vs saldo sistem.
- Penguncian pencetakan draf jika terjadi selisih kas (*cash difference*).
- Approval supervisor dan pemindahan tanggung jawab kas ke brankas utama.

### 5. Informasi Nasabah & Integritas Akuntansi (FASE 5)
- Dasbor saldo dan riwayat mutasi mandiri untuk nasabah.
- Double-entry automatic journal: Kas (101) & Tabungan (201).
- Atomic database transaction dengan mekanisme rollback otomatis jika terjadi kegagalan.

---

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
# Hapus seluruh data nasabah, transaksi, jurnal, laporan, dan audit log hasil testing.
# Akun staff (admin/teller/supervisor) beserta passwordnya dan konfigurasi sistem tetap utuh.
php artisan bankmini:reset-nasabah

# Tanpa prompt konfirmasi (untuk skrip/deployment)
php artisan bankmini:reset-nasabah --force
```

### Menjalankan Pengujian (Testing)
```bash
# Jalankan seluruh unit & feature tests
php artisan test

# Atau jalankan feature test tertentu
php vendor/bin/phpunit tests/Feature/ForgotPasswordTest.php
php vendor/bin/phpunit tests/Feature/AuthRbacTest.php
```
