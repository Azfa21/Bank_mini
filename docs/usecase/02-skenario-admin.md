# SKENARIO USE CASE — MODUL ADMINISTRATOR

---

## UC-ADM-01 — Kelola Akun Internal

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-01 |
| Nama | Kelola Akun Internal (Admin, Teller, Supervisor) |
| Aktor | Administrator |
| Tujuan | Menambah, mengubah, memblokir/mengaktifkan, dan menghapus akun pegawai internal. |
| Trigger | Administrator membuka menu kelola pengguna. |

**Prakondisi**
1. Administrator terautentikasi dengan peran `admin`.

**Kondisi Akhir**
- Data akun internal tersimpan/diperbarui; perubahan tercatat pada audit log.

**Skenario Utama**
1. Administrator membuka menu kelola pengguna.
2. Sistem menampilkan daftar akun dengan filter peran dan status.
3. Administrator memilih aksi: **tambah**, **ubah**, **blokir/aktifkan**, atau **hapus**.
4. **Tambah**: Administrator mengisi `full_name`, `username`, `password`, `role` (admin/teller/supervisor), dan `status`.
5. **Ubah**: Administrator mengubah data akun; password bersifat opsional.
6. Sistem memvalidasi keunikan `username`, validitas `role`, dan kewajiban password saat pembuatan.
7. Sistem menerapkan guard "minimal harus tersisa 1 akun aktif" untuk setiap peran.
8. Sistem menyimpan perubahan ke database.
9. Sistem mencatat audit log dan memuat ulang daftar.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 6) — Validasi gagal**: Sistem menampilkan pesan error (username duplikat, role tidak valid, password kosong).
- **A2 (langkah 7) — Melanggar guard peran**: Sistem menolak perubahan (mis. memblokir admin aktif terakhir).
- **A3 — Hapus akun dengan relasi**: Sistem menolak penghapusan bila akun masih memiliki data terkait (nasabah/transaksi/laporan/pemindahan kas).
- **A4 — Hapus akun sendiri**: Sistem menolak penghapusan akun yang sedang login.

**Aturan Bisnis**
- Hanya Administrator yang dapat mengelola akun internal.
- Peran yang tersedia: `admin`, `teller`, `supervisor`.
- Akun berstatus `blocked` tidak dapat login.
- Minimal satu akun aktif per peran harus tetap ada.

**Kebutuhan Data**
- `users`: `full_name`, `username`, `password_hash`, `role`, `status`.
- `audit_logs`.

---

## UC-ADM-02 — Kelola Profil Nasabah

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-02 |
| Nama | Kelola Profil Nasabah/Siswa |
| Aktor | Administrator |
| Tujuan | Menambah, mengubah, memblokir/mengaktifkan profil nasabah beserta rekeningnya. |
| Trigger | Administrator membuka menu kelola nasabah. |

**Prakondisi**
1. Administrator terautentikasi dengan peran `admin`.

**Kondisi Akhir**
- Profil nasabah dan rekening tersimpan; nomor siswa dan nomor rekening dibuat otomatis.

**Skenario Utama**
1. Administrator membuka menu kelola nasabah.
2. Sistem menampilkan daftar nasabah dengan filter status dan pencarian (nama/NIS/kelas).
3. Administrator memilih aksi: **tambah**, **ubah**, atau **blokir/aktifkan**.
4. Administrator mengisi `full_name`, `class_name`, `phone_number`, dan `pin` (6 digit).
5. Sistem memvalidasi field dan keunikan `student_number`/`qr_code_value`.
6. Sistem membuat profil nasabah dengan NIS otomatis dan `qr_code_value` (UUID).
7. Sistem membuka rekening otomatis (nomor rekening format tahun ajaran, saldo awal 0, saldo minimum default Rp10.000).
8. Sistem mencatat audit log dan memuat ulang daftar.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 5) — Validasi gagal**: Sistem menampilkan pesan error.
- **A2 — Blokir/Aktifkan**: Sistem mengubah status nasabah; nasabah diblokir tidak dapat bertransaksi/login.

**Aturan Bisnis**
- Satu nasabah memiliki paling banyak satu rekening aktif.
- Nomor rekening dibuat berdasarkan tahun ajaran dan nomor urut (unik).
- `student_number` dan `qr_code_value` harus unik.

**Kebutuhan Data**
- `customers`: `student_number`, `full_name`, `class_name`, `phone_number`, `pin_hash`, `qr_code_value`, `status`.
- `accounts`: `account_number`, `customer_id`, `academic_year`, `balance`, `opening_balance`, `minimum_balance`, `status`.
- `audit_logs`.

---

## UC-ADM-03 — Import Nasabah CSV

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-03 |
| Nama | Import Nasabah via CSV |
| Aktor | Administrator |
| Tujuan | Menambahkan banyak nasabah sekaligus dari berkas CSV. |
| Trigger | Administrator membuka form import nasabah. |

**Prakondisi**
1. Administrator terautentikasi.
2. Berkas CSV berisi kolom `nis`, `nama`, `kelas`, `no_hp`.

**Kondisi Akhir**
- Nasabah valid dibuat beserta rekeningnya; ringkasan hasil ditampilkan.

**Skenario Utama**
1. Administrator membuka form import.
2. Administrator mengunduh template CSV (opsional).
3. Administrator mengunggah berkas CSV.
4. Sistem memvalidasi header dan format berkas.
5. Sistem mengiterasi setiap baris: memvalidasi NIS/nama/kelas.
6. Untuk baris valid, sistem membuat profil nasabah dan membuka rekening otomatis.
7. Baris tidak valid dicatat sebagai gagal.
8. Sistem menghitung dan menampilkan ringkasan (jumlah berhasil/gagal).

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 4) — Format tidak valid**: Sistem menampilkan pesan error dan menghentikan proses.
- **A2 (langkah 5) — Baris tidak valid**: Baris dilewati, dicatat sebagai gagal, proses lanjut.

**Aturan Bisnis**
- NIS dan nomor rekening dibuat/diverifikasi unik.
- Import dijalankan dalam transaksi per baris.

**Kebutuhan Data**
- `customers`, `accounts`, `audit_logs`.

---

## UC-ADM-04 — Lihat Detail Rekening & Cetak Kartu

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-04 |
| Nama | Lihat Detail Rekening & Cetak Kartu Nasabah |
| Aktor | Administrator |
| Tujuan | Melihat detail rekening dan mencetak kartu nasabah ber-QR Code. |
| Trigger | Administrator membuka detail rekening dari daftar nasabah. |

**Prakondisi**
1. Administrator terautentikasi.
2. Rekening nasabah tersedia.

**Kondisi Akhir**
- Detail rekening ditampilkan; kartu nasabah dirender untuk dicetak.

**Skenario Utama**
1. Administrator membuka detail rekening nasabah.
2. Sistem memuat data akun dan nasabah.
3. Sistem menampilkan saldo awal, saldo berjalan, saldo minimum, dan status rekening.
4. Administrator memilih cetak kartu nasabah.
5. Sistem merender kartu dengan QR Code.
6. Administrator mencetak/menyimpan kartu.

**Skenario Alternatif/Eksepsi**
- **A1 — Rekening tidak ditemukan**: Sistem menampilkan pesan kesalahan.

**Kebutuhan Data**
- `accounts`, `customers` (`qr_code_value`).

---

## UC-ADM-05 — Konfigurasi Sistem

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-05 |
| Nama | Konfigurasi Sistem / Produk Bank Mini |
| Aktor | Administrator |
| Tujuan | Mengatur parameter produk bank mini. |
| Trigger | Administrator membuka halaman konfigurasi. |

**Prakondisi**
1. Administrator terautentikasi.

**Kondisi Akhir**
- Konfigurasi tersimpan dan berlaku pada validasi transaksi berikutnya.

**Skenario Utama**
1. Administrator membuka halaman konfigurasi.
2. Sistem menampilkan konfigurasi saat ini.
3. Administrator mengubah: `bank_name`, `operational_hours`, `minimum_balance`, `admin_fee`, `max_withdrawal_without_order`.
4. Sistem memvalidasi input.
5. Sistem menyimpan konfigurasi (`updateOrCreate`).
6. Sistem mencatat audit log dan menampilkan konfirmasi sukses.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 4) — Validasi gagal**: Sistem menampilkan pesan error.

**Aturan Bisnis**
- Nilai default saldo mengendap minimum Rp10.000.
- Batas penarikan tanpa pesanan `0` berarti tidak ada batas.
- Biaya administrasi dicatat sebagai pendapatan (akun 401).

**Kebutuhan Data**
- `system_configs`: `config_key`, `config_value`.

---

## UC-ADM-06 — Audit Jurnal & Buku Besar

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-06 |
| Nama | Audit Jurnal & Buku Besar |
| Aktor | Administrator, Supervisor |
| Tujuan | Menelusuri jurnal akuntansi dan buku besar per akun. |
| Trigger | Aktor membuka menu audit jurnal / buku besar. |

**Prakondisi**
1. Aktor terautentikasi sebagai `admin` atau `supervisor`.

**Kondisi Akhir**
- Daftar jurnal dan/atau buku besar ditampilkan.

**Skenario Utama**
1. Aktor membuka audit jurnal.
2. Aktor menerapkan filter (nomor transaksi, kode akun, rentang tanggal).
3. Sistem mengambil jurnal beserta total debit dan kredit.
4. Sistem menampilkan daftar jurnal.
5. Aktor memilih Buku Besar per akun (101, 102, 201, 401, 501).
6. Sistem menghitung saldo akhir sesuai *normal balance* akun.
7. Sistem menampilkan Buku Besar.

**Skenario Alternatif/Eksepsi**
- **A1 — Akses ditolak**: Teller dan Nasabah ditolak (403) saat mengakses jurnal.

**Aturan Bisnis**
- Hanya Administrator dan Supervisor yang dapat mengakses jurnal back-end.
- Total debit dan kredit setiap transaksi harus seimbang.

**Kebutuhan Data**
- `jurnal_akuntansi`: `journal_number`, `transaction_id`, `account_code`, `entry_type`, `debit_amount`, `credit_amount`, `description`, `status`, `journal_at`.

---

## UC-ADM-07 — Laporan Keuangan

| Atribut | Keterangan |
|---|---|
| ID | UC-ADM-07 |
| Nama | Laporan Keuangan (Neraca Saldo, Laba Rugi, Neraca) |
| Aktor | Administrator, Supervisor |
| Tujuan | Menampilkan laporan keuangan dan mengekspornya. |
| Trigger | Aktor membuka menu laporan keuangan. |

**Prakondisi**
1. Aktor terautentikasi sebagai `admin` atau `supervisor`.

**Kondisi Akhir**
- Laporan ditampilkan di halaman atau diunduh sebagai CSV.

**Skenario Utama**
1. Aktor membuka menu laporan keuangan.
2. Aktor memilih jenis laporan: **Neraca Saldo**, **Laba Rugi**, atau **Neraca**.
3. Sistem menghitung nilai dari jurnal akuntansi.
4. Neraca Saldo: menampilkan total debit/kredit, status keseimbangan, dan rasio likuiditas kas (101+102)/201.
5. Laba Rugi: menampilkan pendapatan (401), beban (501), dan laba bersih.
6. Neraca: menampilkan aset, kewajiban, ekuitas, dan status keseimbangan (aset = kewajiban + ekuitas).
7. Aktor dapat mengekspor laporan ke CSV.

**Skenario Alternatif/Eksepsi**
- **A1 — Tidak seimbang**: Sistem menandai laporan sebagai tidak seimbang.

**Aturan Bisnis**
- Rumus Neraca: Aset = Kewajiban + Ekuitas.
- Akun: Kas Teller (101), Kas Brankas (102), Tabungan (201), Pendapatan Admin (401), Beban Operasional (501).

**Kebutuhan Data**
- `jurnal_akuntansi`.
