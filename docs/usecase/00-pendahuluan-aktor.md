# SKENARIO USE CASE — SISTEM INFORMASI E-TELLER BANK MINI SEKOLAH

## 00. PENDAHULUAN, RUANG LINGKUP, DAN IDENTIFIKASI AKTOR

### 0.1 Identitas Dokumen

| Atribut | Keterangan |
|---|---|
| Nama Sistem | Sistem Informasi E-Teller Bank Mini Sekolah |
| Jenis Dokumen | Skenario Use Case (Use Case Scenario) |
| Arsitektur | Client-Server (aplikasi web) |
| Basis Data | Relasional (transaksional, foreign key, check constraint) |
| Teknologi | Laravel (PHP), Blade, MySQL/SQLite, Vite |
| Bahasa | Indonesia |
| Versi | 1.0 |

### 0.2 Tujuan Dokumen

Dokumen ini menjabarkan seluruh alur interaksi antara aktor dengan sistem dalam bentuk skenario use case. Setiap use case memuat kondisi awal, alur utama, alur alternatif/eksepsi, aturan bisnis, kebutuhan data, dan catatan non-fungsional. Dokumen ini menjadi acuan perancangan, pengujian, dan penyusunan activity diagram.

### 0.3 Ruang Lingkup

Sistem mencakup:
1. Autentikasi, otorisasi berbasis peran (RBAC), dan pemulihan kredensial.
2. Manajemen akun internal (Admin, Teller, Supervisor) dan profil nasabah.
3. Operasional transaksi loket: setoran, penarikan, transfer.
4. Penutupan kas harian dan persetujuan Supervisor.
5. Informasi mandiri nasabah (saldo, mutasi, rekening koran, kartu virtual).
6. Akuntansi double-entry otomatis dan pelaporan keuangan.

Di luar ruang lingkup: integrasi pembayaran daring, aplikasi mobile native, dan koneksi ke sistem bank eksternal.

### 0.4 Identifikasi Aktor

| Aktor | Jenis | Deskripsi | Hak Akses Utama |
|---|---|---|---|
| Administrator | Internal (manusia) | Mengelola akun internal, data nasabah/rekening, konfigurasi, dan audit jurnal. | Manajemen pengguna, nasabah, konfigurasi, laporan & jurnal. Tidak boleh transaksi loket. |
| Teller (Piket) | Internal (manusia) | Melayani pencarian nasabah, setoran, penarikan, transfer, registrasi, cetak passbook, dan penutupan kas. | Transaksi loket & laporan harian. Tidak dapat mengubah/menghapus transaksi final. |
| Supervisor (Kepala Bank Mini) | Internal (manusia) | Memvalidasi laporan, menyetujui, memindahkan kas ke brankas, dan mengoreksi transaksi. | Approval laporan, koreksi/pembalik, laporan & jurnal. Tidak boleh transaksi loket. |
| Nasabah / Siswa | Eksternal (manusia) | Melihat saldo, mutasi, rekening koran, dan kartu; memasukkan PIN untuk penarikan/transfer. | Data rekening miliknya sendiri. |
| Sistem (Aplikasi Server) | Sistem | Memvalidasi, memproses transaksi atomik, membuat jurnal, dan mencatat audit. | — |
| Database | Sistem | Menyimpan data dengan integritas transaksional. | — |
| Printer | Perangkat | Mencetak struk, buku tabungan, laporan, dan kartu. | — |

### 0.5 Daftar Use Case

| ID | Nama Use Case | Aktor Utama | Modul |
|---|---|---|---|
| UC-AUTH-01 | Login Multi-Role | Pengguna | Autentikasi |
| UC-AUTH-02 | Logout | Pengguna | Autentikasi |
| UC-AUTH-03 | Lupa Password Pegawai | Pegawai | Autentikasi |
| UC-AUTH-04 | Reset PIN Nasabah | Nasabah | Autentikasi |
| UC-ADM-01 | Kelola Akun Internal | Administrator | Admin |
| UC-ADM-02 | Kelola Profil Nasabah | Administrator | Admin |
| UC-ADM-03 | Import Nasabah CSV | Administrator | Admin |
| UC-ADM-04 | Lihat Detail Rekening & Cetak Kartu | Administrator | Admin |
| UC-ADM-05 | Konfigurasi Sistem | Administrator | Admin |
| UC-ADM-06 | Audit Jurnal & Buku Besar | Administrator, Supervisor | Akuntansi |
| UC-ADM-07 | Laporan Keuangan | Administrator, Supervisor | Akuntansi |
| UC-TLR-01 | Pencarian Nasabah | Teller | Teller |
| UC-TLR-02 | Registrasi Nasabah Didampingi Teller | Teller | Teller |
| UC-TLR-03 | Transaksi Setoran Tunai | Teller, Nasabah | Teller |
| UC-TLR-04 | Transaksi Penarikan Tunai | Teller, Nasabah | Teller |
| UC-TLR-05 | Transfer Antar Rekening | Teller, Nasabah | Teller |
| UC-TLR-06 | Cetak Struk Transaksi | Teller | Teller |
| UC-TLR-07 | Cetak Buku Tabungan (Passbook) | Teller | Teller |
| UC-TLR-08 | Penutupan Kas (Rekonsiliasi Harian) | Teller | Teller |
| UC-TLR-09 | Cetak Laporan Harian Teller | Teller | Teller |
| UC-SPV-01 | Daftar & Detail Laporan Harian | Supervisor | Supervisor |
| UC-SPV-02 | Approval Laporan Harian | Supervisor | Supervisor |
| UC-SPV-03 | Koreksi/Pembalik Transaksi | Supervisor | Supervisor |
| UC-SPV-04 | Laporan Akuntansi Supervisor | Supervisor | Akuntansi |
| UC-NSB-01 | Lihat Saldo | Nasabah | Nasabah |
| UC-NSB-02 | Riwayat Mutasi | Nasabah | Nasabah |
| UC-NSB-03 | Cetak e-Statement (Rekening Koran) | Nasabah | Nasabah |
| UC-NSB-04 | Cetak Kartu Nasabah Virtual | Nasabah | Nasabah |

### 0.6 Matriks Otorisasi Role

| Fungsi | Admin | Teller | Supervisor | Nasabah |
|---|---|---|---|---|
| Kelola akun internal | Ya | Tidak | Tidak | Tidak |
| Kelola profil nasabah & rekening | Ya | Registrasi didampingi | Tidak | Tidak |
| Konfigurasi sistem | Ya | Tidak | Tidak | Tidak |
| Setoran / Penarikan / Transfer | Tidak | Ya | Tidak | Tidak (hanya otorisasi PIN) |
| Penutupan kas | Tidak | Ya | Tidak | Tidak |
| Approval laporan harian | Tidak | Tidak | Ya (bukan laporan sendiri) | Tidak |
| Koreksi/pembalik transaksi | Tidak | Tidak | Ya | Tidak |
| Audit jurnal & Buku Besar | Ya | Tidak | Ya | Tidak |
| Laporan keuangan (Laba Rugi, Neraca) | Ya | Tidak | Ya | Tidak |
| Lihat saldo & mutasi | Tidak | Tidak | Tidak | Ya (milik sendiri) |

### 0.7 Konvensi Penulisan Skenario

- **Prakondisi**: keadaan yang harus benar sebelum use case dimulai.
- **Skenario Utama**: jalur sukses normal, ditulis bernomor.
- **Skenario Alternatif/Eksepsi**: penyimpangan dari alur utama, merujuk nomor langkah.
- **Kondisi Akhir**: keadaan sistem setelah use case selesai.
- **Aturan Bisnis**: batasan/validasi yang mengikat.
- **Kebutuhan Data**: entitas/field yang dibaca atau ditulis.
