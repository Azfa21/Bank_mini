# Plan Fitur Tambahan & Perbaikan Sistem Bank Mini Sekolah (Optimasi & Roadmap v2.0)

Berdasarkan analisis mendalam terhadap [`prd.md`](file:///d:/RPL/Project/Bank_Mini/prd.md), [`struktur.md`](file:///d:/RPL/Project/Bank_Mini/struktur.md), [`task.md`](file:///d:/RPL/Project/Bank_Mini/task.md), serta implementasi codebase Laravel saat ini, sistem telah berhasil menyelesaikan fungsionalitas inti (Fase 1 s.d. Fase 5).

Untuk meningkatkan kualitas aplikasi ke standar **enterprise/fintech perbankan sekolah** yang paling optimal, aman, dan siap digunakan secara riil atau dinilai maksimal dalam pengujian, berikut adalah analisis gap, rencana perbaikan integritas, dan penambahan fitur strategis.

---

## 1. Temuan Evaluasi & Kebutuhan Perbaikan (Hardening & Bug Fixes)

### 1.1 Integritas Akuntansi Kas Brankas
- **Masalah**: Saat ini ketika Supervisor menyetujui penutupan kas dan membuat `CashTransfer` (Laci Teller $\rightarrow$ Brankas Utama), data tercatat di tabel `cash_transfers` namun **tidak ada jurnal akuntansi** yang mencatat perpindahan kas fisik dari akun Kas Teller ke Kas Brankas.
- **Perbaikan**: Tambahkan kode akun `102` (Kas Brankas Utama / Khazanah). Saat Supervisor meng-approve penutupan kas, sistem otomatis mem-posting jurnal:
  - `Debet 102 Kas Brankas Utama`
  - `Kredit 101 Kas Teller`

### 1.2 Pencegahan Race Condition Nomor Transaksi & Laporan
- **Masalah**: `generateTransactionNumber()` dan `generateReportNumber()` menggunakan `count()`, yang berisiko bentrok (*duplicate key violation*) saat terjadi transaksi bersamaan di detik yang sama.
- **Perbaikan**: Implementasikan sequence generator berbasis atomik atau timestamp mikro/DB lock/random suffix unik yang terjamin keunikannya.

### 1.3 Perlindungan Brute-Force PIN Nasabah di Loket
- **Masalah**: Belum ada batasan salah memasukkan PIN nasabah pada transaksi penarikan di loket Teller.
- **Perbaikan**: Tambahkan mekanisme proteksi *failed PIN attempts* (maksimal 3-5 kali salah berturut-turut, akun dikunci sementara / butuh reset PIN, dan tercatat di Audit Log).

### 1.4 Pembersihan Typo & Dokumen Korup
- **Masalah**: Terdapat baris korup pada [`struktur.md`](file:///d:/RPL/Project/Bank_Mini/struktur.md#L32-L34) (`PlanPengaturan saldo mengendap minimum·auto coding` dan `D:\RPL\mdTasks:`).
- **Perbaikan**: Rapikan dan selaraskan dokumen dokumentasi.

---

## 2. Rencana Fitur Tambahan (Feature Enhancements Roadmap)

### FASE 6 — Transaksi Lanjutan & Layanan Nasabah (Customer & Teller Enhancements)
1. **Transfer Antar Rekening (Overbooking / P2P Transfer)**:
   - Fitur transfer saldo antar rekening siswa (misal untuk kas kelas, bayar iuran, atau transfer loket).
   - Validasi PIN nasabah, saldo minimum, dan atomisitas transaksi.
   - Jurnal ganda: `Debet 201 (Rekening Pengirim)` dan `Kredit 201 (Rekening Penerima)`.
2. **Cetak Buku Tabungan Fisik (Passbook Printing)**:
   - Modul cetak format buku tabungan fisik bank dengan opsi memilih baris (*line index*) agar bisa mencetak di atas buku tabungan cetak sekolah tanpa menimpa baris sebelumnya.
3. **Ekspor Rekening Koran Digital (e-Statement PDF)**:
   - Fitur download Rekening Koran resmi periode bulanan/kustom dengan format standar bank, barcode verifikasi, dan watermark.
4. **Kartu Pelajar / Nasabah Virtual & QR Generator**:
   - Tampilan Kartu Nasabah Virtual di halaman Nasabah & Admin dengan QR Code yang bisa diunduh/dicetak (ID Card Tabungan).

### FASE 7 — Modul Laporan Keuangan & Akuntansi Lengkap (General Ledger & Financial Reporting)
1. **Buku Besar (General Ledger)**:
   - Filter mutasi per kode akun (`101 Kas Teller`, `102 Kas Brankas`, `201 Tabungan Nasabah`).
2. **Neraca Saldo (Trial Balance) & Laporan Likuiditas**:
   - Memastikan total seluruh debet sama dengan total kredit di seluruh sistem.
   - Menghitung rasio likuiditas: Total Kas Fisik (Laci + Brankas) vs Total Kewajiban Simpanan Nasabah.
3. **Ekspor Jurnal & Laporan ke Excel/CSV**:
   - Kemudahan audit eksternal dan laporan periodik ke Kepala Sekolah.

### FASE 8 — Monitoring, Analitik & Manajemen Loket (Operations & Analytics)
1. **Dashboard Analytics & Real-Time Financial Metric**:
   - Grafik interaktif tren setoran vs penarikan harian/bulanan.
   - Top nasabah penabung aktif & statistik performa Teller.
2. **Workflow Penyesuaian Selisih Kas (Voucher Penyesuaian / Adjustment)**:
   - Modul investigasi resmi ketika ada selisih kas fisik di laci dengan berita acara dan jurnal penyesuaian (*Beban Selisih Kas* / *Pendapatan Selisih Kas*).
3. **Antrean Loket Sederhana (Queue Ticket & Display)**:
   - Nomor antrean loket yang dapat ditampilkan di layar display ruang Bank Mini.

---

## 3. Proposed Changes (Rincian File yang Akan Dimodifikasi/Dibuat)

### Core Integrity & Backend Service
#### [MODIFY] [DailyReportService.php](file:///d:/RPL/Project/Bank_Mini/app/Services/DailyReportService.php)
- Tambahkan auto-posting jurnal transfer kas `101 Kas Teller` $\rightarrow$ `102 Kas Brankas` saat approval Supervisor.
- Perbaiki sequence number generation agar anti-collision.

#### [MODIFY] [TransactionService.php](file:///d:/RPL/Project/Bank_Mini/app/Services/TransactionService.php)
- Perbaiki sequence number generation (`TRX-...`).
- Tambahkan service method untuk transaksi `transfer` antar rekening beserta jurnal balance assertion.
- Tambahkan proteksi rate limit / failed attempt PIN nasabah.

#### [MODIFY] [Account.php](file:///d:/RPL/Project/Bank_Mini/app/Models/Account.php) & [Transaction.php](file:///d:/RPL/Project/Bank_Mini/app/Models/Transaction.php)
- Dukungan transaksi tipe `transfer` (relasi sender/receiver).

#### [NEW] Migration: `add_transfer_and_pin_attempts_fields`
- Menambahkan kolom `failed_pin_attempts`, `locked_until` pada tabel nasabah/rekening.
- Menambahkan tipe `transfer_in` / `transfer_out` pada transaksi jika diperlukan.

### Reporting & Accounting Modules
#### [NEW] [AccountingReportController.php](file:///d:/RPL/Project/Bank_Mini/app/Http/Controllers/AccountingReportController.php)
- Controller untuk Buku Besar (General Ledger), Neraca Saldo (Trial Balance), dan Ekspor CSV/Excel.

#### [MODIFY] [JournalController.php](file:///d:/RPL/Project/Bank_Mini/app/Http/Controllers/JournalController.php)
- Tambahkan filter akun, rentang tanggal, dan fitur ekspor CSV.

### Nasabah & Teller Modules
#### [NEW] [PassbookController.php](file:///d:/RPL/Project/Bank_Mini/app/Http/Controllers/Teller/PassbookController.php) & View Cetak Buku Tabungan
- Antarmuka cetak baris buku tabungan fisik.

#### [MODIFY] [MutasiController.php](file:///d:/RPL/Project/Bank_Mini/app/Http/Controllers/Nasabah/MutasiController.php) & [DashboardController.php](file:///d:/RPL/Project/Bank_Mini/app/Http/Controllers/Nasabah/DashboardController.php)
- Tambahkan tombol Cetak Rekening Koran / e-Statement PDF dan Kartu Virtual ber-QR Code.

### Dokumentasi
#### [MODIFY] [struktur.md](file:///d:/RPL/Project/Bank_Mini/struktur.md) & [prd.md](file:///d:/RPL/Project/Bank_Mini/prd.md) & [task.md](file:///d:/RPL/Project/Bank_Mini/task.md)
- Bersihkan baris teks rusak dan perbarui roadmap Fase 6 - 8.

---

## 4. Verification Plan

### Automated Tests
- Eksekusi unit/feature test: `php artisan test`
- Buat test suite baru:
  - `tests/Feature/VaultJournalTest.php`: Memvalidasi jurnal `102 Kas Brankas` saat approval laporan harian.
  - `tests/Feature/TransferTransactionTest.php`: Memvalidasi transaksi transfer antar rekening, saldo, PIN, dan double-entry journal.
  - `tests/Feature/PinRateLimitTest.php`: Memvalidasi lock akun saat PIN berulang kali salah.
  - `tests/Feature/AccountingReportTest.php`: Memvalidasi kalkulasi Neraca Saldo dan Buku Besar.

### Manual Verification
- Uji coba transaksi setor, tarik, dan transfer di UI Teller.
- Uji coba penutupan kas Teller dan approval Supervisor, cek jurnal otomatis akun `102`.
- Verifikasi cetak struk, cetak buku tabungan, dan unduh e-Statement.
- Cek audit log dan security header.
