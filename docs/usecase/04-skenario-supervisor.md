# SKENARIO USE CASE — MODUL SUPERVISOR

---

## UC-SPV-01 — Daftar & Detail Laporan Harian

| Atribut | Keterangan |
|---|---|
| ID | UC-SPV-01 |
| Nama | Lihat Daftar & Detail Laporan Harian |
| Aktor | Supervisor |
| Tujuan | Meninjau laporan harian Teller untuk keperluan validasi. |
| Trigger | Supervisor membuka menu laporan harian. |

**Prakondisi**
1. Supervisor terautentikasi dengan peran `supervisor`.

**Kondisi Akhir**
- Daftar/detail laporan ditampilkan beserta perbandingan kas dan audit log.

**Skenario Utama**
1. Supervisor membuka daftar laporan harian.
2. Supervisor menerapkan filter status/tanggal.
3. Supervisor memilih salah satu laporan.
4. Sistem memuat detail laporan, transaksi terkait, dan audit log.
5. Sistem menampilkan perbandingan saldo sistem, uang fisik, dan selisih kas.

**Skenario Alternatif/Eksepsi**
- **A1 — Tidak ada laporan**: Sistem menampilkan daftar kosong.

**Kebutuhan Data**
- `daily_reports`, `transactions`, `audit_logs`, `cash_transfers`, `users`.

---

## UC-SPV-02 — Approval Laporan Harian

| Atribut | Keterangan |
|---|---|
| ID | UC-SPV-02 |
| Nama | Approval Laporan Harian & Pemindahan Kas |
| Aktor | Supervisor |
| Tujuan | Menyetujui/menolak laporan harian dan memindahkan kas ke brankas utama. |
| Trigger | Supervisor membuka detail laporan berstatus `submitted`. |

**Prakondisi**
1. Supervisor terautentikasi.
2. Laporan berstatus `submitted`.

**Kondisi Akhir**
- Laporan berstatus `approved`/`rejected`; kas dipindahkan ke brankas (bila disetujui); jurnal pemindahan dibuat.

**Skenario Utama (Setujui)**
1. Supervisor membuka detail laporan `submitted`.
2. Supervisor memeriksa transaksi, total kas, laporan, dan bukti fisik.
3. Supervisor memilih **Setujui**.
4. Sistem memeriksa bahwa Supervisor bukan Teller pembuat laporan.
5. Sistem memeriksa selisih kas = 0.
6. Sistem mengubah status laporan menjadi `approved` dan mencatat approver serta waktu.
7. Sistem mencatat pemindahan kas (`CashTransfer`) dari **Laci Teller** ke **Brankas Utama**.
8. Sistem membuat jurnal pemindahan: **Debet Kas Brankas (102)** dan **Kredit Kas Teller (101)**.
9. Sistem mencatat audit `daily_report_approved` dan `cash_transferred`.
10. Sistem mengarsipkan laporan.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 4) — Supervisor adalah Teller pembuat**: Sistem menolak (tidak boleh menyetujui laporan sendiri).
- **A2 (langkah 5) — Terdapat selisih kas**: Sistem menolak approval; laporan harus ditolak dan Teller menghitung ulang atau diselesaikan via koreksi transaksi.
- **A3 — Saldo kas laci tidak mencukupi jurnal**: Sistem menolak pemindahan kas.
- **A4 — Tolak laporan**: Supervisor mengisi catatan penolakan; status menjadi `rejected` dan Teller dapat memperbaiki.

**Aturan Bisnis**
- Teller tidak dapat menyetujui laporan miliknya sendiri.
- Laporan berselisih tidak dapat disetujui.
- Pemindahan kas hanya setelah approval Supervisor.
- Approval memindahkan tanggung jawab kas dari laci Teller ke brankas utama.

**Kebutuhan Data**
- `daily_reports`: `status`, `approved_by`, `approved_at`, `approval_notes`.
- `cash_transfers`: `report_id`, `supervisor_id`, `amount`, `source_location`, `destination_location`, `status`, `transferred_at`.
- `jurnal_akuntansi`, `audit_logs`.

---

## UC-SPV-03 — Koreksi/Pembalik Transaksi

| Atribut | Keterangan |
|---|---|
| ID | UC-SPV-03 |
| Nama | Koreksi/Pembalik Transaksi (Reversal) |
| Aktor | Supervisor |
| Tujuan | Mengoreksi transaksi sukses dengan jurnal pembalik dan pemulihan saldo. |
| Trigger | Supervisor memilih transaksi sukses pada halaman laporan/detail. |

**Prakondisi**
1. Supervisor terautentikasi.
2. Transaksi berstatus `success` dan bertipe setoran, penarikan, atau transfer.
3. Laporan harian tanggal transaksi **belum** disetujui.

**Kondisi Akhir**
- Transaksi asal bertanda `reversed`; transaksi pembalik baru terbentuk; saldo dipulihkan; jurnal pembalik dibuat.

**Skenario Utama**
1. Supervisor memilih transaksi sukses untuk dikoreksi.
2. Sistem mengotorisasi aksi reverse.
3. Sistem memastikan transaksi bukan transaksi reversal (tidak dapat dikoreksi ulang).
4. Sistem memastikan laporan harian tanggal tersebut belum disetujui.
5. Sistem mengunci rekening terkait secara berurutan (menaik).
6. Sistem menghitung pemulihan saldo sesuai jenis transaksi:
   - Setoran → saldo berkurang sebesar nominal.
   - Penarikan → saldo bertambah sebesar nominal + biaya admin.
   - Transfer → saldo pengirim bertambah, saldo penerima berkurang.
7. Sistem membuat transaksi `reversal` dan jurnal pembalik.
8. Sistem menandai transaksi asal `reversed` beserta `reversed_at`, `reversed_by`, dan `reversal_reason`.
9. Sistem mencatat audit `transaction_reversed`.
10. Sistem menampilkan hasil koreksi.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Sudah berupa reversal**: Sistem menolak ("tidak dapat dikoreksi ulang").
- **A4 (langkah 4) — Laporan sudah approved**: Sistem menolak karena kas telah dipindahkan ke brankas.
- **A5 (langkah 6) — Saldo tidak mencukupi untuk membatalkan setoran/transfer**: Sistem menolak koreksi.
- **A6 — Alasan kosong**: Sistem menolak (alasan wajib).

**Aturan Bisnis**
- Transaksi yang sudah dikoreksi tidak dapat dikoreksi ulang.
- Koreksi ditolak bila laporan tanggal transaksi telah disetujui.
- Koreksi menghasilkan jurnal pembalik yang seimbang dan memulihkan saldo.

**Kebutuhan Data**
- `transactions`: `reversal_of_transaction_id`, `reversed_at`, `reversed_by`, `reversal_reason`, `status`, `type=reversal`.
- `accounts`, `jurnal_akuntansi`, `daily_reports`, `audit_logs`.

---

## UC-SPV-04 — Laporan Akuntansi Supervisor

| Atribut | Keterangan |
|---|---|
| ID | UC-SPV-04 |
| Nama | Laporan Akuntansi (Buku Besar, Neraca Saldo, Laba Rugi, Neraca) |
| Aktor | Supervisor |
| Tujuan | Meninjau laporan akuntansi lengkap. |
| Trigger | Supervisor membuka menu laporan akuntansi. |

**Prakondisi**
1. Supervisor terautentikasi.

**Kondisi Akhir**
- Laporan akuntansi ditampilkan/diekspor.

**Skenario Utama**
1. Supervisor membuka menu laporan akuntansi.
2. Supervisor memilih jenis laporan.
3. Sistem menghitung nilai dari jurnal akuntansi.
4. Sistem menampilkan Buku Besar per akun / Neraca Saldo / Laba Rugi / Neraca.
5. Supervisor dapat mengekspor laporan ke CSV.

**Skenario Alternatif/Eksepsi**
- **A1 — Tidak seimbang**: Sistem menandai laporan tidak seimbang.

**Kebutuhan Data**
- `jurnal_akuntansi`.
