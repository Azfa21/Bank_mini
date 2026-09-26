# SKENARIO USE CASE — MODUL NASABAH

---

## UC-NSB-01 — Lihat Saldo

| Atribut | Keterangan |
|---|---|
| ID | UC-NSB-01 |
| Nama | Lihat Saldo Rekening |
| Aktor | Nasabah / Siswa |
| Tujuan | Melihat saldo berjalan dan transaksi terakhir rekening sendiri. |
| Trigger | Nasabah membuka dasbor. |

**Prakondisi**
1. Nasabah terautentikasi melalui guard `nasabah`.
2. Nasabah memiliki rekening aktif.

**Kondisi Akhir**
- Saldo dan transaksi terakhir ditampilkan.

**Skenario Utama**
1. Nasabah login dan membuka dasbor.
2. Sistem mengambil rekening milik nasabah yang sedang login.
3. Sistem mengambil saldo berjalan.
4. Sistem mengambil transaksi terakhir.
5. Sistem menampilkan dasbor saldo.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 2) — Tidak punya rekening**: Sistem menampilkan pesan belum ada rekening aktif.

**Aturan Bisnis**
- Nasabah hanya dapat melihat data rekening miliknya sendiri.

**Kebutuhan Data**
- `customers`, `accounts` (`balance`), `transactions`.

---

## UC-NSB-02 — Riwayat Mutasi

| Atribut | Keterangan |
|---|---|
| ID | UC-NSB-02 |
| Nama | Riwayat Mutasi Rekening |
| Aktor | Nasabah |
| Tujuan | Melihat seluruh setoran, penarikan, dan transfer rekening sendiri. |
| Trigger | Nasabah membuka menu riwayat mutasi. |

**Prakondisi**
1. Nasabah terautentikasi dan memiliki rekening.

**Kondisi Akhir**
- Daftar mutasi (tanggal, jenis, nominal, saldo akhir) ditampilkan dengan paginasi.

**Skenario Utama**
1. Nasabah membuka menu riwayat mutasi.
2. Nasabah menerapkan filter jenis transaksi dan/atau rentang tanggal.
3. Sistem memvalidasi bahwa data yang diminta milik nasabah tersebut.
4. Sistem mengambil transaksi di mana rekening nasabah sebagai pengirim atau penerima.
5. Sistem melakukan paginasi hasil (15 per halaman).
6. Sistem menampilkan tanggal, jenis transaksi, nominal, dan saldo akhir.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 3) — Upaya akses rekening lain**: Sistem menolak akses.

**Aturan Bisnis**
- Nasabah tidak dapat mengakses rekening, profil, atau mutasi nasabah lain.
- Riwayat besar ditampilkan dengan paginasi.

**Kebutuhan Data**
- `transactions` (`account_id`, `recipient_account_id`, `type`, `amount`, `balance_after`, `transaction_at`, `status`), `accounts`.

---

## UC-NSB-03 — Cetak e-Statement (Rekening Koran)

| Atribut | Keterangan |
|---|---|
| ID | UC-NSB-03 |
| Nama | Cetak/e-Statement Rekening Koran |
| Aktor | Nasabah |
| Tujuan | Menghasilkan rekening koran resmi untuk periode tertentu. |
| Trigger | Nasabah membuka menu rekening koran. |

**Prakondisi**
1. Nasabah terautentikasi dan memiliki rekening.

**Kondisi Akhir**
- e-Statement dengan ringkasan, kode verifikasi, dan watermark dirender/dicetak.

**Skenario Utama**
1. Nasabah membuka menu rekening koran.
2. Nasabah memilih rentang tanggal (default awal bulan s/d hari ini).
3. Sistem mengambil transaksi dan menyusun ringkasan (total setoran, penarikan, transfer masuk/keluar).
4. Sistem menghitung kode verifikasi.
5. Sistem merender e-Statement dengan watermark.
6. Nasabah mencetak/menyimpan PDF.

**Skenario Alternatif/Eksepsi**
- **A1 — Rekening tidak ditemukan**: Sistem menampilkan kesalahan 404.

**Aturan Bisnis**
- Rekening koran hanya memuat transaksi milik nasabah.
- Kode verifikasi dihitung dari kombinasi identitas dan ringkasan.

**Kebutuhan Data**
- `transactions`, `accounts`, `customers`.

---

## UC-NSB-04 — Cetak Kartu Nasabah Virtual

| Atribut | Keterangan |
|---|---|
| ID | UC-NSB-04 |
| Nama | Cetak Kartu Nasabah Virtual ber-QR Code |
| Aktor | Nasabah |
| Tujuan | Menampilkan dan mencetak kartu nasabah virtual dengan QR Code. |
| Trigger | Nasabah membuka dasbor dan memilih cetak kartu. |

**Prakondisi**
1. Nasabah terautentikasi dan memiliki rekening aktif.

**Kondisi Akhir**
- Kartu nasabah virtual dirender dan dicetak.

**Skenario Utama**
1. Nasabah membuka dasbor.
2. Sistem mengambil data akun dan `qr_code_value`.
3. Sistem menampilkan kartu nasabah virtual ber-QR Code.
4. Nasabah memilih cetak kartu.
5. Sistem merender kartu.
6. Sistem mencetak kartu.

**Skenario Alternatif/Eksepsi**
- **A1 (langkah 2) — Tidak punya rekening**: Sistem menampilkan pesan belum ada rekening aktif.

**Kebutuhan Data**
- `customers` (`full_name`, `student_number`, `qr_code_value`), `accounts` (`account_number`).
