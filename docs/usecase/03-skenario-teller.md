# SKENARIO USE CASE — MODUL TELLER

---

## UC-TLR-01 — Pencarian Nasabah

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-01 |
| Nama | Pencarian Nasabah |
| Aktor | Teller |
| Tujuan | Menemukan profil nasabah dan rekening dengan cepat di loket. |
| Trigger | Teller membuka menu pencarian nasabah. |

**Prakondisi**
1. Teller terautentikasi dengan peran `teller`.

**Kondisi Akhir**
- Profil nasabah ditampilkan atau daftar hasil/pesan tidak ditemukan ditampilkan.

**Skenario Utama**
1. Teller membuka menu pencarian nasabah.
2. Teller memindai QR Code atau memasukkan nomor rekening, NIS, atau nama.
3. Sistem mencari kecocokan **exact** berdasarkan `account_number`.
4. Jika tidak ada, sistem mencari kecocokan **exact** berdasarkan `qr_code_value`.
5. Jika tidak ada, sistem melakukan pencarian parsial (`like`) pada nomor rekening, NIS, nama, dan kelas (maksimal 25 hasil).
6. Bila tepat satu hasil, sistem mengarahkan ke halaman detail nasabah.
7. Bila lebih dari satu, sistem menampilkan daftar hasil.
8. Teller memilih salah satu hasil untuk melihat detail dan saldo.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 2) — Query kosong**: Sistem menampilkan pesan agar mengisi kata kunci.
- **A2 (langkah 5) — Tidak ada hasil**: Sistem menampilkan pesan "Nasabah tidak ditemukan".
- **A3 — Nasabah lain**: Sistem hanya menampilkan hasil pencarian yang sah.

**Aturan Bisnis**
- Pencarian harus cepat pada kondisi operasional loket.
- Sistem tidak menampilkan data nasabah di luar hasil pencarian.

**Kebutuhan Data**
- `accounts`: `account_number`, `balance`, `status`.
- `customers`: `student_number`, `full_name`, `class_name`, `qr_code_value`, `status`.

---

## UC-TLR-02 — Registrasi Nasabah Didampingi Teller

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-02 |
| Nama | Registrasi Nasabah Didampingi Teller |
| Aktor | Teller |
| Tujuan | Mendaftarkan nasabah baru langsung di loket. |
| Trigger | Teller membuka form registrasi nasabah. |

**Prakondisi**
1. Teller terautentikasi dengan peran `teller`.

**Kondisi Akhir**
- Profil nasabah dan rekening baru terbentuk; NIS dan nomor rekening ditampilkan.

**Skenario Utama**
1. Teller membuka form registrasi nasabah.
2. Teller mengisi `full_name`, `class_name`, `phone_number`, dan `pin` (6 digit).
3. Sistem memvalidasi input.
4. Sistem membuat NIS otomatis (`tahun` + urutan) yang dijamin unik.
5. Sistem membuat profil nasabah beserta `qr_code_value` (UUID).
6. Sistem membuka rekening otomatis (nomor rekening format tahun ajaran, saldo awal 0).
7. Sistem mencatat audit `customer_registered`.
8. Sistem menampilkan hasil registrasi (nama, NIS, nomor rekening).

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Validasi gagal**: Sistem menampilkan pesan error (PIN bukan 6 digit, field kosong).
- **A2 — Tabrakan NIS**: Sistem menghasilkan kandidat NIS baru hingga unik.

**Aturan Bisnis**
- Nomor siswa dan nomor rekening dibuat otomatis oleh sistem.
- Satu nasabah memiliki satu rekening aktif.

**Kebutuhan Data**
- `customers`, `accounts`, `audit_logs`.

---

## UC-TLR-03 — Transaksi Setoran Tunai

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-03 |
| Nama | Transaksi Setoran Tunai |
| Aktor | Teller (utama), Nasabah (pendamping) |
| Tujuan | Menyimpan setoran tunai, menambah saldo, dan membuat jurnal. |
| Trigger | Teller membuka modul setoran untuk rekening nasabah. |

**Prakondisi**
1. Teller terautentikasi dengan peran `teller`.
2. Rekening nasabah ditemukan dan berstatus `active`; nasabah tidak diblokir.
3. Slip setoran lengkap.

**Kondisi Akhir**
- Transaksi setoran tersimpan berstatus `success`; saldo bertambah; dua baris jurnal dibuat; struk dicetak.

**Skenario Utama**
1. Teller membuka modul setoran.
2. Teller memindai QR Code atau memasukkan nomor rekening.
3. Sistem menampilkan profil nasabah dan saldo berjalan.
4. Teller memeriksa kelengkapan slip setoran.
5. Teller memasukkan nominal slip, nominal uang fisik, dan rincian pecahan.
6. Sistem memvalidasi nominal > 0 dan kecocokan nominal slip dengan uang fisik.
7. Teller mengonfirmasi transaksi.
8. Sistem memulai database transaction dan mengunci rekening (`lockForUpdate`).
9. Sistem menyimpan transaksi setoran.
10. Sistem menambah saldo rekening.
11. Sistem membuat dua baris jurnal: **Debet Kas (101)** dan **Kredit Tabungan (201)**.
12. Sistem memvalidasi jurnal seimbang.
13. Sistem melakukan `COMMIT`.
14. Sistem mencatat audit sukses dan mengunci transaksi.
15. Sistem menampilkan dan mencetak struk setoran.
16. Teller menyerahkan buku tabungan dan struk kepada nasabah.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 6) — Nominal slip ≠ uang fisik**: Sistem menolak penyimpanan, mencatat penolakan, dan Teller meminta konfirmasi nasabah.
- **A2 (langkah 6) — Nominal ≤ 0**: Sistem menolak transaksi.
- **A3 (langkah 12) — Jurnal tidak seimbang / gagal simpan**: Sistem melakukan `ROLLBACK` dan menampilkan kegagalan.
- **A4 — Rekening tidak aktif / nasabah diblokir**: Sistem menolak transaksi.

**Aturan Bisnis**
- Nominal transaksi harus > Rp0.
- Transaksi setoran hanya tersimpan bila nominal slip = uang fisik.
- Setiap transaksi menghasilkan tepat dua baris jurnal seimbang.
- Transaksi final tidak dapat diubah/dihapus Teller.

**Kebutuhan Data**
- `transactions`: `receipt_number`, `account_id`, `teller_id`, `type=deposit`, `slip_number`, `slip_amount`, `physical_cash_amount`, `denomination_breakdown`, `amount`, `balance_before`, `balance_after`, `status`, `transaction_at`.
- `accounts`, `jurnal_akuntansi`, `audit_logs`.

---

## UC-TLR-04 — Transaksi Penarikan Tunai

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-04 |
| Nama | Transaksi Penarikan Tunai |
| Aktor | Teller (utama), Nasabah (otorisasi PIN) |
| Tujuan | Memproses penarikan tunai dengan validasi saldo, biaya admin, dan PIN. |
| Trigger | Teller membuka modul penarikan untuk rekening nasabah. |

**Prakondisi**
1. Teller terautentikasi dengan peran `teller`.
2. Rekening aktif dan nasabah tidak diblokir.
3. Nasabah siap memasukkan PIN 6 digit.

**Kondisi Akhir**
- Transaksi penarikan tersimpan `success`; saldo berkurang; jurnal (termasuk biaya admin) dibuat; struk dicetak.

**Skenario Utama**
1. Teller membuka modul penarikan dan memilih rekening.
2. Sistem menampilkan profil dan saldo nasabah.
3. Teller memeriksa kelengkapan slip penarikan dan memasukkan nominal penarikan.
4. Sistem memeriksa status rekening dan kecukupan saldo.
5. Sistem memeriksa batas penarikan tanpa pesanan.
6. Sistem menghitung saldo setelah penarikan termasuk biaya administrasi.
7. Sistem memastikan saldo setelah penarikan tidak kurang dari saldo mengendap minimum.
8. Nasabah memasukkan PIN 6 digit.
9. Sistem memverifikasi PIN (hash).
10. Teller mengonfirmasi transaksi.
11. Sistem memulai database transaction dan mengunci rekening.
12. Sistem mengurangi saldo rekening.
13. Sistem menyimpan transaksi dan membuat jurnal: **Debet Tabungan (201)**, **Kredit Kas (101)**, dan **Kredit Pendapatan Admin (401)** bila ada biaya.
14. Sistem memvalidasi jurnal seimbang lalu `COMMIT`.
15. Sistem mencatat audit sukses dan mencetak struk penarikan.
16. Teller menyerahkan uang tunai, buku tabungan, dan struk kepada nasabah.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 4) — Saldo tidak mencukupi**: Sistem menolak dan menampilkan pesan.
- **A2 (langkah 5) — Melebihi batas tanpa pesanan**: Sistem menolak transaksi.
- **A3 (langkah 7) — Saldo < saldo minimum**: Sistem menolak transaksi.
- **A4 (langkah 9) — PIN salah**: Sistem mencatat percobaan; setelah **3x** gagal, rekening dikunci **15 menit** dan dicatat pada audit log.
- **A5 (langkah 14) — Jurnal tidak seimbang / gagal simpan**: Sistem `ROLLBACK`.

**Aturan Bisnis**
- Penarikan hanya bila saldo mencukupi dan saldo setelah penarikan ≥ Rp10.000 (saldo mengendap minimum).
- PIN nasabah tepat 6 digit dan diverifikasi sebelum penarikan.
- Biaya administrasi dicatat sebagai pendapatan (akun 401).
- Transaksi final tidak dapat diubah Teller.

**Kebutuhan Data**
- `transactions`: `type=withdrawal`, `amount`, `admin_fee`, `denomination_breakdown`, `balance_before`, `balance_after`, `status`.
- `customers`: `pin_hash`, `failed_pin_attempts`, `locked_until`.
- `system_configs`: `minimum_balance`, `admin_fee`, `max_withdrawal_without_order`.
- `accounts`, `jurnal_akuntansi`, `audit_logs`.

---

## UC-TLR-05 — Transfer Antar Rekening

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-05 |
| Nama | Transfer Antar Rekening (P2P) |
| Aktor | Teller (utama), Nasabah (otorisasi PIN) |
| Tujuan | Memindahkan saldo antar rekening nasabah dengan jurnal ganda. |
| Trigger | Teller membuka modul transfer. |

**Prakondisi**
1. Teller terautentikasi dengan peran `teller`.
2. Rekening pengirim dan penerima aktif dan berbeda.
3. Nasabah pengirim siap memasukkan PIN.

**Kondisi Akhir**
- Saldo pengirim berkurang, saldo penerima bertambah; jurnal 201↔201 dibuat; struk bukti transfer dicetak.

**Skenario Utama**
1. Teller membuka modul transfer dan memilih rekening sumber.
2. Teller memasukkan nomor rekening tujuan dan nominal transfer.
3. Sistem memvalidasi rekening tujuan berbeda dan aktif.
4. Nasabah pengirim memasukkan PIN 6 digit.
5. Sistem memverifikasi PIN.
6. Sistem memulai database transaction dan mengunci kedua rekening **berdasarkan urutan ID menaik** (pencegahan deadlock).
7. Sistem memeriksa saldo pengirim dan saldo minimum.
8. Sistem mengurangi saldo pengirim dan menambah saldo penerima.
9. Sistem membuat jurnal: **Debet Tabungan (201) pengirim** dan **Kredit Tabungan (201) penerima**.
10. Sistem memvalidasi jurnal seimbang lalu `COMMIT`.
11. Sistem mencatat audit sukses dan mencetak struk bukti transfer.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Rekening sama / tidak aktif**: Sistem menolak transaksi.
- **A2 (langkah 5) — PIN salah**: Sistem menolak (mengikuti aturan penguncian 3x/15 menit).
- **A3 (langkah 7) — Saldo tidak mencukupi / < minimum**: Sistem `ROLLBACK` dan menolak.
- **A4 (langkah 10) — Jurnal tidak seimbang**: Sistem `ROLLBACK`.

**Aturan Bisnis**
- Rekening tujuan tidak boleh sama dengan pengirim.
- Transfer menghasilkan dua baris jurnal 201↔201 yang seimbang.
- Penguncian rekening berurutan untuk mencegah deadlock.

**Kebutuhan Data**
- `transactions`: `type=transfer`, `recipient_account_id`, `amount`, `balance_before`, `balance_after`.
- `accounts`, `jurnal_akuntansi`, `audit_logs`.

---

## UC-TLR-06 — Cetak Struk Transaksi

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-06 |
| Nama | Cetak Struk Transaksi |
| Aktor | Teller |
| Tujuan | Menampilkan dan mencetak struk transaksi lengkap. |
| Trigger | Teller membuka hasil transaksi. |

**Prakondisi**
1. Transaksi berhasil tersimpan.

**Kondisi Akhir**
- Struk ditampilkan dan dicetak.

**Skenario Utama**
1. Teller membuka halaman hasil transaksi.
2. Sistem mengotorisasi akses terhadap transaksi.
3. Sistem memuat relasi (nasabah, teller, jurnal, transaksi koreksi terkait).
4. Sistem merender struk lengkap: nama bank, nomor struk, nomor referensi jurnal, tanggal & waktu, jenis transaksi, nomor rekening, nama nasabah, NIS, nomor slip, nama teller, nominal, biaya admin, total, terbilang, saldo sebelum, saldo sesudah, dan status.
5. Sistem mengirim ke printer dan mencetak struk.

**Skenario Alternatif/Eksepsi**
- **A1 — Akses ditolak**: Sistem menolak akses (403) bila transaksi tidak berhak diakses.

**Kebutuhan Data**
- `transactions`, `accounts`, `customers`, `users`, `jurnal_akuntansi`.

---

## UC-TLR-07 — Cetak Buku Tabungan (Passbook)

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-07 |
| Nama | Cetak Buku Tabungan (Passbook Printing) |
| Aktor | Teller |
| Tujuan | Mencetak mutasi ke halaman fisik buku tabungan. |
| Trigger | Teller memilih rekening dan membuka menu buku tabungan. |

**Prakondisi**
1. Teller terautentikasi; rekening nasabah tersedia.

**Kondisi Akhir**
- Halaman buku tabungan dirender dan dicetak sesuai posisi baris.

**Skenario Utama**
1. Teller memilih rekening nasabah.
2. Sistem menampilkan transaksi berstatus `success` dengan paginasi.
3. Teller memilih baris awal per halaman (1–20).
4. Sistem mengambil transaksi sesuai halaman dan baris awal.
5. Sistem merender halaman buku tabungan.
6. Sistem mencetak buku tabungan.

**Skenario Alternatif/Eksepsi**
- **A1 — Tidak ada transaksi**: Sistem menampilkan halaman kosong.

**Kebutuhan Data**
- `transactions`, `accounts`, `customers`.

---

## UC-TLR-08 — Penutupan Kas (Rekonsiliasi Harian)

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-08 |
| Nama | Penutupan Kas / Rekonsiliasi Harian |
| Aktor | Teller |
| Tujuan | Menutup kas harian dan merekonsiliasi saldo sistem dengan uang fisik. |
| Trigger | Teller membuka modul penutupan kas. |

**Prakondisi**
1. Teller terautentikasi.
2. Seluruh transaksi hari kerja telah selesai.

**Kondisi Akhir**
- Laporan harian berstatus `submitted` tersimpan dan draf dicetak.

**Skenario Utama**
1. Teller membuka modul penutupan kas dan memilih tanggal kerja.
2. Sistem mengambil seluruh transaksi sukses Teller pada tanggal tersebut.
3. Sistem menghitung total setoran, total penarikan, dan total biaya admin.
4. Sistem menghitung saldo akhir: **Saldo Awal + Total Setoran − Total Penarikan**.
5. Teller memasukkan total uang fisik dan rincian pecahan.
6. Sistem menghitung selisih: **Uang Fisik − Saldo Sistem**.
7. Bila selisih = 0, sistem menyimpan laporan berstatus `submitted`.
8. Sistem mengunci cetak laporan final bila terdapat selisih.
9. Sistem mencetak draf Laporan Harian Teller.
10. Teller menyerahkan laporan kepada Supervisor.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 6) — Terdapat selisih**: Sistem mewajibkan berita acara selisih (minimal 10 karakter) agar laporan dapat disimpan untuk diperiksa Supervisor.
- **A2 (langkah 7) — Berita acara tidak valid**: Sistem menolak penyimpanan.
- **A3 — Laporan tanggal sama sudah ada**: Sistem menolak pembuatan laporan ganda.

**Aturan Bisnis**
- Laporan final tidak dapat dicetak sebagai laporan final bila terjadi selisih kas.
- Satu Teller satu laporan per tanggal.
- Koreksi memengaruhi rekap kas (penarikan yang dikoreksi menambah kas laci; setoran yang dikoreksi mengurangi kas laci).

**Kebutuhan Data**
- `daily_reports`: `report_number`, `teller_id`, `report_date`, `opening_balance`, `total_deposit`, `total_withdrawal`, `total_admin_fee`, `closing_balance_system`, `closing_balance_physical`, `closing_breakdown`, `cash_difference`, `status`, `submitted_at`, `approval_notes`.
- `transactions`, `audit_logs`.

---

## UC-TLR-09 — Cetak Laporan Harian Teller

| Atribut | Keterangan |
|---|---|
| ID | UC-TLR-09 |
| Nama | Cetak Laporan Harian Teller |
| Aktor | Teller |
| Tujuan | Mencetak Laporan Harian Teller beserta rincian pecahan. |
| Trigger | Teller membuka laporan yang telah dibuat. |

**Prakondisi**
1. Laporan harian telah dibuat.

**Kondisi Akhir**
- Laporan harian dirender dan dicetak.

**Skenario Utama**
1. Teller membuka laporan yang telah dibuat.
2. Sistem mengotorisasi akses laporan.
3. Sistem memuat data teller, approver, dan rincian pecahan.
4. Sistem merender Laporan Harian Teller.
5. Sistem mencetak laporan.

**Skenario Alternatif/Eksepsi**
- **A1 — Laporan tidak ditemukan**: Sistem menampilkan pesan kesalahan.

**Kebutuhan Data**
- `daily_reports`, `users`, `transactions`.
