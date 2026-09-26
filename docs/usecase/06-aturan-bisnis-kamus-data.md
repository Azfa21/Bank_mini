# ATURAN BISNIS GLOBAL & KAMUS DATA

---

## 1. ATURAN BISNIS GLOBAL

### 1.1 Keamanan & Akses
| Kode | Aturan |
|---|---|
| BR-01 | Seluruh pengguna wajib melewati autentikasi sebelum mengakses fungsi terlindungi. |
| BR-02 | Password pegawai dan PIN nasabah disimpan sebagai hash (bcrypt), tidak pernah dalam bentuk teks biasa. |
| BR-03 | Sistem menerapkan session/token guard; setiap permintaan memvalidasi peran sebelum query/operasi data. |
| BR-04 | Akses URL yang tidak sesuai peran ditolak dan dialihkan. |
| BR-05 | PIN nasabah tepat 6 digit; PIN tidak ditampilkan atau disimpan sebagai teks biasa. |
| BR-06 | Rate limiting 5 permintaan/menit pada login dan pemulihan kredensial. |
| BR-07 | Security headers (CSP, `X-Frame-Options`) dan `Cache-Control: no-store` diterapkan pada halaman terautentikasi. |
| BR-08 | Akses kamera dibatasi pada origin sendiri (`Permissions-Policy: camera=(self)`). |
| BR-09 | Audit log mencatat login, logout, penolakan akses, transaksi, approval, koreksi, dan pemulihan kredensial. |
| BR-10 | Audit log tidak menyimpan password/PIN asli. |

### 1.2 Pemisahan Tugas (Segregation of Duties)
| Kode | Aturan |
|---|---|
| BR-11 | Administrator tidak boleh memproses setoran/penarikan. |
| BR-12 | Supervisor tidak boleh melakukan transaksi loket. |
| BR-13 | Teller tidak dapat menyetujui laporan hariannya sendiri. |
| BR-14 | Teller tidak dapat mengubah atau menghapus transaksi berstatus final. |
| BR-15 | Nasabah tidak dapat mengakses modul transaksi, jurnal, akun internal, atau data nasabah lain. |

### 1.3 Validasi Transaksi
| Kode | Aturan |
|---|---|
| BR-16 | Nominal transaksi harus > Rp0. |
| BR-17 | Transaksi setoran hanya disimpan bila nominal slip = nominal uang fisik. |
| BR-18 | Penarikan hanya bila saldo mencukupi. |
| BR-19 | Saldo setelah penarikan tidak boleh kurang dari saldo mengendap minimum (default Rp10.000). |
| BR-20 | Penarikan wajib memverifikasi PIN nasabah. |
| BR-21 | PIN salah 3 kali berturut-turut mengunci rekening selama 15 menit. |
| BR-22 | Penarikan yang melebihi batas tanpa pesanan ditolak (bila batas > 0). |
| BR-23 | Rekening harus ditemukan dan berstatus `active` sebelum transaksi. |
| BR-24 | Rekening tujuan transfer tidak boleh sama dengan rekening pengirim. |
| BR-25 | Semua validasi penting dilakukan di sisi server, bukan hanya di frontend. |

### 1.4 Akuntansi & Integritas Data
| Kode | Aturan |
|---|---|
| BR-26 | Setiap transaksi berhasil menghasilkan tepat dua baris jurnal (double-entry). |
| BR-27 | Total debit harus sama dengan total kredit. |
| BR-28 | Setoran: Debet Kas (101), Kredit Tabungan (201). |
| BR-29 | Penarikan: Debet Tabungan (201), Kredit Kas (101); biaya admin → Kredit Pendapatan Admin (401). |
| BR-30 | Transfer: Debet Tabungan (201) pengirim, Kredit Tabungan (201) penerima. |
| BR-31 | Pemindahan kas brankas: Debet Kas Brankas (102), Kredit Kas Teller (101). |
| BR-32 | Perubahan saldo, transaksi, dan jurnal dilakukan dalam satu database transaction. |
| BR-33 | Kegagalan validasi/penyimpanan membatalkan seluruh proses (rollback). |
| BR-34 | Nominal rupiah menggunakan tipe integer presisi (BIGINT), bukan floating point. |
| BR-35 | Foreign key menggunakan ON DELETE RESTRICT untuk menjaga histori. |
| BR-36 | Penghapusan data master yang masih direferensikan transaksi ditolak. |

### 1.5 Penutupan Kas & Approval
| Kode | Aturan |
|---|---|
| BR-37 | Rumus saldo akhir: Saldo Awal + Total Setoran − Total Penarikan. |
| BR-38 | Laporan final tidak dapat dicetak bila terdapat selisih kas. |
| BR-39 | Laporan berselisih tidak dapat disetujui; wajib berita acara (min 10 karakter). |
| BR-40 | Pemindahan kas hanya setelah approval Supervisor. |
| BR-41 | Koreksi ditolak bila laporan harian tanggal transaksi telah disetujui (kas sudah di brankas). |
| BR-42 | Transaksi yang sudah dikoreksi tidak dapat dikoreksi ulang. |

### 1.6 Chart of Accounts
| Kode Akun | Nama Akun | Saldo Normal | Tipe |
|---|---|---|---|
| 101 | Kas Teller (Laci Loket) | Debit | Aset |
| 102 | Kas Brankas Utama (Khazanah) | Debit | Aset |
| 201 | Tabungan Nasabah (Simpanan Siswa) | Kredit | Kewajiban |
| 401 | Pendapatan Administrasi | Kredit | Pendapatan |
| 501 | Beban Operasional Bank Mini | Debit | Beban |

### 1.7 Status & Enumerasi
| Entitas | Status/Enum | Nilai |
|---|---|---|
| users | role | admin, teller, supervisor |
| users/customers | status | active, blocked |
| transactions | type | deposit, withdrawal, transfer, reversal |
| transactions | status | success, reversed |
| daily_reports | status | draft, submitted, approved, rejected |
| cash_transfers | status | transferred |
| jurnal_akuntansi | entry_type | debit, credit |
| jurnal_akuntansi | status | posted |

---

## 2. KAMUS DATA (FIELD WAJIB)

### 2.1 users
`id`, `full_name`, `username` (unik), `password_hash`, `role`, `status`, `last_login_at`, `created_at`, `updated_at`.

### 2.2 customers
`id`, `student_number` (unik), `full_name`, `class_name`, `phone_number`, `pin_hash`, `qr_code_value` (unik), `created_by`, `status`, `failed_pin_attempts`, `locked_until`, `created_at`.

### 2.3 accounts
`id`, `account_number` (unik), `customer_id` (FK), `academic_year`, `opening_balance`, `balance`, `minimum_balance`, `status`, `opened_at`, `created_at`, `updated_at`.

### 2.4 transactions
`id`, `account_id` (FK), `recipient_account_id` (FK, nullable), `teller_id` (FK), `type`, `amount`, `admin_fee`, `slip_number`, `slip_amount`, `physical_cash_amount`, `denomination_breakdown` (JSON), `balance_before`, `balance_after`, `receipt_number` (unik), `notes`, `reversal_of_transaction_id` (FK), `reversed_at`, `reversed_by` (FK), `reversal_reason`, `status`, `transaction_at`, `created_at`.

### 2.5 jurnal_akuntansi
`id`, `journal_number`, `transaction_id` (FK, nullable), `cash_transfer_id` (FK, nullable), `account_code`, `entry_type`, `amount`, `debit_amount`, `credit_amount`, `description`, `status`, `journal_at`, `created_at`.

### 2.6 daily_reports
`id`, `report_number` (unik), `teller_id` (FK), `report_date`, `opening_balance`, `total_deposit`, `total_withdrawal`, `total_admin_fee`, `closing_balance_system`, `closing_balance_physical`, `closing_breakdown` (JSON), `cash_difference`, `status`, `approved_by` (FK), `approved_at`, `submitted_at`, `approval_notes`, `created_at`, `updated_at`.
- Keunikan: (`teller_id`, `report_date`).

### 2.7 cash_transfers
`id`, `report_id` (FK), `supervisor_id` (FK), `amount`, `source_location`, `destination_location`, `status`, `transferred_at`, `notes`, `created_at`.

### 2.8 system_configs
`id`, `config_key` (unik), `config_value`, `updated_at`.
- Kunci: `bank_name`, `operational_hours`, `minimum_balance`, `admin_fee`, `max_withdrawal_without_order`.

### 2.9 audit_logs
`id`, `user_id` (FK, nullable), `action`, `entity_name`, `entity_id`, `ip_address`, `metadata` (JSON), `created_at`.

### 2.10 document_sequences
`id`, `sequence_key` (unik), `last_number`, `updated_at` — untuk generator nomor transaksi/laporan anti-collision.

---

## 3. FORMAT PENOMORAN

| Dokumen | Format | Contoh |
|---|---|---|
| Nomor rekening | `10` + `YY` + `YY+1` + urut 4 digit | `1026270001` |
| Nomor siswa (NIS) | `YYYY` + urut 4 digit | `20260001` |
| Nomor transaksi/struk | `TRX-YYYYMMDD` + urut | `TRX-20260921-0001` |
| Nomor slip | `SLIP-YYYYMMDD` + urut | `SLIP-20260921-0001` |
| Nomor laporan harian | `RPT-YYYYMMDD` + urut | `RPT-20260921-0001` |
| Nomor koreksi | `REV-YYYYMMDD` + urut | `REV-20260921-0001` |
| Nomor jurnal transaksi | `<receipt_number>-NN` | `TRX-20260921-0001-01` |
| Nomor jurnal pemindahan | `JRN-TRF-YYYYMMDD-XXXX-NN` | `JRN-TRF-20260921-0001-01` |

---

## 4. KEBUTUHAN NON-FUNGSIONAL

| Aspek | Kebutuhan |
|---|---|
| Konsistensi | Transaksi atomik dengan commit/rollback; pencegahan race condition via row lock. |
| Performa | Pencarian nomor rekening/QR cepat; paginasi riwayat transaksi. |
| Keamanan | Hashing kredensial, RBAC, security headers, rate limiting, audit trail. |
| Auditabilitas | Seluruh aktivitas penting tercatat pada audit log tanpa data sensitif. |
| Portabilitas | Berjalan pada Windows/Linux Desktop dan jaringan lokal sekolah. |
| Perangkat | Dukungan kamera/webcam untuk QR Code, printer untuk struk/laporan/kartu. |
| Antarmuka | Format Rupiah, hanya menerima angka positif, pesan kesalahan jelas, pencegahan double submit. |
| Bahasa | Antarmuka dan dokumen berbahasa Indonesia. |
