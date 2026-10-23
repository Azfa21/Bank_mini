# Use Case Model — Bank Mini Sekolah

| Atribut | Keterangan |
|---|---|
| Dokumen | Spesifikasi Use Case (UML 2.5) |
| Sistem | Bank Mini Sekolah (web Laravel + aplikasi nasabah Flutter) |
| Versi | 1.0 |
| Tanggal | 22 Oktober 2026 |
| Status | Final untuk lampiran SRS |
| Berkas diagram | `task/use-case-bank-mini.drawio` (9 halaman) |
| Sumber acuan | `routes/web.php`, `routes/api.php`, `app/Http/Controllers/`, `app/Services/`, `task.md` |

---

## 1. Tujuan dan Ruang Lingkup

Dokumen ini memodelkan kebutuhan fungsional Bank Mini Sekolah dalam bentuk use case
UML 2.5. Setiap use case menggambarkan satu tujuan yang bernilai bagi aktor, bukan satu
tombol atau satu endpoint. Dokumen dipakai sebagai lampiran SRS (bagian pemodelan
kebutuhan fungsional) dan sebagai rujukan pengujian.

Ruang lingkup mencakup seluruh fitur yang benar-benar tersedia pada kode:

- Web internal untuk Administrator dan Teller.
- Web nasabah untuk penabung.
- API dan aplikasi nasabah berbasis Flutter.

Di luar ruang lingkup: proses administrasi sekolah di luar aplikasi, integrasi payment
gateway, dan notifikasi selain email kode verifikasi.

Batas sistem adalah aplikasi Bank Mini Sekolah beserta basis datanya. Petugas sekolah
(Administrator dan Teller) dan nasabah berada di luar batas sistem. Layanan email
diperlakukan sebagai aktor sekunder karena sistem menyerahkan pengiriman kode
verifikasi kepadanya.

---

## 2. Aktor

| Kode | Aktor | Jenis | Deskripsi | Kanal |
|---|---|---|---|---|
| A1 | Administrator | Primer | Mengelola akun pengguna internal, data induk anggota dan penabung, konfigurasi, otorisasi laporan kas, koreksi transaksi, laporan akuntansi, dan administrasi akademik. | Web |
| A2 | Teller | Primer | Melayani loket: identifikasi nasabah, registrasi dan aktivasi rekening, transaksi setoran/penarikan/transfer, pencetakan dokumen, penutupan rekening, dan penutupan kas harian. | Web |
| A3 | Nasabah (Penabung) | Primer | Siswa pemilik rekening. Membaca saldo, mutasi, rekening koran, dan kartu; mengubah PIN transaksi; memulihkan password. | Web dan aplikasi Flutter |
| A4 | Layanan Email | Sekunder | Sistem luar yang mengirim kode verifikasi pemulihan password ke email terdaftar. | SMTP |

Catatan:

- Peran lama `supervisor` sudah digabungkan ke Administrator, sehingga tidak lagi
  muncul sebagai aktor terpisah.
- Nasabah web dan nasabah mobile adalah aktor yang sama dengan kanal berbeda. Use case
  yang dipakai sama; yang berbeda hanya mekanisme sesi (cookie) dan token (API).
- Perintah CLI `bankmini:graduate` dijalankan operator di server, bukan aktor web, dan
  tidak dimodelkan sebagai use case.

---

## 3. Daftar Use Case

### 3.1 Autentikasi dan Keamanan Akses

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-01 | Masuk ke Aplikasi | A1, A2, A3 | — |
| UC-02 | Keluar dari Aplikasi | A1, A2, A3 | — |
| UC-03 | Meminta Kode Verifikasi Pemulihan Password | A1, A2, A3, A4 | — |
| UC-04 | Mengatur Ulang Password | A1, A2, A3 | «include» UC-03 |
| UC-05 | Mengubah PIN Transaksi | A3 | — |

### 3.2 Administrator — Data Induk

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-06 | Mereset PIN Penabung | A1 | «include» pada UC-10 |
| UC-07 | Mengelola Akun Pengguna Internal | A1 | — |
| UC-08 | Mengelola Data Anggota | A1 | — |
| UC-09 | Mengimpor Anggota dari Berkas CSV | A1 | «extend» UC-08 |
| UC-10 | Mengelola Data Penabung | A1 | «include» UC-06 |
| UC-11 | Melihat Detail Rekening | A1 | — |
| UC-12 | Mencetak Kartu Nasabah | A1 | — |
| UC-13 | Mengatur Konfigurasi Sistem | A1 | — |

### 3.3 Teller — Identifikasi dan Pembukaan Rekening

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-14 | Mencari Data Nasabah | A2 | — |
| UC-15 | Memindai QR Code Kartu Nasabah | A2 | «extend» UC-14 |
| UC-16 | Mendaftarkan Anggota Baru | A2 | — |
| UC-17 | Mengaktifkan Rekening Anggota | A2 | «include» UC-19 |
| UC-18 | Melihat Ringkasan Loket Harian | A2 | — |
| UC-19 | Mencatat Setoran Tunai | A2 | — |

### 3.4 Teller — Transaksi dan Penutupan Kas

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-20 | Mencatat Penarikan Tunai | A2 | — |
| UC-21 | Mencatat Transfer Antar Rekening | A2 | — |
| UC-22 | Mencetak Struk Transaksi | A2 | — |
| UC-23 | Mencetak Buku Tabungan | A2 | — |
| UC-24 | Mengajukan Penutupan Rekening | A2 | — |
| UC-25 | Membuat Laporan Kas Harian | A2 | — |
| UC-26 | Mencetak Laporan Kas Harian | A2 | «extend» UC-25 |

### 3.5 Administrator — Otorisasi Kas dan Koreksi

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-27 | Memeriksa Laporan Kas Harian | A1 | — |
| UC-28 | Menyetujui Laporan Kas Harian | A1 | «include» UC-29 |
| UC-29 | Memindahkan Kas ke Brankas Utama | A1 | Included oleh UC-28 |
| UC-30 | Menolak Laporan Kas Harian | A1 | — |
| UC-31 | Mengoreksi Transaksi | A1 | — |

### 3.6 Administrator — Akuntansi dan Laporan

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-32 | Melihat Jurnal Akuntansi | A1 | — |
| UC-33 | Melihat Buku Besar dan Neraca Saldo | A1 | — |
| UC-34 | Mengekspor Laporan Akuntansi | A1 | «extend» UC-32 |
| UC-35 | Melihat Laporan Bulanan | A1 | — |
| UC-36 | Mencetak Laporan Bulanan | A1 | — |
| UC-37 | Melihat Dasbor Analitik | A1 | — |

### 3.7 Administrator — Kelulusan dan Kenaikan Kelas

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-38 | Memproses Kenaikan Kelas | A1 | — |
| UC-39 | Memproses Kelulusan Angkatan | A1 | — |
| UC-40 | Menandai Anggota Lulus | A1 | — |
| UC-41 | Menandai Anggota Tidak Lulus | A1 | — |
| UC-42 | Menyetujui Permintaan Penutupan Rekening | A1 | — |
| UC-43 | Menolak Permintaan Penutupan Rekening | A1 | — |
| UC-44 | Mengunduh Arsip Kelulusan | A1 | «extend» UC-39 |

### 3.8 Nasabah — Layanan Mandiri

| ID | Nama Use Case | Aktor | Relasi |
|---|---|---|---|
| UC-45 | Melihat Saldo dan Ringkasan Rekening | A3 | — |
| UC-46 | Melihat Riwayat Mutasi | A3 | — |
| UC-47 | Melihat dan Mengunduh Rekening Koran | A3 | «extend» UC-46 |
| UC-48 | Melihat Kartu Nasabah Virtual | A3 | — |
| UC-49 | Melihat Profil Nasabah | A3 | — |

Total: 49 use case, 4 aktor (3 primer, 1 sekunder).

---

## 4. Diagram Use Case

Diagram disimpan dalam satu berkas draw.io dengan sembilan halaman. Sistem dipecah per
modul agar setiap gambar tetap terbaca saat dicetak pada kertas A3 atau A4 landscape.

| Halaman | Nama Halaman di draw.io | Use Case | Keterangan |
|---|---|---|---|
| 00 | `00 Legenda` | — | Notasi, batas sistem, dan panduan mengubah diagram. |
| 01 | `01 Autentikasi dan Keamanan` | UC-01…UC-05 | Termasuk aktor sekunder Layanan Email. |
| 02 | `02 Administrator — Data Induk` | UC-06…UC-13 | Pengguna internal, anggota, penabung, rekening, konfigurasi. |
| 03 | `03 Teller — Identifikasi dan Pembukaan` | UC-14…UC-19 | Pencarian, QR, registrasi, aktivasi, setoran. |
| 04 | `04 Teller — Transaksi dan Penutupan Kas` | UC-20…UC-26 | Penarikan, transfer, cetak, penutupan rekening dan kas. |
| 05 | `05 Administrator — Otorisasi Kas` | UC-27…UC-31 | Pemeriksaan, approval, penolakan, pemindahan kas, koreksi. |
| 06 | `06 Administrator — Akuntansi dan Laporan` | UC-32…UC-37 | Jurnal, buku besar, ekspor, laporan bulanan, dasbor. |
| 07 | `07 Administrator — Kelulusan dan Kenaikan Kelas` | UC-38…UC-44 | Administrasi akademik dan penutupan rekening alumni. |
| 08 | `08 Nasabah — Layanan Mandiri` | UC-45…UC-49 | Web nasabah dan aplikasi Flutter. |

### Cara membuka dan mengubah

1. Buka <https://app.diagrams.net>, pilih **Open Existing Diagram**, lalu pilih berkas
   `task/use-case-bank-mini.drawio`. Bisa juga memakai aplikasi draw.io desktop.
2. Setiap tab di bawah kanvas adalah satu halaman diagram.
3. Ekspor gambar untuk laporan: **File → Export as → PNG** (centang *Transparent
   Background* bila ingin ditempel di Word) atau **SVG**.
4. Bila nama use case berubah, ubah juga nama pada dokumen ini. Kode `UC-xx` adalah
   kunci penghubung antara diagram, spesifikasi, matriks, dan laporan pengujian.

---

## 5. Spesifikasi Use Case

Format setiap use case: aktor, tujuan, prakondisi, pemicu, pascakondisi, kanal, relasi,
implementasi, alur utama, serta alur alternatif dan pengecualian.

### 5.1 Modul Autentikasi dan Keamanan Akses

#### UC-01 — Masuk ke Aplikasi

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator, Teller, Nasabah |
| Tujuan | Aktor memperoleh akses ke sistem sesuai perannya. |
| Prakondisi | Akun terdaftar dan berstatus aktif. |
| Pemicu | Aktor membuka halaman login web atau layar masuk aplikasi. |
| Pascakondisi | Sesi web aktif atau token API terbit; percobaan masuk tercatat pada audit log. |
| Kanal | Web (sesi) dan API mobile (token). |
| Relasi | — |
| Implementasi | `Auth\LoginController@login`, `Mobile\AuthController@login`, `routes/web.php`, `routes/api.php` |

**Alur utama**

1. Aktor membuka formulir masuk.
2. Aktor mengisi identifier (username untuk pegawai, NIS untuk nasabah) dan password.
3. Sistem memvalidasi bahwa kedua field wajib terisi.
4. Sistem mencocokkan kredensial pegawai pada guard web; bila gagal, sistem mencocokkan NIS pada guard nasabah.
5. Sistem memeriksa status akun.
6. Sistem memperbarui ID sesi (web) atau menerbitkan token acak yang disimpan sebagai hash beserta masa berlaku (mobile).
7. Sistem mencatat `login_success` pada audit log.
8. Sistem mengarahkan aktor ke dasbor sesuai peran; untuk mobile, sistem mengembalikan data nasabah dan rekening.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Kredensial tidak cocok | Menampilkan "Kredensial tidak valid" (HTTP 401 untuk JSON) dan mencatat `login_failed`. |
| A2 | Akun berstatus diblokir | Membatalkan sesi, menampilkan pesan akun diblokir; untuk alumni ditampilkan pesan rekening ditutup karena kelulusan; mencatat `login_blocked`. |
| A3 | Field wajib kosong | Menampilkan pesan validasi dan tidak memproses autentikasi. |
| A4 | Permintaan melebihi batas laju | Menolak dengan HTTP 429. |

#### UC-02 — Keluar dari Aplikasi

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator, Teller, Nasabah |
| Tujuan | Aktor mengakhiri akses dan sesi/token tidak dapat dipakai lagi. |
| Prakondisi | Aktor sedang masuk. |
| Pemicu | Aktor memilih tombol keluar. |
| Pascakondisi | Sesi web tidak berlaku, token mobile yang dipakai dihapus, audit logout tercatat. |
| Kanal | Web dan API mobile. |
| Relasi | — |
| Implementasi | `Auth\LoginController@logout`, `Mobile\AuthController@logout` |

**Alur utama**

1. Aktor memilih keluar.
2. Sistem mencatat `logout` beserta identitas aktor.
3. Pada web, sistem mengeluarkan aktor dari guard pegawai dan guard nasabah, membatalkan sesi, dan memperbarui token CSRF.
4. Pada mobile, sistem menghapus token yang sedang dipakai.
5. Sistem mengarahkan aktor ke halaman login.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Tidak ada sesi/token aktif | Sistem tetap mengarahkan ke halaman login tanpa error. |

#### UC-03 — Meminta Kode Verifikasi Pemulihan Password

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator, Teller, Nasabah, Layanan Email |
| Tujuan | Aktor menerima kode verifikasi di email terdaftar sebagai langkah awal pemulihan password. |
| Prakondisi | Akun terdaftar, berstatus aktif, dan memiliki email. |
| Pemicu | Aktor memilih "Lupa Password" dan mengirim permintaan kode. |
| Pascakondisi | Kode verifikasi ber-hash tersimpan dengan masa berlaku; email terkirim; permintaan tercatat. |
| Kanal | Web dan API mobile. |
| Relasi | Di-«include» oleh UC-04. |
| Implementasi | `Auth\ForgotPasswordController@requestOtp`, `CredentialRecoveryService::requestOtp`, `ResetOtpMail` |

**Alur utama**

1. Aktor memilih jenis akun: pegawai (`staff`) atau nasabah (`nasabah`).
2. Aktor mengisi identifier: username untuk pegawai, nomor siswa untuk nasabah.
3. Sistem mencari akun berdasarkan identifier login, bukan email.
4. Sistem memeriksa status akun dan ketersediaan email terdaftar.
5. Sistem memeriksa jeda kirim ulang dan batas permintaan per jam.
6. Sistem membuat kode 6 digit, menyimpan hash-nya beserta masa berlaku 10 menit, dan menghanguskan kode lama yang belum terpakai.
7. Sistem mengirim kode ke email terdaftar melalui Layanan Email.
8. Sistem mencatat `password_reset._otp_requested` dan menampilkan konfirmasi.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Identifier tidak ditemukan | HTTP 404 dengan pesan sesuai jenis akun (pegawai diarahkan ke Administrator, nasabah diarahkan aktivasi di loket). |
| A2 | Akun diblokir | HTTP 403 dan pesan agar menghubungi Administrator. |
| A3 | Email belum terdaftar | HTTP 422 dan pesan agar melengkapi email melalui Teller atau Administrator. |
| A4 | Masih dalam jeda kirim ulang (60 detik) | Tidak mengirim kode baru; menampilkan sisa waktu tunggu. |
| A5 | Melebihi 5 permintaan per jam | HTTP 429 dan pesan untuk mencoba lagi nanti. |

#### UC-04 — Mengatur Ulang Password

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator, Teller, Nasabah |
| Tujuan | Aktor menetapkan password baru dengan kode verifikasi yang sah. |
| Prakondisi | Kode verifikasi aktif, belum kedaluwarsa, dan belum terpakai; akun aktif. |
| Pemicu | Aktor mengirim formulir reset password. |
| Pascakondisi | Password login baru berlaku; kode verifikasi hangus; token mobile nasabah dicabut; audit tercatat. |
| Kanal | Web dan API mobile. |
| Relasi | «include» UC-03. |
| Implementasi | `Auth\ForgotPasswordController@resetCredential`, `CredentialRecoveryService::verifyAndReset` |

**Alur utama**

1. Aktor mengisi jenis akun, identifier, kode verifikasi 6 digit, password baru minimal 8 karakter, dan konfirmasi password.
2. Sistem mencari akun dan email terdaftar.
3. Sistem membuka satu transaksi basis data dan mengunci kode verifikasi terbaru.
4. Sistem memeriksa masa berlaku dan status kode.
5. Sistem mencocokkan hash kode verifikasi.
6. Sistem menandai kode terpakai secara atomik sehingga satu kode hanya dapat dipakai sekali.
7. Sistem memperbarui password login.
8. Bila akun adalah nasabah, sistem mereset penghitung PIN gagal, membuka kunci PIN, dan mencabut seluruh token aplikasi mobile.
9. Sistem mencatat `password_reset._success` dan menampilkan pesan berhasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Kode salah | Menambah penghitung percobaan dan menampilkan sisa percobaan; setelah 5 kali kode dihanguskan (HTTP 429). |
| A2 | Kode kedaluwarsa atau tidak ada | HTTP 422 dan pesan meminta kode baru. |
| A3 | Kode sudah dipakai | HTTP 422; kode tidak dapat dipakai ulang. |
| A4 | Akun diblokir | HTTP 403; pemulihan ditolak. |
| A5 | Permintaan lain sedang mengunci data yang sama | HTTP 409 dan pesan untuk mencoba lagi. |

#### UC-05 — Mengubah PIN Transaksi

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah mengganti PIN transaksi loket tanpa mengubah password login. |
| Prakondisi | Nasabah sudah masuk; PIN lama diketahui; rekening tidak sedang terkunci. |
| Pemicu | Nasabah membuka halaman atau layar PIN dan mengirim formulir. |
| Pascakondisi | PIN baru tersimpan sebagai hash; PIN lama tidak berlaku; audit tercatat. |
| Kanal | Web nasabah dan aplikasi mobile. |
| Relasi | — |
| Implementasi | `Nasabah\PinController@update`, `Mobile\AuthController@changePin`, `SaverCredentialService::changeTransactionPin` |

**Alur utama**

1. Nasabah membuka halaman atau layar ubah PIN.
2. Nasabah mengisi PIN saat ini, PIN baru 6 digit, dan konfirmasi PIN baru.
3. Sistem memvalidasi panjang PIN, kecocokan konfirmasi, dan bahwa PIN baru berbeda dari PIN lama.
4. Sistem memverifikasi PIN lama memakai aturan yang sama dengan transaksi loket.
5. Sistem menyimpan hash PIN baru di dalam transaksi basis data dengan penguncian baris.
6. Sistem mencatat `customer_pin_changed` pada audit log.
7. Sistem menampilkan pesan berhasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | PIN lama salah | Menambah penghitung gagal dan menampilkan sisa percobaan. |
| A2 | Salah 3 kali berturut-turut | Mengunci verifikasi PIN selama 15 menit dan mencatat `customer_pin_locked`. |
| A3 | PIN baru sama dengan PIN lama | Validasi menolak. |
| A4 | PIN bukan 6 digit atau konfirmasi tidak cocok | Validasi menolak. |

### 5.2 Modul Administrator — Data Induk

#### UC-06 — Mereset PIN Penabung

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator menetapkan PIN transaksi baru ketika penabung tidak dapat memakai PIN lamanya. |
| Prakondisi | Penabung terdaftar dan bukan alumni. |
| Pemicu | Administrator mengisi PIN baru pada formulir ubah penabung. |
| Pascakondisi | PIN baru tersimpan; alasan reset dan identitas Administrator tercatat pada audit log. |
| Kanal | Web. |
| Relasi | Di-«include» oleh UC-10. |
| Implementasi | `Admin\SaverController@update`, `UpdateSaverRequest` |

**Alur utama**

1. Administrator membuka formulir ubah data penabung.
2. Administrator mengisi PIN baru 6 digit dan alasan reset.
3. Sistem memvalidasi PIN tepat 6 digit dan alasan wajib terisi.
4. Sistem menyimpan hash PIN baru.
5. Sistem mencatat `pin_reset_by_admin` beserta aktor dan alasan.
6. Sistem menampilkan pesan berhasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Alasan reset kosong | Validasi menolak karena jejak audit tidak dapat dipertanggungjawabkan. |
| A2 | PIN bukan 6 digit | Validasi menolak. |
| A3 | Penabung sudah alumni | Perubahan ditolak dan data alumni tetap terkunci. |

#### UC-07 — Mengelola Akun Pengguna Internal

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memelihara akun Administrator dan Teller. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Pengguna. |
| Pascakondisi | Data akun internal sesuai perubahan; audit reset password tercatat bila ada. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\UserController`, `StoreUserRequest`, `UpdateUserRequest` |

**Alur utama**

1. Administrator membuka daftar akun dengan filter role dan status serta pagination.
2. Administrator memilih tambah, ubah, blokir/aktifkan, atau hapus.
3. Saat menambah, Administrator mengisi nama lengkap, username, email, password, role (admin/teller), dan status.
4. Sistem memvalidasi keunikan username dan email, password minimal 6 karakter, serta role yang diizinkan.
5. Saat mengubah dan mengisi password baru, Administrator wajib mengisi alasan reset.
6. Sistem menyimpan perubahan dan menampilkan pesan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Username atau email sudah dipakai | Validasi menolak. |
| A2 | Perubahan menyisakan nol Admin aktif atau nol Teller aktif | Perubahan ditolak. |
| A3 | Administrator menghapus akunnya sendiri | Penghapusan ditolak. |
| A4 | Akun masih memiliki data terkait (anggota, penabung, transaksi, laporan, pemindahan kas) | Penghapusan ditolak. |
| A5 | Reset password tanpa alasan | Validasi menolak. |

#### UC-08 — Mengelola Data Anggota

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memelihara daftar siswa yang belum memiliki rekening. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Anggota. |
| Pascakondisi | Data anggota tersimpan, berubah, atau terhapus; audit pendaftaran tercatat. |
| Kanal | Web. |
| Relasi | Di-«extend» oleh UC-09. |
| Implementasi | `Admin\MemberController`, `MemberRegistrationService`, `StoreMemberRequest`, `UpdateMemberRequest` |

**Alur utama**

1. Administrator membuka daftar anggota (anggota tanpa rekening dan belum lulus) dengan pencarian nama, NIS, kelas, atau angkatan.
2. Administrator memilih tambah atau ubah.
3. Administrator mengisi NIS (opsional), nama lengkap (wajib), kelas, dan angkatan keluar.
4. Bila NIS kosong, sistem membentuk NIS otomatis dari tahun berjalan dan nomor urut.
5. Sistem menyimpan anggota dengan status belum memiliki rekening.
6. Untuk penghapusan, sistem meminta konfirmasi lalu menghapus data anggota.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | NIS sudah terdaftar | Validasi menolak. |
| A2 | NIS diubah padahal anggota sudah punya rekening | Perubahan ditolak. |
| A3 | Anggota punya histori rekening | Penghapusan ditolak. |
| A4 | Nama kosong | Validasi menolak. |

#### UC-09 — Mengimpor Anggota dari Berkas CSV

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator menambah banyak anggota sekaligus dari berkas CSV. |
| Prakondisi | Administrator sudah masuk; berkas CSV tersedia. |
| Pemicu | Administrator mengunggah berkas pada menu Impor Anggota. |
| Pascakondisi | Anggota baru dibuat, kelas anggota lama diperbarui, ringkasan impor ditampilkan. |
| Kanal | Web. |
| Relasi | «extend» UC-08. |
| Implementasi | `Admin\MemberController@import`, `MemberImportService`, `MemberImportRequest` |

**Alur utama**

1. Administrator mengunduh templat CSV berisi kolom `nis`, `nama`, `angkatan_keluar`, dan `kelas`.
2. Administrator mengunggah berkas CSV atau TXT dengan ukuran maksimal 5 MB.
3. Sistem mendeteksi pemisah kolom (koma atau titik koma) dan memvalidasi keberadaan kolom `nis` dan `nama`.
4. Sistem memvalidasi setiap baris: NIS wajib, maksimal 30 karakter, tidak duplikat di dalam berkas; nama wajib, maksimal 100 karakter.
5. Sistem memproses baris per potongan 100 data di dalam transaksi basis data.
6. Anggota baru dibuat dengan status belum memiliki rekening; anggota yang sudah ada hanya diperbarui kelasnya bila berbeda.
7. Sistem menampilkan ringkasan jumlah diimpor, diperbarui, dilewati, gagal, dan maksimal 50 catatan masalah.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Berkas bukan CSV atau melebihi 5 MB | Validasi menolak. |
| A2 | Header tidak memuat `nis` atau `nama` | Impor dibatalkan dengan pesan header tidak lengkap. |
| A3 | Baris tanpa NIS atau nama | Baris dihitung gagal dan dicatat pada ringkasan. |
| A4 | NIS duplikat di dalam berkas | Baris berikutnya dihitung gagal. |
| A5 | NIS sudah terdaftar dan kelas tidak berubah | Baris dihitung dilewati. |

#### UC-10 — Mengelola Data Penabung

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memelihara profil, status, dan kredensial penabung. |
| Prakondisi | Penabung terdaftar. |
| Pemicu | Administrator membuka menu Penabung. |
| Pascakondisi | Profil atau status penabung berubah; audit reset PIN tercatat bila ada. |
| Kanal | Web. |
| Relasi | «include» UC-06. |
| Implementasi | `Admin\SaverController`, `UpdateSaverRequest` |

**Alur utama**

1. Administrator membuka daftar penabung dengan filter status dan pencarian nama, nomor siswa, atau kelas.
2. Administrator memilih ubah data atau blokir/aktifkan.
3. Saat mengubah, Administrator dapat memperbarui nomor siswa, nama, kelas, nomor telepon, email, dan status.
4. Bila PIN baru diisi, sistem menjalankan UC-06.
5. Sistem menyimpan perubahan dan menampilkan pesan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Penabung sudah alumni | Perubahan dan pemblokiran ditolak; data alumni dikunci. |
| A2 | Nomor siswa atau email sudah dipakai penabung lain | Validasi menolak. |
| A3 | Email kosong | Validasi menolak karena email dipakai untuk pemulihan password. |
| A4 | PIN diisi tanpa alasan reset | Validasi menolak. |

#### UC-11 — Melihat Detail Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memeriksa kondisi satu rekening sebelum mengambil keputusan. |
| Prakondisi | Rekening terdaftar. |
| Pemicu | Administrator membuka rekening dari daftar penabung atau pencarian. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\AccountController@show` |

**Alur utama**

1. Administrator memilih satu rekening.
2. Sistem menampilkan data penabung, nomor rekening, tahun ajaran, saldo awal, saldo berjalan, saldo mengendap minimum, dan status rekening.
3. Sistem menampilkan kartu penabung virtual ber-QR dan tautan cetak kartu.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Rekening tidak ditemukan | HTTP 404. |
| A2 | Aktor bukan Administrator | Akses ditolak oleh policy. |

#### UC-12 — Mencetak Kartu Nasabah

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mencetak kartu nasabah ber-QR untuk dipakai di loket. |
| Prakondisi | Rekening dan penabung tersedia. |
| Pemicu | Administrator memilih cetak kartu dari detail rekening. |
| Pascakondisi | Halaman kartu siap cetak; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\AccountController@printCard`, `QrCodeController`, `QrCodeService` |

**Alur utama**

1. Administrator membuka detail rekening.
2. Administrator memilih cetak kartu.
3. Sistem menampilkan kartu berisi identitas penabung, nomor rekening, dan QR unik penabung.
4. Administrator mencetak kartu atau mengunduh berkas QR.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Data QR kosong | Permintaan QR ditolak dengan HTTP 422. |

#### UC-13 — Mengatur Konfigurasi Sistem

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator menetapkan parameter bank yang dipakai lintas modul. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Konfigurasi. |
| Pascakondisi | Nilai konfigurasi tersimpan dan berlaku pada proses berikutnya. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\SystemConfigController` |

**Alur utama**

1. Administrator membuka halaman konfigurasi.
2. Administrator mengubah nama bank, saldo mengendap minimum, setoran minimal aktivasi, dan daftar kelas/jurusan.
3. Sistem memvalidasi field wajib, nilai bilangan bulat tidak negatif, dan panjang teks.
4. Sistem menyimpan setiap kunci konfigurasi.
5. Sistem menampilkan pesan berhasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Nilai negatif atau bukan angka | Validasi menolak. |
| A2 | Field wajib kosong | Validasi menolak. |
| A3 | Aktor bukan Administrator | Akses ditolak oleh policy. |

### 5.3 Modul Teller — Identifikasi dan Pembukaan Rekening

#### UC-14 — Mencari Data Nasabah

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller menemukan rekening atau anggota yang akan dilayani. |
| Prakondisi | Teller sudah masuk. |
| Pemicu | Teller membuka halaman pencarian dan mengirim kata kunci. |
| Pascakondisi | Teller berada di halaman rekening, aktivasi, atau daftar hasil pencarian. |
| Kanal | Web. |
| Relasi | Di-«extend» oleh UC-15. |
| Implementasi | `Teller\MemberSearchController@search`, `@lookup`, `@show` |

**Alur utama**

1. Teller membuka halaman pencarian.
2. Teller mengisi nomor rekening, NIS, nama, atau nilai QR Code.
3. Sistem mencari kecocokan persis pada nomor rekening, nilai QR penabung, lalu NIS anggota.
4. Bila tepat satu rekening ditemukan, sistem membuka halaman rekening.
5. Bila yang ditemukan anggota tanpa rekening, sistem membuka halaman aktivasi.
6. Bila hasil lebih dari satu, sistem menampilkan maksimal 25 rekening dan 25 anggota untuk dipilih.
7. Teller memilih salah satu hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Kata kunci kosong | Pesan agar mengisi kata kunci terlebih dahulu. |
| A2 | Tidak ada hasil | Pesan "Anggota atau penabung tidak ditemukan". |
| A3 | Anggota atau penabung sudah alumni | Tidak muncul pada daftar hasil kerja harian. |

#### UC-15 — Memindai QR Code Kartu Nasabah

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mengisi kata kunci pencarian tanpa mengetik. |
| Prakondisi | Kartu nasabah ber-QR tersedia; perangkat memiliki kamera. |
| Pemicu | Teller membuka panel pindai QR. |
| Pascakondisi | Kata kunci terisi dan pencarian dijalankan. |
| Kanal | Web (kamera perangkat). |
| Relasi | «extend» UC-14. |
| Implementasi | `resources/views/teller/search.blade.php`, `resources/js/qr-scanner.js` |

**Alur utama**

1. Teller membuka panel "Pindai QR Code memakai kamera".
2. Sistem meminta izin kamera dan menampilkan pratinjau.
3. Teller mengarahkan kamera ke QR pada kartu nasabah.
4. Sistem membaca nilai QR dan menjalankan pencarian UC-14.
5. Kamera dihentikan setelah hasil terbaca.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Kamera tidak tersedia atau izin ditolak | Panel menampilkan pesan agar memakai pencarian manual. |
| A2 | QR tidak dikenali | Sistem menampilkan status dan tetap membuka pencarian manual. |

#### UC-16 — Mendaftarkan Anggota Baru

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mendaftarkan siswa sebagai anggota sebelum rekening dibuka. |
| Prakondisi | Teller sudah masuk. |
| Pemicu | Teller membuka formulir registrasi anggota. |
| Pascakondisi | Anggota tersimpan berstatus belum memiliki rekening; audit tercatat. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\MemberRegistrationController`, `MemberRegistrationService`, `StoreMemberRegistrationRequest` |

**Alur utama**

1. Teller membuka formulir registrasi.
2. Teller mengisi NIS (opsional), nama lengkap, kelas, dan angkatan keluar.
3. Sistem memvalidasi keunikan NIS dan kewajiban nama.
4. Bila NIS kosong, sistem membentuk NIS otomatis.
5. Sistem menyimpan anggota dengan status belum memiliki rekening.
6. Sistem menampilkan ringkasan pendaftaran dan tautan aktivasi.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | NIS sudah terdaftar | Validasi menolak. |
| A2 | Nama kosong | Validasi menolak. |

#### UC-17 — Mengaktifkan Rekening Anggota

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller membuka rekening penabung baru beserta setoran awal. |
| Prakondisi | Anggota terdaftar, belum memiliki rekening, dan belum pernah menjadi penabung. |
| Pemicu | Teller membuka halaman aktivasi dari hasil pencarian atau daftar anggota. |
| Pascakondisi | Penabung dan rekening aktif terbentuk, setoran awal tercatat, anggota berstatus aktif, struk ditampilkan. |
| Kanal | Web. |
| Relasi | «include» UC-19. |
| Implementasi | `Teller\MemberActivationController`, `MemberActivationService`, `AccountService::openFor` |

**Alur utama**

1. Teller membuka formulir aktivasi anggota.
2. Teller mengisi nominal setoran awal, nomor slip (opsional), PIN 6 digit, password aplikasi dan konfirmasinya, email, kelas, serta nomor telepon.
3. Sistem memvalidasi setoran minimal sesuai konfigurasi, email unik, PIN tepat 6 digit, dan password minimal 8 karakter.
4. Dalam satu transaksi basis data, sistem membuat data penabung dengan hash PIN dan password serta QR unik.
5. Sistem membuka rekening dengan nomor otomatis berdasarkan tahun ajaran dan nomor urut.
6. Sistem mencatat setoran awal melalui UC-19.
7. Sistem menandai anggota berstatus memiliki rekening aktif.
8. Sistem mencatat audit aktivasi dan menampilkan struk transaksi.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Setoran di bawah batas minimal | Validasi menolak dan menampilkan nominal minimal. |
| A2 | Email sudah dipakai penabung lain | Validasi menolak. |
| A3 | Anggota sudah punya rekening atau sudah terdaftar sebagai penabung | Aktivasi ditolak. |
| A4 | Password atau konfirmasi tidak cocok | Validasi menolak. |

#### UC-18 — Melihat Ringkasan Loket Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller melihat kondisi loket hari ini sebelum dan sesudah melayani. |
| Prakondisi | Teller sudah masuk. |
| Pemicu | Teller membuka dasbor Teller. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `routes/web.php` (`teller.dashboard`), `resources/views/teller/dashboard.blade.php` |

**Alur utama**

1. Teller membuka dasbor.
2. Sistem menampilkan total setoran hari ini, total penarikan hari ini, jumlah transaksi sukses, dan status laporan kas hari ini.
3. Sistem menampilkan tautan cepat ke pencarian nasabah dan registrasi anggota.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Belum ada transaksi hari ini | Nilai ditampilkan nol. |

#### UC-19 — Mencatat Setoran Tunai

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mencatat uang tunai yang diterima dan menambah saldo penabung. |
| Prakondisi | Rekening aktif, penabung aktif, dan laporan kas hari ini belum disubmit atau disetujui. |
| Pemicu | Teller membuka formulir setoran dari halaman rekening. |
| Pascakondisi | Transaksi tersimpan, saldo bertambah, jurnal seimbang terbentuk, struk tersedia. |
| Kanal | Web. |
| Relasi | Di-«include» oleh UC-17. |
| Implementasi | `Teller\TransactionController@storeDeposit`, `TransactionService::deposit`, `StoreDepositRequest` |

**Alur utama**

1. Teller membuka formulir setoran.
2. Teller mengisi nominal uang yang diterima dan nomor slip (opsional).
3. Sistem memvalidasi nominal berupa bilangan bulat lebih besar dari nol.
4. Sistem mengunci baris rekening dan memeriksa status rekening, status penabung, serta ada tidaknya permintaan penutupan.
5. Sistem memeriksa bahwa laporan kas Teller untuk tanggal ini belum disubmit atau disetujui.
6. Sistem membuat nomor transaksi dan nomor slip otomatis bila tidak diisi.
7. Sistem menyimpan transaksi, menambah saldo, dan memposting jurnal Debet 101 Kas Teller, Kredit 201 Tabungan.
8. Sistem memastikan total debit sama dengan total kredit; bila tidak, seluruh transaksi dibatalkan.
9. Sistem mencatat audit dan menampilkan struk.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Nominal nol atau negatif | Validasi menolak. |
| A2 | Rekening tidak aktif atau sedang menunggu penutupan | Transaksi ditolak. |
| A3 | Penabung diblokir atau alumni | Transaksi ditolak. |
| A4 | Laporan kas tanggal ini sudah disubmit/disetujui | Transaksi ditolak. |
| A5 | Nomor slip sudah dipakai | Transaksi ditolak. |
| A6 | Jurnal tidak seimbang | Seluruh proses dibatalkan dan pesan error ditampilkan. |

### 5.4 Modul Teller — Transaksi dan Penutupan Kas

#### UC-20 — Mencatat Penarikan Tunai

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller menyerahkan uang tunai kepada penabung dan mengurangi saldo. |
| Prakondisi | Rekening aktif, penabung aktif, PIN benar, laporan kas hari ini belum ditutup. |
| Pemicu | Teller membuka formulir penarikan dari halaman rekening. |
| Pascakondisi | Transaksi tersimpan, saldo berkurang, jurnal seimbang terbentuk, struk tersedia. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\TransactionController@storeWithdrawal`, `TransactionService::withdraw`, `StoreWithdrawalRequest` |

**Alur utama**

1. Teller membuka formulir penarikan.
2. Teller mengisi nominal, PIN penabung, dan nomor slip (opsional).
3. Sistem memvalidasi nominal lebih besar dari nol dan PIN tepat 6 digit.
4. Sistem memverifikasi PIN penabung; PIN salah menambah penghitung gagal.
5. Sistem mengunci rekening dan memeriksa status rekening, penabung, permintaan penutupan, dan status laporan kas.
6. Sistem memastikan saldo mencukupi dan saldo setelah penarikan tidak di bawah saldo mengendap.
7. Sistem menyimpan transaksi, mengurangi saldo, dan memposting jurnal Debet 201 Tabungan, Kredit 101 Kas Teller.
8. Sistem mencatat audit dan menampilkan struk.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | PIN salah | Menampilkan sisa percobaan; salah 3 kali mengunci verifikasi 15 menit. |
| A2 | Saldo tidak mencukupi | Transaksi ditolak. |
| A3 | Saldo setelah penarikan di bawah saldo mengendap | Transaksi ditolak dengan nominal batas yang diizinkan. |
| A4 | Rekening tidak aktif atau penabung diblokir/alumni | Transaksi ditolak. |
| A5 | Laporan kas tanggal ini sudah ditutup | Transaksi ditolak. |

#### UC-21 — Mencatat Transfer Antar Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller memindahkan saldo dari rekening pengirim ke rekening penerima. |
| Prakondisi | Kedua rekening aktif, PIN pengirim benar, laporan kas hari ini belum ditutup. |
| Pemicu | Teller membuka formulir transfer dari halaman rekening pengirim. |
| Pascakondisi | Transaksi tersimpan, saldo kedua rekening berubah, jurnal seimbang terbentuk, struk tersedia. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\TransactionController@storeTransfer`, `TransactionService::transfer`, `StoreTransferRequest` |

**Alur utama**

1. Teller membuka formulir transfer dari rekening pengirim.
2. Teller mengisi nomor rekening tujuan, nominal, PIN pengirim, nomor slip (opsional), dan catatan.
3. Sistem memvalidasi rekening tujuan ada dan berbeda dari rekening pengirim.
4. Sistem memverifikasi PIN pengirim.
5. Sistem mengunci kedua rekening dalam urutan ID menaik untuk mencegah deadlock.
6. Sistem memeriksa status kedua rekening, status laporan kas, kecukupan saldo, dan batas saldo mengendap pengirim.
7. Sistem menyimpan transaksi, mengubah saldo pengirim dan penerima, serta memposting jurnal Debet 201 pengirim, Kredit 201 penerima.
8. Sistem mencatat audit dan menampilkan struk.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Rekening tujuan sama dengan pengirim | Validasi menolak. |
| A2 | Rekening tujuan tidak ditemukan | Validasi menolak. |
| A3 | PIN salah atau terkunci | Transaksi ditolak. |
| A4 | Saldo pengirim tidak mencukupi | Transaksi ditolak. |
| A5 | Saldo pengirim setelah transfer di bawah saldo mengendap | Transaksi ditolak. |
| A6 | Rekening penerima tidak aktif | Transaksi ditolak. |

#### UC-22 — Mencetak Struk Transaksi

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller menyerahkan bukti transaksi resmi kepada penabung. |
| Prakondisi | Transaksi berstatus sukses tersimpan. |
| Pemicu | Teller memilih cetak struk setelah transaksi atau dari riwayat. |
| Pascakondisi | Struk ditampilkan atau dicetak; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\TransactionController@receipt`, `resources/views/teller/receipt.blade.php` |

**Alur utama**

1. Teller membuka struk transaksi.
2. Sistem menampilkan nama bank, nomor transaksi, jenis transaksi, nominal, terbilang, saldo sebelum dan sesudah, nomor referensi jurnal, keterangan, dan status.
3. Teller mencetak struk.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Transaksi tidak ditemukan | HTTP 404. |

#### UC-23 — Mencetak Buku Tabungan

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mencetak mutasi pada buku tabungan fisik penabung. |
| Prakondisi | Rekening memiliki riwayat transaksi (boleh kosong). |
| Pemicu | Teller membuka buku tabungan dari halaman rekening. |
| Pascakondisi | Halaman buku tabungan siap cetak; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\PassbookController@show`, `@print` |

**Alur utama**

1. Teller membuka halaman buku tabungan.
2. Sistem menampilkan transaksi sukses per halaman sebanyak 20 baris.
3. Teller memilih nomor halaman dan baris awal cetak (1 sampai 20).
4. Teller mencetak halaman buku tabungan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Belum ada transaksi | Halaman tetap tampil kosong tanpa error. |
| A2 | Baris awal di luar rentang | Nilai dibatasi ke rentang 1 sampai 20. |

#### UC-24 — Mengajukan Penutupan Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mencairkan saldo penutupan dan mengajukan penutupan rekening alumni. |
| Prakondisi | Rekening aktif, tidak ada permintaan penutupan lain, PIN benar, laporan kas hari ini masih terbuka. |
| Pemicu | Teller membuka formulir penutupan rekening. |
| Pascakondisi | Saldo di atas saldo mengendap dicairkan, permintaan penutupan berstatus menunggu keputusan, transaksi baru pada rekening ditahan. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Teller\AccountClosureController`, `AccountClosureService::request`, `TransactionService::withdrawClosurePayout` |

**Alur utama**

1. Teller membuka formulir penutupan rekening.
2. Teller mengisi PIN penabung dan catatan (opsional).
3. Sistem memverifikasi PIN penabung.
4. Sistem mengunci rekening dan memeriksa bahwa rekening aktif serta belum ada permintaan penutupan.
5. Sistem menghitung saldo di atas saldo mengendap dan mencairkannya sebagai penarikan bila lebih dari nol.
6. Sistem membuat permintaan penutupan berstatus menunggu dengan nominal pencairan dan identitas Teller.
7. Sistem mencatat audit dan menampilkan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Saldo hanya sebesar saldo mengendap | Pencairan nol, permintaan tetap dibuat. |
| A2 | PIN salah atau terkunci | Permintaan ditolak. |
| A3 | Sudah ada permintaan penutupan yang menunggu | Permintaan baru ditolak. |
| A4 | Rekening tidak aktif | Permintaan ditolak. |
| A5 | Laporan kas tanggal ini sudah ditutup | Pencairan ditolak. |

#### UC-25 — Membuat Laporan Kas Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller menutup kas loket dan menyerahkan rekap harian untuk divalidasi. |
| Prakondisi | Teller sudah masuk; tanggal laporan dipilih. |
| Pemicu | Teller mengirim formulir penutupan kas. |
| Pascakondisi | Laporan berstatus menunggu validasi; transaksi baru pada tanggal tersebut ditolak. |
| Kanal | Web. |
| Relasi | Di-«extend» oleh UC-26. |
| Implementasi | `Teller\ClosingController@store`, `DailyReportService::createReport`, `StoreClosingRequest` |

**Alur utama**

1. Teller membuka halaman penutupan kas dan memilih tanggal kerja.
2. Sistem menghitung saldo awal laci dari mutasi kas yang belum tersapu, total setoran, total penarikan (termasuk dampak jurnal pembalik), dan saldo akhir sistem.
3. Teller memilih membuat laporan.
4. Sistem memeriksa bahwa belum ada laporan berstatus draft, menunggu, atau disetujui untuk tanggal tersebut.
5. Sistem memakai ulang nomor laporan yang sama bila sebelumnya pernah ditolak, atau membuat nomor baru.
6. Sistem menyimpan laporan berstatus menunggu validasi dan mencatat audit.
7. Sistem menampilkan halaman cetak laporan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Laporan tanggal tersebut sudah ada | Pembuatan laporan ditolak. |
| A2 | Tanggal tidak sesuai format | Validasi menolak. |
| A3 | Teller bukan pemilik laporan | Akses cetak ditolak oleh policy. |

#### UC-26 — Mencetak Laporan Kas Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Teller |
| Tujuan | Teller mencetak bukti penutupan kas untuk diserahkan ke Administrator. |
| Prakondisi | Laporan kas harian tersedia. |
| Pemicu | Teller memilih cetak setelah membuat laporan atau dari riwayat. |
| Pascakondisi | Laporan siap cetak; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | «extend» UC-25. |
| Implementasi | `Teller\ClosingController@print` |

**Alur utama**

1. Teller membuka laporan kas harian.
2. Sistem menampilkan identitas Teller, tanggal, saldo awal, total setoran, total penarikan, saldo akhir sistem, dan daftar transaksi.
3. Teller mencetak laporan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Laporan bukan milik Teller yang masuk | Akses ditolak. |

### 5.5 Modul Administrator — Otorisasi Kas dan Koreksi

#### UC-27 — Memeriksa Laporan Kas Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memeriksa laporan Teller sebelum menyetujui atau menolak. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Laporan Harian. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\DailyReportController@index`, `@show` |

**Alur utama**

1. Administrator membuka daftar laporan dengan filter status dan tanggal serta pagination.
2. Administrator membuka satu laporan.
3. Sistem menampilkan rekap kas, daftar transaksi, data pemindahan kas, dan jejak audit laporan tersebut.
4. Administrator memutuskan untuk menyetujui (UC-28) atau menolak (UC-30).

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Laporan tidak ditemukan | HTTP 404. |
| A2 | Aktor bukan Administrator | Akses ditolak oleh policy. |

#### UC-28 — Menyetujui Laporan Kas Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memvalidasi laporan dan memindahkan kas laci ke brankas utama. |
| Prakondisi | Status laporan menunggu validasi; Administrator bukan pembuat laporan; arus kas tanggal tersebut belum berubah. |
| Pemicu | Administrator mengirim formulir persetujuan. |
| Pascakondisi | Laporan disetujui, kas laci tersapu ke brankas, jurnal pemindahan terbentuk, audit tercatat. |
| Kanal | Web. |
| Relasi | «include» UC-29. |
| Implementasi | `Admin\DailyReportController@approve`, `DailyReportService::approve`, `ApproveReportRequest` |

**Alur utama**

1. Administrator membuka detail laporan.
2. Administrator mengisi catatan pemeriksaan (wajib, maksimal 500 karakter).
3. Sistem mengunci laporan dan memastikan statusnya masih menunggu validasi.
4. Bila laporan memiliki transaksi, sistem menghitung ulang rekap dan membandingkan saldo akhir sistem dengan nilai saat laporan dibuat.
5. Sistem memastikan Administrator bukan Teller pembuat laporan.
6. Sistem mengubah status menjadi disetujui, mencatat penyetuju, waktu, dan catatan.
7. Sistem menjalankan UC-29 untuk memindahkan kas sesuai saldo akhir sistem.
8. Sistem mencatat audit persetujuan dan pemindahan kas.
9. Sistem menampilkan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Catatan pemeriksaan kosong | Validasi menolak. |
| A2 | Status laporan bukan menunggu validasi | Persetujuan ditolak. |
| A3 | Administrator adalah pembuat laporan | Persetujuan ditolak. |
| A4 | Arus kas berubah setelah laporan dibuat | Persetujuan ditolak; laporan harus dihitung ulang. |
| A5 | Nominal pemindahan melebihi saldo kas laci menurut jurnal | Pemindahan ditolak dan seluruh persetujuan dibatalkan. |

#### UC-29 — Memindahkan Kas ke Brankas Utama

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Seluruh kas laci Teller tersapu ke Brankas Utama dan tercatat pada jurnal. |
| Prakondisi | Laporan kas harian disetujui pada alur UC-28. |
| Pemicu | Persetujuan laporan (UC-28). |
| Pascakondisi | Pemindahan kas tercatat; jurnal Debet 102 dan Kredit 101 terbentuk bila nominal lebih dari nol. |
| Kanal | Web (otomatis di dalam persetujuan). |
| Relasi | Included oleh UC-28. |
| Implementasi | `DailyReportService::approve`, `CashTransfer` |

**Alur utama**

1. Sistem menetapkan nominal pemindahan sebesar saldo akhir sistem pada laporan.
2. Sistem mencatat pemindahan dari Laci Teller ke Brankas Utama berstatus transferred.
3. Bila nominal lebih dari nol, sistem memposting jurnal Debet 102 Kas Brankas Utama, Kredit 101 Kas Teller.
4. Sistem mencatat audit `cash_transferred`.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Nominal nol | Pemindahan tetap dicatat tanpa jurnal. |
| A2 | Saldo kas laci menurut jurnal lebih kecil dari nominal | Persetujuan dibatalkan. |

#### UC-30 — Menolak Laporan Kas Harian

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengembalikan laporan agar Teller memperbaiki. |
| Prakondisi | Status laporan menunggu validasi. |
| Pemicu | Administrator mengirim formulir penolakan. |
| Pascakondisi | Laporan berstatus ditolak dengan alasan; Teller dapat mengirim ulang dengan nomor yang sama. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\DailyReportController@reject`, `DailyReportService::reject`, `RejectReportRequest` |

**Alur utama**

1. Administrator membuka detail laporan.
2. Administrator mengisi alasan penolakan (wajib, maksimal 500 karakter).
3. Sistem memastikan status laporan masih menunggu validasi.
4. Sistem mengubah status menjadi ditolak dan mengosongkan data penyetuju.
5. Sistem mencatat audit penolakan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Alasan kosong | Validasi menolak. |
| A2 | Status laporan bukan menunggu validasi | Penolakan ditolak. |

#### UC-31 — Mengoreksi Transaksi

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator membatalkan transaksi keliru tanpa menghapus jejak aslinya. |
| Prakondisi | Transaksi berstatus sukses dan berjenis setoran, penarikan, atau transfer; laporan harian tanggal tersebut belum disetujui. |
| Pemicu | Administrator memilih tombol koreksi pada transaksi. |
| Pascakondisi | Transaksi koreksi dan jurnal pembalik terbentuk; saldo dipulihkan; transaksi asal ditandai reversed. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\CorrectionController@store`, `ReversalService::reverse`, `ReverseTransactionRequest` |

**Alur utama**

1. Administrator membuka transaksi dari halaman mutasi atau riwayat.
2. Administrator mengisi alasan koreksi (5 sampai 255 karakter).
3. Sistem memastikan transaksi bukan transaksi koreksi, berstatus sukses, dan berjenis yang didukung.
4. Sistem memastikan laporan harian tanggal transaksi belum disetujui.
5. Sistem mengunci rekening terkait dalam urutan ID menaik.
6. Sistem memulihkan saldo sesuai jenis transaksi: setoran dikurangi bila saldo cukup, penarikan ditambahkan, transfer dikembalikan ke pengirim dan ditarik dari penerima bila saldo penerima cukup.
7. Sistem membuat transaksi koreksi dengan nomor baru dan memposting jurnal pembalik.
8. Sistem menandai transaksi asal sebagai reversed beserta waktu, aktor, dan alasan.
9. Sistem mencatat audit dan menampilkan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Alasan kurang dari 5 karakter | Validasi menolak. |
| A2 | Transaksi sudah dikoreksi atau bukan transaksi sukses | Koreksi ditolak. |
| A3 | Laporan harian tanggal tersebut sudah disetujui | Koreksi ditolak karena kas sudah di brankas. |
| A4 | Saldo tidak mencukupi untuk membalik setoran atau transfer | Koreksi ditolak. |
| A5 | Transaksi koreksi dikoreksi ulang | Koreksi ditolak. |

### 5.6 Modul Administrator — Akuntansi dan Laporan

#### UC-32 — Melihat Jurnal Akuntansi

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengaudit jejak jurnal dari setiap transaksi. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Jurnal. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web dan JSON API internal. |
| Relasi | Di-«extend» oleh UC-34. |
| Implementasi | `JournalController@index` |

**Alur utama**

1. Administrator membuka halaman jurnal.
2. Administrator memfilter berdasarkan akun (101 atau 102), nomor transaksi, dan rentang tanggal.
3. Sistem menampilkan daftar jurnal dengan total debit dan kredit sesuai filter.
4. Sistem menampilkan 20 baris per halaman dengan filter yang tetap terbawa.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Kode akun 201 dikirim lewat URL | Kode diabaikan dan filter kembali ke akun yang ditampilkan. |
| A2 | Aktor bukan Administrator | Akses ditolak oleh policy. |

#### UC-33 — Melihat Buku Besar dan Neraca Saldo

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memeriksa mutasi dan saldo akun kas beserta keseimbangan buku. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Buku Besar. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `AccountingReportController@generalLedger`, `AccountingService::trialBalance` |

**Alur utama**

1. Administrator memilih akun (101 atau 102) dan rentang tanggal.
2. Sistem menampilkan saldo awal, daftar mutasi, total debit, total kredit, dan saldo akhir akun.
3. Sistem menampilkan ringkasan neraca saldo kumulatif sampai tanggal akhir rentang.
4. Sistem menampilkan indikator seimbang yang dihitung dari seluruh akun, termasuk akun yang tidak ditampilkan.
5. Sistem menampilkan 25 baris mutasi per halaman.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Tanpa rentang tanggal | Seluruh riwayat ditampilkan dengan saldo awal nol. |
| A2 | URL neraca saldo lama dibuka | Sistem mengalihkan ke halaman buku besar dengan rentang yang setara. |

#### UC-34 — Mengekspor Laporan Akuntansi

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengunduh laporan akuntansi dalam bentuk CSV untuk arsip atau analisis lanjutan. |
| Prakondisi | Administrator sudah masuk dan filter laporan sudah dipilih. |
| Pemicu | Administrator memilih tombol ekspor. |
| Pascakondisi | Berkas CSV terunduh; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | «extend» UC-32. |
| Implementasi | `AccountingReportController@exportCsv` |

**Alur utama**

1. Administrator memilih jenis ekspor: jurnal, buku besar, atau neraca saldo.
2. Sistem membaca filter halaman: akun dan rentang tanggal.
3. Sistem menuliskan data ke CSV dengan UTF-8 BOM agar terbaca di Excel.
4. Sistem mengirim berkas dengan nama bertanggal.
5. Sistem mencatat data pada audit hanya untuk ekspor arsip alumni, bukan ekspor akuntansi.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Jenis tidak dikenal | Sistem memakai format jurnal. |
| A2 | Kode akun tidak ditampilkan | Ekspor jatuh ke akun 101. |

#### UC-35 — Melihat Laporan Bulanan

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator melihat rekap transaksi satu bulan. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka menu Laporan Bulanan. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `MonthlyReportController@index` |

**Alur utama**

1. Administrator memilih bulan.
2. Sistem menghitung total setoran, total penarikan, jumlah transaksi, dan rekap per Teller dari transaksi berstatus sukses atau reversed.
3. Sistem menampilkan laporan bulanan.
4. Administrator dapat mencetak laporan (UC-36).

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Format bulan tidak valid | Sistem memakai bulan berjalan. |

#### UC-36 — Mencetak Laporan Bulanan

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mencetak laporan bulanan untuk arsip. |
| Prakondisi | Laporan bulanan sudah ditampilkan. |
| Pemicu | Administrator memilih cetak. |
| Pascakondisi | Halaman laporan siap cetak; tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `MonthlyReportController@print` |

**Alur utama**

1. Administrator memilih cetak dari laporan bulanan.
2. Sistem menampilkan versi cetak dengan filter bulan yang sama.
3. Administrator mencetak laporan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Bulan tidak valid | Sistem memakai bulan berjalan. |

#### UC-37 — Melihat Dasbor Analitik

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator memantau kondisi bank secara ringkas. |
| Prakondisi | Administrator sudah masuk. |
| Pemicu | Administrator membuka dasbor. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\DashboardController@index`, `AnalyticsService` |

**Alur utama**

1. Administrator membuka dasbor.
2. Sistem menampilkan ringkasan analitik, tren tujuh hari, penabung dengan setoran terbesar, dan kinerja Teller.
3. Sistem menampilkan jumlah laporan menunggu validasi, laporan disetujui, jumlah transaksi sukses hari ini, dan lima audit log terbaru.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Belum ada data transaksi | Nilai ditampilkan nol atau kosong. |

### 5.7 Modul Administrator — Kelulusan dan Kenaikan Kelas

#### UC-38 — Memproses Kenaikan Kelas

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator menaikkan kelas anggota dan penabung pada pergantian tahun ajaran. |
| Prakondisi | Data kelas terisi; tahun ajaran berjalan diketahui. |
| Pemicu | Administrator membuka menu Kenaikan Kelas dan mengirim formulir. |
| Pascakondisi | Kelas anggota dan penabung berubah sesuai pilihan; tahun ajaran proses terakhir tersimpan; audit tercatat. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\ClassPromotionController`, `ClassPromotionService::apply`, `StoreClassPromotionRequest` |

**Alur utama**

1. Administrator membuka halaman kenaikan kelas.
2. Sistem mengelompokkan anggota tanpa rekening dan penabung aktif/diblokir per kelas, termasuk kelas yang belum terisi.
3. Administrator memilih tindakan per kelas: naik ke tingkat berikutnya atau tinggal kelas.
4. Administrator dapat mencentang siswa tertentu untuk tinggal kelas meskipun kelasnya naik.
5. Bila tahun ajaran ini pernah diproses, Administrator wajib mencentang konfirmasi pengulangan.
6. Sistem membaca seluruh sasaran lebih dulu agar satu siswa tidak diproses dua kali.
7. Dalam satu transaksi basis data, sistem memperbarui kelas anggota, penabung, dan anggota tertaut.
8. Sistem menyimpan tahun ajaran terakhir dan mencatat audit berisi pemetaan kelas, jumlah siswa, dan siswa yang tinggal kelas.
9. Sistem menampilkan ringkasan hasil.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Tahun ajaran sudah diproses tanpa konfirmasi | Proses ditolak. |
| A2 | Tingkat kelas tidak dikenali | Baris kelas dilewati dan dihitung pada ringkasan. |
| A3 | Tidak ada siswa yang bergerak | Ringkasan menyatakan belum ada yang dinaikkan. |

#### UC-39 — Memproses Kelulusan Angkatan

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengarsipkan satu angkatan sebagai alumni. |
| Prakondisi | Angkatan keluar dipilih. |
| Pemicu | Administrator mengirim formulir kelulusan angkatan. |
| Pascakondisi | Anggota tanpa saldo diarsipkan; penabung yang masih bersaldo tetap menunggu penyelesaian di loket; audit tercatat. |
| Kanal | Web. |
| Relasi | Di-«extend» oleh UC-44. |
| Implementasi | `Admin\GraduationController@store`, `GraduationService::graduateBatch`, `GraduateBatchRequest` |

**Alur utama**

1. Administrator memilih angkatan keluar.
2. Sistem mengambil seluruh anggota pada angkatan tersebut beserta penabung dan rekeningnya.
3. Sistem menentukan status setiap anggota: alumni, hanya daftar anggota, siap diarsipkan, menunggu pencairan di loket, atau menunggu persetujuan penutupan.
4. Sistem mengarsipkan anggota yang siap: rekening ditutup bila aktif, penabung menjadi alumni, token mobile dihapus, dan anggota ditandai lulus.
5. Sistem melewati penabung yang saldonya masih di atas saldo mengendap dan menghitungnya sebagai pekerjaan loket.
6. Sistem mencatat audit dan menampilkan ringkasan jumlah diarsipkan serta jumlah yang masih menunggu.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Anggota sudah alumni | Dilewati. |
| A2 | Saldo masih di atas saldo mengendap | Tidak diarsipkan; harus dicairkan di loket. |
| A3 | Permintaan penutupan masih menunggu | Anggota dibiarkan sampai keputusan Administrator. |

#### UC-40 — Menandai Anggota Lulus

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengarsipkan satu anggota tanpa memproses seluruh angkatan. |
| Prakondisi | Anggota belum lulus. |
| Pemicu | Administrator memilih tombol lulus pada satu anggota. |
| Pascakondisi | Anggota menjadi alumni; rekening ditutup bila saldonya tinggal saldo mengendap. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\GraduationController@graduateMember`, `GraduationService::graduateMember` |

**Alur utama**

1. Administrator memilih anggota pada halaman kelulusan.
2. Sistem memeriksa bahwa anggota belum lulus.
3. Bila rekening aktif dan saldonya masih di atas saldo mengendap, sistem menolak.
4. Bila rekening aktif dan saldonya tinggal saldo mengendap, sistem menutup rekening dan menandai penabung alumni.
5. Sistem menandai anggota lulus dan mencatat audit.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Anggota sudah alumni | Tidak ada perubahan. |
| A2 | Saldo masih di atas saldo mengendap | Penandaan lulus ditolak. |

#### UC-41 — Menandai Anggota Tidak Lulus

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator membatalkan kelulusan dan mengembalikan anggota ke angkatan berikutnya. |
| Prakondisi | Anggota belum diarsipkan sebagai alumni dan angkatan pada layar masih sesuai data. |
| Pemicu | Administrator mengirim formulir tidak lulus. |
| Pascakondisi | Pencairan yang sudah dilakukan dibatalkan; anggota masuk angkatan berikutnya; audit tercatat. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\GraduationController@notGraduate`, `GraduationService::notGraduate`, `NotGraduateRequest` |

**Alur utama**

1. Administrator memilih anggota dan mengisi angkatan yang diharapkan serta alasan (opsional).
2. Sistem mengunci data anggota dan memastikan belum alumni serta angkatannya belum berubah.
3. Bila ada permintaan penutupan yang menunggu, sistem menolaknya dan membatalkan pencairan melalui jurnal pembalik.
4. Sistem menggeser angkatan keluar anggota ke tahun berikutnya.
5. Sistem mencatat audit berisi angkatan lama, angkatan baru, dan status pembatalan pencairan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Anggota sudah alumni | Tindakan ditolak. |
| A2 | Data angkatan sudah berubah | Tindakan ditolak; halaman harus dimuat ulang. |
| A3 | Angkatan tidak berbentuk tahun 4 digit | Angkatan baru dikosongkan dan Administrator diminta mengisinya manual. |

#### UC-42 — Menyetujui Permintaan Penutupan Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengesahkan penutupan rekening dan status alumni penabung. |
| Prakondisi | Permintaan penutupan berstatus menunggu; rekening aktif; sisa saldo tidak melebihi saldo mengendap. |
| Pemicu | Administrator memilih setujui pada permintaan penutupan. |
| Pascakondisi | Rekening ditutup, penabung dan anggota menjadi alumni, token mobile dihapus, audit tercatat. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\GraduationController@approveClosure`, `AccountClosureService::approve`, `ApproveClosureRequest` |

**Alur utama**

1. Administrator membuka permintaan penutupan.
2. Administrator mengisi catatan keputusan (opsional, maksimal 500 karakter).
3. Sistem mengunci permintaan dan memastikan statusnya masih menunggu.
4. Sistem memastikan rekening masih aktif dan saldonya tidak melebihi saldo mengendap.
5. Sistem menutup rekening dan menandai penabung serta anggota sebagai alumni.
6. Sistem menghapus token aplikasi mobile penabung.
7. Sistem mencatat keputusan, penyetuju, waktu, dan audit.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Permintaan sudah diputuskan | Persetujuan ditolak. |
| A2 | Rekening tidak aktif | Persetujuan ditolak. |
| A3 | Saldo masih di atas saldo mengendap | Persetujuan ditolak; Teller harus mencairkan lebih dulu. |

#### UC-43 — Menolak Permintaan Penutupan Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator membatalkan permintaan penutupan dan mengembalikan saldo penabung. |
| Prakondisi | Permintaan penutupan berstatus menunggu. |
| Pemicu | Administrator mengirim formulir penolakan. |
| Pascakondisi | Pencairan dibatalkan, saldo kembali, rekening tetap aktif, audit tercatat. |
| Kanal | Web. |
| Relasi | — |
| Implementasi | `Admin\GraduationController@rejectClosure`, `AccountClosureService::reject`, `RejectClosureRequest` |

**Alur utama**

1. Administrator membuka permintaan penutupan.
2. Administrator mengisi alasan penolakan (wajib, maksimal 500 karakter).
3. Sistem memastikan permintaan masih menunggu.
4. Bila ada transaksi pencairan yang sukses, sistem membuat jurnal pembalik agar saldo penabung kembali.
5. Sistem menandai permintaan ditolak beserta penyetuju, waktu, dan alasan.
6. Sistem mencatat audit.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Alasan kosong | Validasi menolak. |
| A2 | Permintaan sudah diputuskan | Penolakan ditolak. |

#### UC-44 — Mengunduh Arsip Kelulusan

| Atribut | Keterangan |
|---|---|
| Aktor | Administrator |
| Tujuan | Administrator mengunduh berkas arsip alumni untuk serah terima ke sekolah. |
| Prakondisi | Minimal satu alumni tercatat pada angkatan yang dipilih. |
| Pemicu | Administrator memilih tombol unduh arsip. |
| Pascakondisi | Berkas CSV tersimpan pada penyimpanan lokal dan terunduh; audit ekspor tercatat. |
| Kanal | Web. |
| Relasi | «extend» UC-39. |
| Implementasi | `Admin\GraduationController@export` |

**Alur utama**

1. Administrator memilih angkatan (boleh kosong untuk semua angkatan).
2. Sistem memastikan angkatan yang dipilih ada pada data anggota.
3. Sistem mengumpulkan anggota yang sudah lulus atau penabungnya berstatus alumni.
4. Sistem menuliskan CSV berisi NIS, nama, angkatan, kelas, tanggal lulus, nomor rekening, status rekening, saldo akhir, tanggal penutupan, dan status penabung.
5. Sistem menyimpan berkas pada `storage/app/backups/alumni` dan mengirimkannya sebagai unduhan.
6. Sistem mencatat audit `alumni_archive_exported`.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Angkatan tidak ada pada data anggota | Unduhan ditolak dengan pesan. |
| A2 | Belum ada alumni | Unduhan ditolak dengan pesan. |

### 5.8 Modul Nasabah — Layanan Mandiri

#### UC-45 — Melihat Saldo dan Ringkasan Rekening

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah mengetahui saldo dan kondisi rekeningnya. |
| Prakondisi | Nasabah sudah masuk dan memiliki rekening. |
| Pemicu | Nasabah membuka dasbor web atau beranda aplikasi. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web nasabah dan aplikasi Flutter. |
| Relasi | — |
| Implementasi | `Nasabah\DashboardController@index`, `Mobile\DashboardController@index` |

**Alur utama**

1. Nasabah membuka dasbor.
2. Sistem menampilkan data rekening milik nasabah: nomor rekening, tahun ajaran, saldo awal, saldo berjalan, saldo mengendap minimum, dan status.
3. Sistem menampilkan transaksi terakhir bila ada.
4. Pada aplikasi, sistem juga menampilkan ringkasan transaksi masuk dan keluar terbaru.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Nasabah belum memiliki rekening | Dasbor tampil tanpa data rekening dan tanpa error. |
| A2 | Sesi atau token tidak berlaku | Sistem mengarahkan ke halaman masuk. |

#### UC-46 — Melihat Riwayat Mutasi

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah menelusuri transaksi yang pernah terjadi pada rekeningnya. |
| Prakondisi | Nasabah sudah masuk dan memiliki rekening. |
| Pemicu | Nasabah membuka menu Mutasi. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Web nasabah dan aplikasi Flutter. |
| Relasi | Di-«extend» oleh UC-47. |
| Implementasi | `Nasabah\MutasiController@index`, `Mobile\MutasiController@index` |

**Alur utama**

1. Nasabah membuka menu mutasi.
2. Sistem menampilkan transaksi keluar dan masuk milik rekening nasabah, termasuk transfer masuk.
3. Nasabah memfilter jenis transaksi dan rentang tanggal.
4. Sistem menampilkan 15 baris per halaman.
5. Nasabah membuka detail satu transaksi bila diperlukan.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Belum ada transaksi | Daftar kosong dengan pesan informatif. |
| A2 | Nasabah membuka data nasabah lain | Akses ditolak; data selalu dibatasi pada rekening miliknya. |

#### UC-47 — Melihat dan Mengunduh Rekening Koran

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah memperoleh rekening koran resmi untuk rentang tanggal tertentu. |
| Prakondisi | Nasabah sudah masuk dan memiliki rekening. |
| Pemicu | Nasabah membuka menu Rekening Koran. |
| Pascakondisi | Dokumen rekening koran ditampilkan atau diunduh; tidak ada perubahan data. |
| Kanal | Web nasabah (cetak) dan aplikasi Flutter (PDF). |
| Relasi | «extend» UC-46. |
| Implementasi | `Nasabah\MutasiController@statement`, `Mobile\MutasiController@statement`, `StatementPdfService` |

**Alur utama**

1. Nasabah memilih rentang tanggal; sistem memakai awal bulan sampai hari ini bila tidak diisi.
2. Sistem mengambil transaksi sukses pada rentang tersebut secara berurutan.
3. Sistem menghitung ringkasan setoran, penarikan, transfer keluar, dan transfer masuk.
4. Sistem membentuk kode verifikasi dokumen dari data rekening, rentang, jumlah transaksi, dan total.
5. Sistem menampilkan rekening koran yang siap dicetak; pada aplikasi, dokumen disimpan atau dibagikan sebagai PDF.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Rekening tidak ditemukan | HTTP 404. |
| A2 | Rentang tanggal terbalik | Data pada rentang tersebut kosong; tidak ada transaksi yang ditampilkan. |

#### UC-48 — Melihat Kartu Nasabah Virtual

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah menampilkan kartu ber-QR untuk keperluan layanan loket. |
| Prakondisi | Nasabah sudah masuk dan memiliki rekening. |
| Pemicu | Nasabah membuka menu Kartu. |
| Pascakondisi | Kartu ditampilkan; tidak ada perubahan data. |
| Kanal | Web nasabah dan aplikasi Flutter. |
| Relasi | — |
| Implementasi | `Nasabah\DashboardController@printCard`, `QrCodeController`, `Mobile\DashboardController@card` |

**Alur utama**

1. Nasabah membuka menu kartu.
2. Sistem menampilkan kartu berisi nama, nomor siswa, nomor rekening, dan QR unik penabung.
3. Nasabah dapat mencetak kartu atau mengunduh gambar QR.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Nasabah belum memiliki rekening | Sistem menampilkan pesan dan kembali ke dasbor. |
| A2 | Data QR kosong | Permintaan QR ditolak dengan HTTP 422. |

#### UC-49 — Melihat Profil Nasabah

| Atribut | Keterangan |
|---|---|
| Aktor | Nasabah |
| Tujuan | Nasabah memeriksa identitas dan status akunnya. |
| Prakondisi | Nasabah sudah masuk melalui aplikasi. |
| Pemicu | Nasabah membuka tab Profil. |
| Pascakondisi | Tidak ada perubahan data. |
| Kanal | Aplikasi Flutter. |
| Relasi | — |
| Implementasi | `Mobile\AuthController@me`, `mobile/lib/screens/profile_screen.dart` |

**Alur utama**

1. Nasabah membuka tab profil.
2. Sistem menampilkan nama lengkap, nomor siswa, kelas, nomor telepon, dan status akun.
3. Sistem menampilkan tautan ke ubah PIN, rekening koran, dan keluar.

**Alur alternatif dan pengecualian**

| Kode | Kondisi | Perlakuan sistem |
|---|---|---|
| A1 | Token kedaluwarsa | Sistem mengarahkan ke layar masuk. |

---

## 6. Matriks Aktor–Use Case

Keterangan: ✓ berarti aktor menjalankan use case; (i) berarti use case dijalankan sebagai bagian dari use case lain.

| Use Case | Administrator | Teller | Nasabah | Layanan Email |
|---|---|---|---|---|
| UC-01 Masuk ke Aplikasi | ✓ | ✓ | ✓ | |
| UC-02 Keluar dari Aplikasi | ✓ | ✓ | ✓ | |
| UC-03 Meminta Kode Verifikasi | ✓ | ✓ | ✓ | ✓ |
| UC-04 Mengatur Ulang Password | ✓ | ✓ | ✓ | |
| UC-05 Mengubah PIN Transaksi | | | ✓ | |
| UC-06 Mereset PIN Penabung | ✓ | | | |
| UC-07 Mengelola Akun Pengguna Internal | ✓ | | | |
| UC-08 Mengelola Data Anggota | ✓ | | | |
| UC-09 Mengimpor Anggota dari CSV | ✓ | | | |
| UC-10 Mengelola Data Penabung | ✓ | | | |
| UC-11 Melihat Detail Rekening | ✓ | | | |
| UC-12 Mencetak Kartu Nasabah | ✓ | | | |
| UC-13 Mengatur Konfigurasi Sistem | ✓ | | | |
| UC-14 Mencari Data Nasabah | | ✓ | | |
| UC-15 Memindai QR Code Kartu | | ✓ | | |
| UC-16 Mendaftarkan Anggota Baru | | ✓ | | |
| UC-17 Mengaktifkan Rekening Anggota | | ✓ | | |
| UC-18 Melihat Ringkasan Loket Harian | | ✓ | | |
| UC-19 Mencatat Setoran Tunai | | ✓ | | |
| UC-20 Mencatat Penarikan Tunai | | ✓ | | |
| UC-21 Mencatat Transfer Antar Rekening | | ✓ | | |
| UC-22 Mencetak Struk Transaksi | | ✓ | | |
| UC-23 Mencetak Buku Tabungan | | ✓ | | |
| UC-24 Mengajukan Penutupan Rekening | | ✓ | | |
| UC-25 Membuat Laporan Kas Harian | | ✓ | | |
| UC-26 Mencetak Laporan Kas Harian | | ✓ | | |
| UC-27 Memeriksa Laporan Kas Harian | ✓ | | | |
| UC-28 Menyetujui Laporan Kas Harian | ✓ | | | |
| UC-29 Memindahkan Kas ke Brankas | ✓ | | | |
| UC-30 Menolak Laporan Kas Harian | ✓ | | | |
| UC-31 Mengoreksi Transaksi | ✓ | | | |
| UC-32 Melihat Jurnal Akuntansi | ✓ | | | |
| UC-33 Melihat Buku Besar dan Neraca Saldo | ✓ | | | |
| UC-34 Mengekspor Laporan Akuntansi | ✓ | | | |
| UC-35 Melihat Laporan Bulanan | ✓ | | | |
| UC-36 Mencetak Laporan Bulanan | ✓ | | | |
| UC-37 Melihat Dasbor Analitik | ✓ | | | |
| UC-38 Memproses Kenaikan Kelas | ✓ | | | |
| UC-39 Memproses Kelulusan Angkatan | ✓ | | | |
| UC-40 Menandai Anggota Lulus | ✓ | | | |
| UC-41 Menandai Anggota Tidak Lulus | ✓ | | | |
| UC-42 Menyetujui Penutupan Rekening | ✓ | | | |
| UC-43 Menolak Penutupan Rekening | ✓ | | | |
| UC-44 Mengunduh Arsip Kelulusan | ✓ | | | |
| UC-45 Melihat Saldo dan Ringkasan | | | ✓ | |
| UC-46 Melihat Riwayat Mutasi | | | ✓ | |
| UC-47 Melihat Rekening Koran | | | ✓ | |
| UC-48 Melihat Kartu Nasabah Virtual | | | ✓ | |
| UC-49 Melihat Profil Nasabah | | | ✓ | |

---

## 7. Ketertelusuran ke Implementasi dan Pengujian

Tabel ini menghubungkan use case dengan fase pada `task.md`, berkas implementasi utama, dan
berkas pengujian yang relevan. Kolom fase menunjukkan fase penambahan fitur, bukan satu-satunya
fase yang menyentuh use case tersebut.

| Use Case | Fase (task.md) | Implementasi utama | Pengujian |
|---|---|---|---|
| UC-01 | 1 | `Auth\LoginController`, `Mobile\AuthController` | `AuthRbacTest`, `MobileAuthTest` |
| UC-02 | 1 | `Auth\LoginController@logout`, `Mobile\AuthController@logout` | `AuthRbacTest`, `MobileAuthTest` |
| UC-03 | 1 | `Auth\ForgotPasswordController@requestOtp`, `CredentialRecoveryService` | `ForgotPasswordTest`, `CredentialRecoveryServiceTest` |
| UC-04 | 1 | `CredentialRecoveryService::verifyAndReset` | `ForgotPasswordTest`, `CredentialRecoveryConcurrentResetTest` |
| UC-05 | 1, 10 | `SaverCredentialService`, `Nasabah\PinController` | `PinChangeTest`, `MobilePinChangeTest`, `PinRateLimitTest` |
| UC-06 | 2, 12 | `Admin\SaverController@update` | `SaverPinResetTest` |
| UC-07 | 2 | `Admin\UserController` | `AuthorizationMatrixTest`, `UserDeletionGuardTest` |
| UC-08 | 11 | `Admin\MemberController`, `MemberRegistrationService` | `MemberCreationTest`, `MemberListFilterTest` |
| UC-09 | 11 | `MemberImportService` | `MemberImportTest` |
| UC-10 | 2, 11 | `Admin\SaverController` | `SaverListFilterTest`, `SaverAlumniLockTest` |
| UC-11 | 2 | `Admin\AccountController@show` | `AccountDetailTest` |
| UC-12 | 6 | `Admin\AccountController@printCard`, `QrCodeService` | `CardPrintTest`, `QrCardTest` |
| UC-13 | 2, 9 | `Admin\SystemConfigController` | `SystemConfigTest` |
| UC-14 | 3, 9 | `Teller\MemberSearchController` | `SearchTest`, `SearchByNameTest`, `SearchEdgeCasesTest` |
| UC-15 | 3 | `resources/js/qr-scanner.js` | `SearchTest`, `QrCardTest` |
| UC-16 | 9 | `Teller\MemberRegistrationController` | `CustomerRegistrationTest`, `RegistrationEdgeCasesTest` |
| UC-17 | 9 | `MemberActivationService`, `AccountService` | `MemberActivationTest` |
| UC-18 | 9 | `resources/views/teller/dashboard.blade.php` | `DashboardTest` |
| UC-19 | 3 | `TransactionService::deposit` | `DepositTest`, `DuplicateSlipTest`, `SlipNumberGenerationTest` |
| UC-20 | 3 | `TransactionService::withdraw` | `WithdrawalTest`, `AccountMinimumBalanceTest` |
| UC-21 | 6 | `TransactionService::transfer` | `TransferTransactionTest` |
| UC-22 | 3, 9 | `Teller\TransactionController@receipt` | `ReceiptTest`, `ReceiptEnrichmentTest` |
| UC-23 | 6 | `Teller\PassbookController` | `PassbookTest`, `PassbookReversalTest` |
| UC-24 | 11 | `AccountClosureService::request` | `AccountClosureTest` |
| UC-25 | 4 | `DailyReportService::createReport` | `ClosingTest`, `PostSubmissionTransactionGuardTest` |
| UC-26 | 4 | `Teller\ClosingController@print` | `ClosingTest` |
| UC-27 | 4 | `Admin\DailyReportController@index`, `@show` | `DailyReportTest` |
| UC-28 | 4, 12 | `DailyReportService::approve` | `DailyReportTest`, `CashSweepIntegrityTest` |
| UC-29 | 4, 7 | `DailyReportService::approve`, `VaultJournalTest` | `VaultJournalTest`, `CashSweepIntegrityTest` |
| UC-30 | 4 | `DailyReportService::reject` | `DailyReportTest` |
| UC-31 | 9, 12 | `ReversalService::reverse` | `ReversalTest`, `ReversalIntegrityTest`, `ReversalCashflowTest` |
| UC-32 | 5 | `JournalController@index` | `JournalTest` |
| UC-33 | 7 | `AccountingReportController@generalLedger`, `AccountingService` | `AccountingReportTest` |
| UC-34 | 7 | `AccountingReportController@exportCsv` | `AccountingReportTest` |
| UC-35 | 11 | `MonthlyReportController@index` | `MonthlyReportTest` |
| UC-36 | 11 | `MonthlyReportController@print` | `MonthlyReportTest` |
| UC-37 | 8 | `Admin\DashboardController`, `AnalyticsService` | `DashboardTest` |
| UC-38 | 11 | `ClassPromotionService::apply` | `ClassPromotionTest`, `ClassRoomTest` |
| UC-39 | 11 | `GraduationService::graduateBatch` | `GraduationTest` |
| UC-40 | 11 | `GraduationService::graduateMember` | `GraduationTest` |
| UC-41 | 11 | `GraduationService::notGraduate` | `GraduationTest` |
| UC-42 | 11 | `AccountClosureService::approve` | `GraduationTest`, `AccountClosureTest` |
| UC-43 | 11 | `AccountClosureService::reject` | `AccountClosureTest` |
| UC-44 | 11 | `Admin\GraduationController@export` | `GraduationTest` |
| UC-45 | 5, 10 | `Nasabah\DashboardController`, `Mobile\DashboardController` | `DashboardTest`, `MobileDashboardTest` |
| UC-46 | 5, 10 | `Nasabah\MutasiController`, `Mobile\MutasiController` | `MutasiTest`, `MobileMutasiTest` |
| UC-47 | 6, 10 | `Nasabah\MutasiController@statement`, `StatementPdfService` | `StatementTest` |
| UC-48 | 6, 10 | `Nasabah\DashboardController@printCard`, `QrCodeController` | `QrCardTest`, `CardPrintTest` |
| UC-49 | 10 | `Mobile\AuthController@me` | `MobileAuthTest` |

---

## 8. Catatan Pemeliharaan

1. **Kode UC adalah kunci.** Bila satu use case dipecah, beri kode baru (misalnya UC-50)
   dan jangan memakai ulang kode lama, agar matriks dan laporan pengujian tetap dapat
   ditelusuri.
2. **Ubah diagram dan dokumen bersama.** Nama pada `task/use-case-bank-mini.drawio` dan
   dokumen ini harus sama. Perbedaan nama akan menyulitkan penilaian SRS.
3. **Jangan menambah use case tanpa bukti implementasi.** Setiap use case pada dokumen
   ini merujuk pada route, controller, atau service yang ada. Use case yang belum
   diimplementasikan sebaiknya ditulis terpisah sebagai "rencana" agar tidak dianggap
   fitur jadi.
4. **Relasi «include» dan «extend» mengikuti arah UML.** «include» dari use case sumber
   ke use case yang selalu dijalankan; «extend» dari use case tambahan ke use case dasar.
   Pada halaman 02, UC-09 memperluas UC-08; pada halaman 04, UC-26 memperluas UC-25.
5. **Gambar untuk laporan.** Ekspor setiap halaman sebagai PNG dengan latar transparan,
   lalu letakkan pada subbab use case di SRS. Halaman 00 tidak perlu dimasukkan ke
   laporan; cukup dipakai sebagai legenda internal.

### Asumsi

- Pembagian halaman diagram dilakukan agar terbaca, bukan pemisahan subsistem. Seluruh
  halaman merupakan satu model use case untuk satu sistem.
- Aktor Layanan Email dianggap tersedia dan dapat mengirim email. Kegagalan pengiriman
  email tidak dimodelkan sebagai alur alternatif karena aplikasi tidak menanganinya
  secara khusus.
- Aplikasi mobile memakai use case yang sama dengan web nasabah; perbedaan teknis token
  dan sesi tidak mengubah tujuan aktor.
