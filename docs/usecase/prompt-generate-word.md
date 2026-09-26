# PROMPT SIAP PAKAI — GENERATE DOKUMEN WORD

Gunakan berkas ini sebagai *prompt* ke asisten AI (mis. ChatGPT/Claude/Copilot) untuk
menghasilkan dokumen Word (`.docx`) berisi skenario use case. Semua konten sumber berada
pada berkas teks di folder `docs/usecase/` dan `docs/activity-diagrams/`.

---

## CARA PAKAI

1. Buka berkas teks sumber pada folder `docs/usecase/` (00 s.d. 06).
2. Salin seluruh isinya.
3. Tempel ke AI bersama **PROMPT INTI** di bawah.
4. Minta AI mengembalikan dokumen dalam format yang diminta, lalu simpan sebagai `.docx`.

---

## PROMPT INTI (salin mulai baris ini)

> Anda adalah asisten penyusun dokumen teknis. Susun sebuah dokumen Microsoft Word
> berjudul **"Skenario Use Case — Sistem Informasi E-Teller Bank Mini Sekolah"**
> berdasarkan seluruh konten yang saya sertakan di bawah.
>
> **Ketentuan format dokumen:**
> 1. **Halaman Judul**: judul dokumen, jenis dokumen, nama sistem, arsitektur, teknologi, versi, dan tanggal.
> 2. **Daftar Isi** otomatis (Table of Contents) dengan heading bernomor.
> 3. **Heading**:
>    - Heading 1: Bab (mis. "BAB 1 PENDAHULUAN", "BAB 2 SKENARIO USE CASE AUTENTIKASI", dst.).
>    - Heading 2: Use Case (mis. "UC-AUTH-01 Login Multi-Role").
>    - Heading 3: Subbagian skenario (Prakondisi, Skenario Utama, Skenario Alternatif/Eksepsi, Aturan Bisnis, Kebutuhan Data, Catatan Non-fungsional).
> 4. **Tabel atribut use case** di awal setiap use case dengan 2 kolom: Atribut | Keterangan
>    (ID, Nama, Aktor, Tujuan, Trigger). Gunakan gaya tabel grid, header berlatar abu-abu, font 11pt.
> 5. **Skenario Utama** dan **Skenario Alternatif/Eksepsi** ditulis sebagai daftar bernomor.
> 6. **Matriks Otorisasi Role** disajikan sebagai tabel (baris = fungsi, kolom = Admin/Teller/Supervisor/Nasabah, isi Ya/Tidak).
> 7. **Kamus Data** disajikan sebagai tabel per entitas.
> 8. Setiap bab modul disusun urut: Autentikasi, Administrator, Teller, Supervisor, Nasabah, lalu Aturan Bisnis & Kamus Data.
> 9. Cantumkan satu paragraf pengantar singkat di awal setiap bab modul.
> 10. Nomor halaman di footer; nama dokumen di header.
> 11. Bahasa Indonesia baku, tanpa mengubah substansi teknis.
> 12. Pertahankan seluruh ID use case (UC-...) dan kode aturan bisnis (BR-...) apa adanya.
>
> **Struktur bab yang diinginkan:**
> - BAB 1 PENDAHULUAN (tujuan, ruang lingkup, identifikasi aktor, daftar use case, matriks otorisasi, konvensi)
> - BAB 2 SKENARIO USE CASE AUTENTIKASI & KEAMANAN AKSES (UC-AUTH-01 s.d. 04)
> - BAB 3 SKENARIO USE CASE ADMINISTRATOR (UC-ADM-01 s.d. 07)
> - BAB 4 SKENARIO USE CASE TELLER (UC-TLR-01 s.d. 09)
> - BAB 5 SKENARIO USE CASE SUPERVISOR (UC-SPV-01 s.d. 04)
> - BAB 6 SKENARIO USE CASE NASABAH (UC-NSB-01 s.d. 04)
> - BAB 7 ATURAN BISNIS GLOBAL, KAMUS DATA, DAN KEBUTUHAN NON-FUNGSIONAL
>
> **Lampiran (opsional):** daftar 28 activity diagram beserta nama berkas `.drawio`-nya.
>
> Kembalikan hasil sebagai dokumen Word. Bila tidak dapat mengeluarkan `.docx` langsung,
> hasilkan kode Python `python-docx` yang menghasilkan dokumen tersebut, atau format
> Markdown siap konversi dengan panduan heading yang sama.

---

## KONTEN SUMBER (tempel di bawah prompt)

Tempel seluruh isi berkas berikut secara berurutan:

1. `docs/usecase/00-pendahuluan-aktor.md`
2. `docs/usecase/01-skenario-auth.md`
3. `docs/usecase/02-skenario-admin.md`
4. `docs/usecase/03-skenario-teller.md`
5. `docs/usecase/04-skenario-supervisor.md`
6. `docs/usecase/05-skenario-nasabah.md`
7. `docs/usecase/06-aturan-bisnis-kamus-data.md`

---

## CONTOH PROMPT SINGKAT (versi ringkas)

> Ubah berkas teks skenario use case Sistem Informasi E-Teller Bank Mini Sekolah yang saya lampirkan menjadi dokumen Word (.docx) yang rapi. Gunakan halaman judul, daftar isi otomatis, Heading 1 untuk bab, Heading 2 untuk setiap use case (UC-...), Heading 3 untuk subbagian, tabel untuk atribut use case dan matriks otorisasi, serta daftar bernomor untuk skenario. Pertahankan semua ID use case dan kode BR. Bahasa Indonesia. Sertakan lampiran daftar activity diagram.

---

## PEMETAAN ACTIVITY DIAGRAM (untuk lampiran)

| Kode | Use Case | Berkas Diagram |
|---|---|---|
| UC-AUTH-01 | Login Multi-Role | `docs/activity-diagrams/01-login-multi-role.drawio` |
| UC-AUTH-02 | Logout | `docs/activity-diagrams/02-logout.drawio` |
| UC-AUTH-03 | Lupa Password Pegawai | `docs/activity-diagrams/03-lupa-password-pegawai.drawio` |
| UC-AUTH-04 | Reset PIN Nasabah | `docs/activity-diagrams/04-reset-pin-nasabah.drawio` |
| UC-ADM-01 | Kelola Akun Internal | `docs/activity-diagrams/05-kelola-akun-internal.drawio` |
| UC-ADM-02 | Kelola Profil Nasabah | `docs/activity-diagrams/06-kelola-profil-nasabah.drawio` |
| UC-ADM-03 | Import Nasabah CSV | `docs/activity-diagrams/07-import-nasabah-csv.drawio` |
| UC-ADM-04 | Detail Rekening & Cetak Kartu | `docs/activity-diagrams/08-detail-rekening-kartu.drawio` |
| UC-ADM-05 | Konfigurasi Sistem | `docs/activity-diagrams/09-konfigurasi-sistem.drawio` |
| UC-ADM-06 | Audit Jurnal & Buku Besar | `docs/activity-diagrams/10-audit-jurnal-buku-besar.drawio` |
| UC-ADM-07 | Laporan Keuangan | `docs/activity-diagrams/11-laporan-keuangan.drawio` |
| UC-TLR-01 | Pencarian Nasabah | `docs/activity-diagrams/12-pencarian-nasabah.drawio` |
| UC-TLR-02 | Registrasi Nasabah | `docs/activity-diagrams/13-registrasi-nasabah.drawio` |
| UC-TLR-03 | Setoran Tunai | `docs/activity-diagrams/14-setoran-tunai.drawio` |
| UC-TLR-04 | Penarikan Tunai | `docs/activity-diagrams/15-penarikan-tunai.drawio` |
| UC-TLR-05 | Transfer Antar Rekening | `docs/activity-diagrams/16-transfer-antar-rekening.drawio` |
| UC-TLR-06 | Cetak Struk | `docs/activity-diagrams/17-cetak-struk.drawio` |
| UC-TLR-07 | Cetak Buku Tabungan | `docs/activity-diagrams/18-cetak-buku-tabungan.drawio` |
| UC-TLR-08 | Penutupan Kas | `docs/activity-diagrams/19-penutupan-kas.drawio` |
| UC-TLR-09 | Cetak Laporan Harian | `docs/activity-diagrams/20-cetak-laporan-harian.drawio` |
| UC-SPV-01 | Daftar & Detail Laporan | `docs/activity-diagrams/21-detail-laporan-supervisor.drawio` |
| UC-SPV-02 | Approval Laporan | `docs/activity-diagrams/22-approval-laporan.drawio` |
| UC-SPV-03 | Koreksi/Pembalik | `docs/activity-diagrams/23-koreksi-pembalik.drawio` |
| UC-SPV-04 | Laporan Akuntansi Supervisor | `docs/activity-diagrams/24-supervisor-laporan-akuntansi.drawio` |
| UC-NSB-01 | Lihat Saldo | `docs/activity-diagrams/25-nasabah-saldo.drawio` |
| UC-NSB-02 | Riwayat Mutasi | `docs/activity-diagrams/26-nasabah-mutasi.drawio` |
| UC-NSB-03 | e-Statement | `docs/activity-diagrams/27-nasabah-rekening-koran.drawio` |
| UC-NSB-04 | Kartu Virtual | `docs/activity-diagrams/28-nasabah-kartu-virtual.drawio` |
