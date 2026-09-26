# SKENARIO USE CASE — MODUL AUTENTIKASI & KEAMANAN AKSES

---

## UC-AUTH-01 — Login Multi-Role

| Atribut | Keterangan |
|---|---|
| ID | UC-AUTH-01 |
| Nama | Login Multi-Role |
| Aktor | Pengguna (Admin/Teller/Supervisor/Nasabah) |
| Tujuan | Mengautentikasi pengguna dan mengarahkannya ke dasbor sesuai peran. |
| Trigger | Pengguna membuka halaman login dan mengirim kredensial. |

**Prakondisi**
1. Pengguna belum terautentikasi.
2. Halaman login dapat diakses.

**Kondisi Akhir**
- Sesi baru terbentuk; pengguna diarahkan ke dasbor sesuai peran; aktivitas tercatat pada audit log.

**Skenario Utama**
1. Pengguna membuka halaman login.
2. Pengguna memasukkan *identifier* (username pegawai atau NIS nasabah) dan *secret* (password atau PIN).
3. Sistem memvalidasi format input (secret wajib 6 digit bila identifier seluruhnya angka).
4. Sistem memeriksa kredensial pada guard pegawai (`username` + `password`).
5. Jika tidak cocok, sistem memeriksa kredensial pada guard nasabah (`student_number` + `PIN`).
6. Sistem memeriksa status akun = `active`.
7. Sistem meregenerasi ID sesi.
8. Sistem mencatat `login_success` pada audit log.
9. Sistem mengarahkan pengguna ke dasbor sesuai peran: Admin → `/admin/dashboard`, Teller → `/teller/dashboard`, Supervisor → `/supervisor/dashboard`, Nasabah → `/nasabah/dashboard`.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Format tidak valid**: Sistem menampilkan pesan kesalahan validasi dan mengembalikan pengguna ke formulir.
- **A2 (langkah 5) — Kredensial tidak cocok**: Sistem mencatat `login_failed` dan menampilkan pesan "Kredensial tidak valid".
- **A3 (langkah 6) — Akun diblokir**: Sistem melakukan logout paksa, mencatat `login_blocked`, dan menampilkan pesan "Akun Anda diblokir".
- **A4 — Rate limit terlampaui**: Endpoint login dibatasi 5 permintaan/menit; permintaan berlebih ditolak.

**Aturan Bisnis**
- Password pegawai dan PIN nasabah disimpan sebagai hash (bcrypt).
- Satu akun internal hanya memiliki satu peran aktif pada satu waktu.
- PIN nasabah tepat 6 digit.
- Percobaan gagal dicatat pada audit log.

**Kebutuhan Data**
- `users`: `username`, `password_hash`, `role`, `status`.
- `customers`: `student_number`, `pin_hash`, `status`.
- `audit_logs`: `action`, `entity_name`, `entity_id`, `ip_address`.

**Catatan Non-fungsional**
- Security headers (CSP, `X-Frame-Options`) dan `Cache-Control: no-store` diterapkan pada respons terautentikasi.
- Rate limiting 5 permintaan/menit pada endpoint login.

---

## UC-AUTH-02 — Logout

| Atribut | Keterangan |
|---|---|
| ID | UC-AUTH-02 |
| Nama | Logout |
| Aktor | Pengguna terautentikasi |
| Tujuan | Mengakhiri sesi aktif dan menghapus kredensial sesi. |
| Trigger | Pengguna menekan tombol logout. |

**Prakondisi**
1. Pengguna memiliki sesi aktif (guard `web` atau `nasabah`).

**Kondisi Akhir**
- Sesi dihapus/dibatalkan; token sesi diregenerasi; pengguna diarahkan ke halaman login; aktivitas tercatat.

**Skenario Utama**
1. Pengguna menekan tombol logout.
2. Sistem mengidentifikasi guard yang aktif (pegawai atau nasabah).
3. Sistem mencatat `logout` pada audit log.
4. Sistem menghapus sesi guard `web` dan `nasabah`.
5. Sistem membatalkan sesi (`invalidate`) dan meregenerasi CSRF token.
6. Sistem mengarahkan pengguna ke halaman login.

**Skenario Alternatif/Eksepsi**
- **A1 — Tidak ada sesi aktif**: Sistem tetap mengarahkan ke halaman login.

**Aturan Bisnis**
- Back-button tidak boleh menampilkan halaman terautentikasi setelah logout (`Cache-Control: no-store`).

**Kebutuhan Data**
- `audit_logs`, `sessions`.

---

## UC-AUTH-03 — Lupa Password Pegawai

| Atribut | Keterangan |
|---|---|
| ID | UC-AUTH-03 |
| Nama | Lupa Password Pegawai (Self-Service) |
| Aktor | Pegawai (Admin/Teller/Supervisor) |
| Tujuan | Memulihkan password pegawai melalui verifikasi identitas mandiri. |
| Trigger | Pegawai membuka halaman lupa password. |

**Prakondisi**
1. Pegawai belum dapat login karena lupa password.
2. Akun pegawai terdaftar pada sistem.

**Kondisi Akhir**
- Password pegawai diperbarui (hash); pegawai diarahkan ke halaman login.

**Skenario Utama**
1. Pegawai membuka halaman lupa password.
2. Pegawai mengisi `username`, `full_name` (nama lengkap terdaftar), password baru (minimal 8 karakter) dan konfirmasinya.
3. Sistem memvalidasi input.
4. Sistem mencari pengguna berdasarkan `username`.
5. Sistem memverifikasi kecocokan `full_name` (tidak peka huruf besar/kecil).
6. Sistem memeriksa status akun = `active`.
7. Sistem memperbarui `password_hash`.
8. Sistem mencatat `password_reset_success` pada audit log.
9. Sistem mengarahkan pegawai ke halaman login dengan pesan sukses.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Input tidak valid**: Sistem menampilkan pesan validasi (password < 8 karakter, konfirmasi tidak cocok).
- **A2 (langkah 5) — Identitas tidak cocok**: Sistem mencatat `password_reset_failed` dan menampilkan pesan identitas tidak sesuai.
- **A3 (langkah 6) — Akun diblokir**: Sistem mencatat `password_reset_blocked` dan menolak pemulihan.
- **A4 — Rate limit**: Endpoint pemulihan dibatasi 5 permintaan/menit.

**Aturan Bisnis**
- Password baru minimal 8 karakter.
- Verifikasi hanya berdasarkan `username` + `full_name` terdaftar.
- Akun berstatus `blocked` tidak dapat dipulihkan.

**Kebutuhan Data**
- `users`: `username`, `full_name`, `password_hash`, `role`, `status`.
- `audit_logs`.

---

## UC-AUTH-04 — Reset PIN Nasabah

| Atribut | Keterangan |
|---|---|
| ID | UC-AUTH-04 |
| Nama | Reset PIN Nasabah (Self-Service) |
| Aktor | Nasabah / Siswa |
| Tujuan | Memulihkan PIN nasabah melalui verifikasi identitas mandiri. |
| Trigger | Nasabah membuka halaman lupa PIN. |

**Prakondisi**
1. Nasabah belum dapat login karena lupa PIN.
2. Data nasabah terdaftar.

**Kondisi Akhir**
- PIN nasabah diperbarui (hash); penghitung gagal PIN direset; nasabah diarahkan ke halaman login.

**Skenario Utama**
1. Nasabah membuka halaman lupa PIN.
2. Nasabah mengisi `student_number` (NIS), `class_name` (kelas terdaftar), PIN baru 6 digit dan konfirmasinya.
3. Sistem memvalidasi input (PIN tepat 6 digit angka).
4. Sistem mencari nasabah berdasarkan `student_number`.
5. Sistem memverifikasi kecocokan `class_name`.
6. Sistem memeriksa status nasabah/rekening = `active`.
7. Sistem memperbarui `pin_hash`, mengosongkan `failed_pin_attempts`, dan `locked_until`.
8. Sistem mencatat `pin_reset_success` pada audit log.
9. Sistem mengarahkan nasabah ke halaman login.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Format PIN salah**: Sistem menampilkan pesan "PIN baru harus tepat 6 digit angka".
- **A2 (langkah 5) — Identitas tidak cocok**: Sistem mencatat `pin_reset_failed` dan menolak pemulihan.
- **A3 (langkah 6) — Akun diblokir**: Sistem mencatat `pin_reset_blocked` dan menolak pemulihan.
- **A4 — Rate limit**: 5 permintaan/menit.

**Aturan Bisnis**
- PIN tepat 6 digit angka dan disimpan sebagai hash.
- Verifikasi berdasarkan `student_number` + `class_name` terdaftar.
- Reset PIN membuka kembali penguncian akibat 3x salah PIN.

**Kebutuhan Data**
- `customers`: `student_number`, `class_name`, `pin_hash`, `status`, `failed_pin_attempts`, `locked_until`.
- `audit_logs`.
