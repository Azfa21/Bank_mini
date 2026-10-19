PRD — Sistem Informasi E-Teller Bank Mini Sekolah
1. Overview
Tujuan Aplikasi
Membangun aplikasi Sistem Informasi E-Teller Bank Mini Sekolah berbasis client-server untuk mengelola rekening nasabah, transaksi setoran dan penarikan tunai, rekonsiliasi kas, persetujuan Supervisor, serta pencatatan jurnal akuntansi secara aman dan terintegrasi.
Masalah yang Diselesaikan
- Pencatatan transaksi loket yang masih manual dan rawan kesalahan.
- Ketidaksesuaian antara uang fisik dan saldo digital.
- Risiko penarikan melebihi saldo atau melanggar saldo mengendap minimum.
- Tidak adanya pemisahan tanggung jawab antara Administrator, Teller, Supervisor, dan Nasabah.
- Tidak tersedianya audit trail dan jurnal akuntansi yang konsisten.
- Kesulitan pencarian profil nasabah secara cepat di loket.
Target Pengguna
1. Administrator
- Mengelola akun internal, rekening nasabah, konfigurasi sistem, dan audit jurnal.
- Tidak dapat melakukan transaksi setoran maupun penarikan.
2. Piket Teller
- Melayani pencarian nasabah, setoran tunai, penarikan tunai, dan pembuatan laporan harian.
- Tidak dapat mengubah atau menghapus transaksi yang telah tersimpan.
3. Supervisor / Kepala Bank Mini
- Memvalidasi laporan, menyelesaikan selisih kas, menyetujui laporan, dan mengotorisasi pemindahan kas.
- Tidak dapat melakukan transaksi loket secara mandiri.
4. Nasabah / Siswa
- Melihat saldo dan riwayat mutasi rekening sendiri.
- Memasukkan PIN 6-digit untuk otorisasi penarikan.
2. Requirements
2.1 Persyaratan Platform
- Aplikasi dapat digunakan melalui browser pada komputer loket dan perangkat pengguna yang didukung.
- Sistem berjalan pada arsitektur client-server.
- Sistem menggunakan database relasional dengan dukungan transaksi, foreign key, check constraint, dan engine transaksional.
- Aplikasi mendukung penggunaan kamera atau webcam untuk pemindaian QR Code.
- Sistem dapat mencetak struk transaksi dan Laporan Harian Teller.
- Sistem dapat dijalankan pada lingkungan Windows atau Linux Desktop.
- Sistem dapat digunakan melalui jaringan lokal sekolah atau jaringan yang dikonfigurasi administrator.
2.2 Jenis dan Jumlah Pengguna
- Sistem memiliki empat role:
- Administrator
- Teller
- Supervisor
- Nasabah
- Satu akun internal hanya memiliki satu role aktif pada satu waktu.
- Nasabah hanya dapat mengakses data rekening miliknya sendiri.
- Administrator dapat membuat, memperbarui, memblokir, dan menghapus akun internal sesuai aturan keamanan.
- Data transaksi yang sudah tersimpan tidak dapat diubah atau dihapus oleh Teller.
2.3 Persyaratan Keamanan
- Seluruh pengguna wajib melewati autentikasi.
- Password pengguna internal wajib disimpan menggunakan password hashing.
- Sistem wajib menerapkan session atau token guard.
- Setiap permintaan ke server wajib memvalidasi role sebelum query atau operasi data dijalankan.
- Akses langsung ke URL yang tidak sesuai role harus ditolak.
- PIN nasabah terdiri dari tepat 6 digit.
- PIN tidak boleh ditampilkan atau disimpan dalam bentuk teks biasa.
- Administrator tidak boleh mengakses fungsi transaksi loket.
- Teller tidak dapat menyetujui laporan hariannya sendiri.
- Supervisor tidak dapat melakukan transaksi setoran atau penarikan.
- Jejak aktivitas penting wajib dicatat dalam audit log.
- Sistem menerapkan Security Headers (CSP, no-store cache) untuk mencegah clickjacking dan browser caching halaman terautentikasi.
- Sistem membatasi penggunaan akses kamera hanya pada origin sendiri (Permissions-Policy: camera=(self)).
2.4 Persyaratan Validasi Data
- Nominal transaksi harus lebih besar dari Rp0.
- Penarikan hanya dapat dilakukan jika:
- Saldo mencukupi.
- Saldo setelah penarikan tidak kurang dari Rp10.000.
- PIN nasabah valid.
- Transaksi setoran hanya dapat disimpan jika nominal slip sama dengan uang fisik.
- Rekening harus ditemukan dan berstatus aktif sebelum transaksi diproses.
- Laporan harian tidak dapat dicetak sebagai laporan final jika terjadi selisih kas.
- Foreign key wajib menjaga histori transaksi dan jurnal.
- Penghapusan data master yang masih direferensikan transaksi harus ditolak.
2.5 Persyaratan Akuntansi
- Setiap transaksi berhasil harus menghasilkan dua baris jurnal akuntansi.
- Jurnal wajib seimbang antara debit dan kredit.
- Setoran dan penarikan harus memperbarui saldo rekening dan jurnal dalam satu transaksi database.
- Jika salah satu proses gagal, seluruh perubahan harus dibatalkan melalui rollback.
- Kode akun yang digunakan:
- Kas: 101
- Tabungan: 201
3. Core Features
FASE 1 — Autentikasi, RBAC, dan Keamanan Akses
Status
Direncanakan
Sub-Fitur
- Login terpusat pengguna internal dan nasabah
- Manajemen sesi atau token pengguna
- Pembatasan akses berdasarkan role
- Keamanan kredensial dan PIN nasabah
- Pemulihan kredensial mandiri (Lupa Password & Reset PIN)
- Pencatatan aktivitas akses
Requirements Fitur
1. Login terpusat
- Pengguna memasukkan username atau identitas rekening dan password/PIN sesuai jenis akunnya.
- Sistem memeriksa status akun.
- Sistem mengarahkan pengguna ke halaman sesuai role.
2. Manajemen sesi atau token pengguna
- Sistem membuat sesi setelah login berhasil.
- Sesi memiliki waktu kedaluwarsa.
- Logout harus menghapus atau membatalkan sesi aktif.
3. Pembatasan akses berdasarkan role
- Administrator dapat mengakses manajemen sistem.
- Teller dapat mengakses transaksi loket.
- Supervisor dapat mengakses validasi dan approval.
- Nasabah hanya dapat mengakses data rekening sendiri.
4. Keamanan kredensial dan PIN nasabah
- Password dan PIN disimpan dalam bentuk hash.
- Percobaan autentikasi gagal dapat dicatat.
- PIN harus diverifikasi sebelum penarikan diproses.
5. Pencatatan aktivitas akses
- Sistem mencatat login, logout, penolakan akses, dan aktivitas penting.
6. Pemulihan kredensial mandiri (Lupa Password & Reset PIN)
- Pengguna internal (Staff) dapat memulihkan password dengan verifikasi username dan nama lengkap terdaftar, dengan password baru minimal 8 karakter.
- Nasabah (Siswa) dapat memulihkan PIN dengan verifikasi nomor siswa (NIS) dan kelas terdaftar, dengan PIN baru tepat 6 digit angka.
- Sistem menolak pemulihan untuk akun yang berstatus diblokir (blocked).
- Seluruh aktivitas pemulihan (berhasil, gagal, diblokir) dicatat dalam audit log dan dilindungi rate limiting (5 req/menit).
Field Wajib
- username
- password
- role
- account_status
- customer_account_number
- customer_pin
- session_identifier
- login_timestamp
FASE 2 — Manajemen Pengguna dan Rekening Nasabah
Status
Direncanakan
Sub-Fitur
- Pengelolaan akun Administrator, Teller, dan Supervisor
- Pengelolaan profil nasabah atau siswa
- Pembuatan nomor rekening otomatis
- Pengaturan saldo mengendap minimum
- Konfigurasi perangkat pemindai QR Code
Requirements Fitur
1. Pengelolaan akun internal
- Administrator dapat menambah, melihat, memperbarui, memblokir, dan menghapus akun.
- Role yang tersedia adalah Administrator, Teller, dan Supervisor.
- Akun yang diblokir tidak dapat login.
2. Pengelolaan profil nasabah
- Administrator dapat membuat dan mengelola data nasabah atau siswa.
- Daftar Anggota hanya menampilkan siswa yang belum memiliki rekening penabung dan belum lulus. Siswa yang sudah menjadi penabung dikelola di daftar Penabung, dan yang sudah lulus di halaman Kelulusan.
- Status rekening anggota diatur sistem: `account_inactive` saat didaftarkan, lalu `account_active` setelah aktivasi di Teller. Administrator tidak mengubahnya lewat form Anggota.
- Status penabung: `active` (dapat bertransaksi), `blocked` (dihentikan sementara), dan `graduated` (alumni, rekening sudah ditutup).
- Penabung tidak dihapus dari daftar. Untuk menghentikan aktivitasnya sementara, Administrator memblokir penabung: rekeningnya tidak dapat ditransaksikan dan penabung tidak dapat login.
- Profil nasabah dapat dihubungkan dengan satu rekening aktif.
- Data penabung, rekening, dan riwayat transaksi disimpan permanen, termasuk setelah siswa lulus. Tidak ada penghapusan baris; yang berubah hanya status dan kolom `graduated_at`.
- Saat siswa lulus, Teller mencairkan seluruh saldo di loket (termasuk saldo mengendap) lewat penutupan rekening, lalu Administrator menyetujui. Persetujuan menutup rekening (`closed`) dan menandai penabung sebagai alumni (`graduated`).
- Penolakan penutupan mengembalikan saldo dan mengaktifkan kembali rekening. Alumni tidak dapat login, baik di web maupun aplikasi nasabah.
- Administrator dapat mengarsipkan satu angkatan sekaligus dari halaman Kelulusan. Anggota tanpa rekening dan penabung bersaldo nol langsung diarsipkan; penabung yang masih bersaldo menunggu penyelesaian di loket.
- Perintah `php artisan bankmini:graduate --year=Tahun [--dry-run]` menyiapkan arsip angkatan yang sama dari CLI.
3. Pembuatan nomor rekening otomatis
- Nomor rekening dibuat berdasarkan tahun ajaran dan nomor urut siswa.
- Nomor rekening harus unik.
4. Pengaturan saldo mengendap minimum
- Nilai default saldo mengendap minimum adalah Rp10.000.
- Administrator dapat mengatur nilai tersebut melalui konfigurasi sistem.
5. Konfigurasi pemindai QR Code
- Administrator dapat mengatur status penggunaan kamera/webcam.
- Teller dapat mencari nasabah menggunakan QR Code atau nomor rekening.
Field Wajib
Akun internal:
- full_name
- username
- password_hash
- role
- status
Profil nasabah:
- student_number
- full_name
- class_name
- phone_number
- qr_code_value
- status
Rekening:
- account_number
- customer_id
- academic_year
- opening_balance
- current_balance
- minimum_balance
- status
Konfigurasi:
- minimum_balance
- qr_scanner_enabled
- qr_device_identifier
FASE 3 — Operasional Transaksi Teller
Status
Direncanakan
Sub-Fitur
- Autentikasi profil nasabah melalui QR Code atau nomor rekening
- Transaksi setoran tunai
- Transaksi penarikan tunai
- Validasi saldo dan nominal uang fisik
- Pencetakan struk transaksi
Requirements Fitur
1. Autentikasi profil nasabah
- Teller memindai QR Code atau memasukkan nomor rekening.
- Sistem menampilkan profil nasabah dan saldo berjalan.
- Sistem tidak menampilkan data nasabah lain di luar hasil pencarian yang sah.
2. Transaksi setoran tunai
- Teller memeriksa kelengkapan slip setoran.
- Teller memasukkan nominal pada slip dan nominal uang fisik.
- Sistem menolak transaksi jika kedua nominal berbeda.
- Sistem menambah saldo setelah transaksi berhasil.
- Transaksi yang tersimpan dikunci dari perubahan.
3. Transaksi penarikan tunai
- Teller memasukkan nominal penarikan.
- Sistem memeriksa saldo rekening.
- Sistem memeriksa saldo mengendap minimum.
- Nasabah memasukkan PIN 6-digit.
- Sistem mengurangi saldo setelah seluruh validasi berhasil.
4. Validasi saldo dan nominal fisik
- Semua validasi dilakukan di sisi server.
- Nominal tidak boleh bernilai nol atau negatif.
- Rekening harus aktif.
- Penarikan tidak boleh menghasilkan saldo di bawah saldo minimum.
5. Pencetakan struk
- Struk berisi nomor transaksi, tanggal, jenis transaksi, rekening, nominal, dan saldo akhir.
- Struk dapat dicetak setelah transaksi berhasil.
Field Wajib
- transaction_number
- account_id
- teller_id
- transaction_type
- slip_number
- slip_amount
- physical_cash_amount
- amount
- balance_before
- balance_after
- customer_pin_verification
- transaction_status
- transaction_timestamp
FASE 4 — Penutupan Kas dan Approval Supervisor
Status
Selesai
Sub-Fitur
- Rekonsiliasi kas harian Teller
- Pembuatan laporan harian Teller
- Validasi dan persetujuan Supervisor
- Pemindahan tanggung jawab kas ke brankas utama
- Penyelesaian selisih dan audit transaksi
Requirements Fitur
1. Rekap transaksi harian
- Sistem mengelompokkan transaksi berdasarkan Teller dan tanggal kerja.
- Sistem menghitung total setoran dan total penarikan.
2. Rekonsiliasi kas
- Rumus saldo akhir:
Saldo Awal + Total Setoran - Total Penarikan
- Teller memasukkan jumlah uang fisik di laci.
- Sistem membandingkan saldo akhir digital dengan uang fisik.
3. Pembuatan laporan
- Teller dapat membuat dan mencetak draf Laporan Harian Teller jika rekonsiliasi sesuai.
- Laporan berstatus menunggu validasi Supervisor.
4. Approval Supervisor
- Supervisor memeriksa transaksi, total kas, laporan, dan bukti fisik.
- Supervisor dapat menyetujui laporan yang sesuai.
- Teller tidak dapat menyetujui laporan miliknya sendiri.
5. Pemindahan kas ke brankas
- Approval Supervisor memindahkan status tanggung jawab kas dari laci Teller ke brankas utama.
- Sistem mencatat waktu, pengguna, dan nominal pemindahan.
6. Penyelesaian selisih
- Jika terjadi selisih, laporan tidak dapat diselesaikan.
- Supervisor dapat melihat detail transaksi dan audit log untuk investigasi.
- Selisih wajib memiliki status penyelesaian atau catatan pemeriksaan.
Field Wajib
- report_number
- business_date
- teller_id
- opening_cash_balance
- total_deposit
- total_withdrawal
- system_ending_cash
- physical_cash
- cash_difference
- report_status
- supervisor_id
- approval_timestamp
- approval_notes
FASE 5 — Informasi Mandiri Nasabah dan Integritas Akuntansi
Status
Selesai
Sub-Fitur
- Dasbor saldo nasabah
- Riwayat mutasi rekening
- Jurnal akuntansi transaksi
- Mekanisme double-entry
- Konsistensi dan rollback transaksi
Requirements Fitur
1. Dasbor saldo nasabah
- Nasabah dapat melihat saldo berjalan rekening sendiri.
- Data saldo diambil dari rekening yang terautentikasi.
2. Riwayat mutasi rekening
- Nasabah dapat melihat seluruh setoran dan penarikan rekening sendiri.
- Riwayat menampilkan tanggal, jenis transaksi, nominal, dan saldo akhir.
3. Jurnal akuntansi transaksi
- Administrator dan Supervisor dapat melihat jurnal.
- Nasabah dan Teller tidak dapat mengakses jurnal back-end.
4. Mekanisme double-entry
- Satu transaksi operasional menghasilkan dua baris jurnal.
- Total debit harus sama dengan total kredit.
- Setoran dan penarikan mengikuti aturan akun Kas 101 dan Tabungan 201.
5. Konsistensi dan rollback
- Perubahan saldo, transaksi, dan jurnal dilakukan dalam satu database transaction.
- Kegagalan validasi atau penyimpanan membatalkan seluruh proses.
- Transaksi berhasil diberi status final dan tidak dapat diedit Teller.
Field Wajib
- journal_number
- transaction_id
- account_code
- debit_amount
- credit_amount
- journal_description
- journal_timestamp
- journal_status
- rollback_reason
FASE 9 — Penyempurnaan Berbasis Riset Pengguna (Revisi Besar)
Status
Selesai
Latar Belakang
Hasil kuesioner pengguna (Teller, Nasabah, Supervisor, Administrator) menunjukkan kebutuhan
yang belum tercakup: rincian uang kertas & logam, kelengkapan struk,
jurnal koreksi/pembalik, pencarian nasabah yang lebih cepat, Neraca Saldo,
konfigurasi produk Bank Mini, dan registrasi nasabah yang didampingi Teller.
Sub-Fitur
- Rincian uang kertas & logam pada transaksi dan penutupan kas
- Struk transaksi lengkap (nomor referensi jurnal, terbilang, status)
- Jurnal koreksi/pembalik oleh Supervisor
- Pencarian nasabah berdasarkan nama/NIS/nomor rekening
- Registrasi nasabah didampingi Teller
- Neraca Saldo dan Buku Besar
- Konfigurasi produk (nama bank, saldo mengendap, setoran minimal aktivasi)
Requirements Fitur
1. Rincian uang kertas & logam
- Teller dapat mencatat jumlah lembar/keping per pecahan pada setoran, penarikan, dan penutupan kas.
- Total uang fisik dihitung otomatis dari rincian pecahan.
- Rincian pecahan disimpan sebagai bukti audit pada transaksi dan Laporan Harian Teller.
2. Struk transaksi
- Struk memuat nama bank, nomor struk, nomor referensi jurnal, tanggal & waktu, jenis transaksi,
  nomor rekening, nama nasabah, NIS, nomor slip, nama Teller, nominal, terbilang,
  saldo sebelum, saldo sesudah, dan status transaksi.
3. Jurnal koreksi/pembalik
- Supervisor dapat mengoreksi transaksi berstatus sukses dengan alasan wajib.
- Sistem membuat jurnal pembalik, memulihkan saldo, dan menandai transaksi asal sebagai "reversed".
- Transaksi yang sudah dikoreksi tidak dapat dikoreksi ulang.
- Koreksi ditolak jika laporan harian tanggal transaksi telah disetujui dan kas telah dipindahkan ke brankas.
4. Pencarian nasabah
- Teller dapat mencari berdasarkan nomor rekening, NIS, nama lengkap, atau QR Code.
- Pencarian yang menghasilkan lebih dari satu nasabah menampilkan daftar hasil.
5. Registrasi nasabah
- Teller dapat mendaftarkan nasabah baru didampingi pada loket.
- Nomor siswa dan nomor rekening dibuat otomatis oleh sistem.
6. Neraca Saldo
- Administrator dan Supervisor dapat melihat Buku Besar dan Neraca Saldo (aset, kewajiban) dengan status keseimbangan.
- Bank Mini tidak mencatat pendapatan maupun beban karena tidak mencari keuntungan; dana murni untuk menabung.
7. Konfigurasi produk
- Administrator mengatur nama bank, saldo mengendap, dan setoran minimal aktivasi anggota.
Field Wajib
- transactions.denomination_breakdown
- transactions.reversal_of_transaction_id
- transactions.reversed_at, transactions.reversed_by, transactions.reversal_reason
- daily_reports.closing_breakdown
- system_configs (bank_name, minimum_balance, min_deposit_to_activate)
4. User Flow
4.1 Flow Login Pengguna
1. Pengguna membuka halaman login.
2. Pengguna memasukkan kredensial.
3. Sistem memeriksa akun, password/PIN, status, dan role.
4. Jika gagal, sistem menampilkan pesan kesalahan dan mencatat percobaan.
5. Jika berhasil, sistem membuat sesi.
6. Sistem mengarahkan pengguna ke dashboard sesuai role.
7. Jika pengguna mengakses URL yang tidak sesuai, sistem menolak akses dan mengarahkan kembali ke halaman yang sesuai.
4.2 Flow Setoran Tunai
 1. Teller login.
 2. Teller membuka modul transaksi setoran.
 3. Teller memindai QR Code nasabah atau memasukkan nomor rekening.
 4. Sistem menampilkan profil dan saldo nasabah.
 5. Teller memeriksa kelengkapan slip setoran.
 6. Teller memasukkan nominal slip.
 7. Teller menghitung dan memasukkan nominal uang fisik.
 8. Sistem membandingkan nominal slip dengan uang fisik.
 9. Jika berbeda, sistem menolak penyimpanan dan Teller meminta konfirmasi nasabah.
10. Jika sesuai, Teller mengonfirmasi transaksi.
11. Sistem memulai database transaction.
12. Sistem menyimpan transaksi setoran.
13. Sistem menambah saldo rekening.
14. Sistem membuat dua baris jurnal akuntansi.
15. Sistem memvalidasi keseimbangan jurnal.
16. Jika berhasil, sistem melakukan commit.
17. Sistem mengunci transaksi.
18. Sistem menampilkan dan mencetak struk setoran.
19. Teller menyerahkan buku tabungan dan struk kepada nasabah.
4.3 Flow Penarikan Tunai
 1. Teller login.
 2. Teller membuka modul transaksi penarikan.
 3. Teller memindai QR Code atau memasukkan nomor rekening.
 4. Teller memeriksa kelengkapan slip penarikan.
 5. Teller memasukkan nominal penarikan.
 6. Sistem memeriksa status rekening dan saldo.
 7. Sistem menghitung saldo setelah penarikan.
 8. Jika saldo tidak mencukupi atau kurang dari Rp10.000, sistem menolak transaksi.
 9. Jika saldo mencukupi, nasabah memasukkan PIN 6-digit.
10. Sistem memverifikasi PIN.
11. Jika PIN salah, sistem menolak transaksi.
12. Jika PIN benar, Teller mengonfirmasi transaksi.
13. Sistem mengurangi saldo rekening.
14. Sistem mencatat transaksi dan dua baris jurnal.
15. Sistem melakukan commit jika semua validasi berhasil.
16. Sistem mencetak struk penarikan.
17. Teller menyerahkan uang tunai, buku tabungan, dan struk kepada nasabah.
4.4 Flow Penutupan Kas
 1. Teller login dan membuka modul penutupan kas.
 2. Teller memilih tanggal kerja.
 3. Sistem mengambil seluruh transaksi Teller pada tanggal tersebut.
 4. Sistem menghitung total setoran dan total penarikan.
 5. Sistem menghitung saldo akhir berdasarkan rumus rekonsiliasi.
 6. Teller memasukkan jumlah uang fisik di laci.
 7. Sistem membandingkan saldo sistem dengan uang fisik.
 8. Jika terjadi selisih, sistem mengunci pencetakan laporan final.
 9. Teller melakukan pemeriksaan ulang.
10. Jika sesuai, sistem membuat Laporan Harian Teller.
11. Teller mencetak dan menyerahkan laporan kepada Supervisor.
12. Supervisor memeriksa transaksi, laporan, dan kas fisik.
13. Supervisor menyetujui laporan jika valid.
14. Sistem mengubah status laporan menjadi disetujui.
15. Sistem mencatat pemindahan tanggung jawab kas ke brankas utama.
16. Sistem mengarsipkan laporan dan mencatat approval.
4.5 Flow Nasabah Melihat Saldo dan Mutasi
1. Nasabah login.
2. Sistem memvalidasi identitas nasabah.
3. Sistem menampilkan saldo rekening milik nasabah.
4. Nasabah membuka riwayat mutasi.
5. Sistem menampilkan transaksi rekening tersebut.
6. Sistem menolak akses terhadap rekening atau profil nasabah lain.
7. Nasabah logout.
5. Architecture
Sequence Diagram — Proses Setoran Tunai
sequenceDiagram
    actor Teller
    actor Nasabah
    participant Client as Client Application
    participant Auth as Auth and RBAC
    participant API as Application Server
    participant DB as Database
    participant Printer as Receipt Printer

    Teller->>Client: Login
    Client->>Auth: Kirim kredensial
    Auth->>DB: Validasi akun dan role
    DB-->>Auth: Akun valid sebagai Teller
    Auth-->>Client: Buat session
    Client-->>Teller: Tampilkan dashboard Teller

    Teller->>Client: Scan QR Code atau masukkan nomor rekening
    Client->>API: Minta profil rekening
    API->>Auth: Validasi session dan role
    Auth-->>API: Akses diizinkan
    API->>DB: Ambil profil nasabah dan saldo
    DB-->>API: Data rekening
    API-->>Client: Tampilkan profil dan saldo

    Teller->>Client: Masukkan nominal slip dan uang fisik
    Client->>API: Kirim data setoran
    API->>Auth: Validasi session dan role
    Auth-->>API: Akses diizinkan
    API->>API: Validasi nominal dan kecocokan uang fisik

    alt Nominal tidak sesuai
        API-->>Client: Tolak transaksi dan tampilkan selisih
        Client-->>Teller: Minta konfirmasi nasabah
    else Nominal sesuai
        API->>DB: BEGIN TRANSACTION
        API->>DB: Simpan transaksi setoran
        API->>DB: Tambah saldo rekening
        API->>DB: Simpan jurnal debit dan kredit
        API->>DB: Validasi jurnal seimbang

        alt Proses gagal
            API->>DB: ROLLBACK
            API-->>Client: Tampilkan kegagalan transaksi
        else Proses berhasil
            API->>DB: COMMIT
            DB-->>API: Transaksi final
            API-->>Client: Tampilkan detail transaksi
            Client->>Printer: Cetak struk
            Printer-->>Teller: Struk setoran
            Teller-->>Nasabah: Serahkan buku tabungan dan struk
        end
    end
6. Database Schema
6.1 ERD
erDiagram
    USERS {
        BIGINT id PK
        VARCHAR full_name
        VARCHAR username UK
        VARCHAR password_hash
        ENUM role
        ENUM status
        DATETIME last_login_at
        DATETIME created_at
        DATETIME updated_at
    }

    CUSTOMERS {
        BIGINT id PK
        VARCHAR student_number UK
        VARCHAR full_name
        VARCHAR class_name
        VARCHAR phone_number
        VARCHAR qr_code_value UK
        ENUM status
        DATETIME created_at
        DATETIME updated_at
    }

    ACCOUNTS {
        BIGINT id PK
        BIGINT customer_id FK
        VARCHAR account_number UK
        VARCHAR academic_year
        BIGINT opening_balance
        BIGINT current_balance
        BIGINT minimum_balance
        ENUM status
        DATETIME opened_at
        DATETIME closed_at
        DATETIME created_at
        DATETIME updated_at
    }

    SYSTEM_SETTINGS {
        BIGINT id PK
        VARCHAR setting_key UK
        VARCHAR setting_value
        VARCHAR setting_type
        BIGINT updated_by FK
        DATETIME updated_at
    }

    TRANSACTIONS {
        BIGINT id PK
        VARCHAR transaction_number UK
        BIGINT account_id FK
        BIGINT teller_id FK
        ENUM transaction_type
        VARCHAR slip_number
        BIGINT slip_amount
        BIGINT physical_cash_amount
        BIGINT amount
        BIGINT balance_before
        BIGINT balance_after
        ENUM status
        DATETIME transaction_at
        DATETIME created_at
    }

    ACCOUNTING_JOURNALS {
        BIGINT id PK
        VARCHAR journal_number
        BIGINT transaction_id FK
        VARCHAR account_code
        ENUM entry_type
        BIGINT debit_amount
        BIGINT credit_amount
        VARCHAR description
        ENUM status
        DATETIME journal_at
        DATETIME created_at
    }

    DAILY_CASH_REPORTS {
        BIGINT id PK
        VARCHAR report_number UK
        DATE business_date
        BIGINT teller_id FK
        BIGINT opening_cash_balance
        BIGINT total_deposit
        BIGINT total_withdrawal
        BIGINT system_ending_cash
        BIGINT physical_cash
        BIGINT cash_difference
        ENUM status
        BIGINT supervisor_id FK
        DATETIME submitted_at
        DATETIME approved_at
        VARCHAR approval_notes
        DATETIME created_at
        DATETIME updated_at
    }

    CASH_TRANSFERS {
        BIGINT id PK
        BIGINT report_id FK
        BIGINT supervisor_id FK
        BIGINT amount
        VARCHAR source_location
        VARCHAR destination_location
        ENUM status
        DATETIME transferred_at
        VARCHAR notes
        DATETIME created_at
    }

    AUDIT_LOGS {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR action
        VARCHAR entity_name
        BIGINT entity_id
        VARCHAR ip_address
        TEXT metadata
        DATETIME created_at
    }

    USERS ||--o{ ACCOUNTS : updates_settings
    USERS ||--o{ SYSTEM_SETTINGS : updates
    CUSTOMERS ||--o| ACCOUNTS : owns
    ACCOUNTS ||--o{ TRANSACTIONS : has
    USERS ||--o{ TRANSACTIONS : processes
    TRANSACTIONS ||--|{ ACCOUNTING_JOURNALS : generates
    USERS ||--o{ DAILY_CASH_REPORTS : submits
    USERS ||--o{ DAILY_CASH_REPORTS : approves
    DAILY_CASH_REPORTS ||--o| CASH_TRANSFERS : authorizes
    USERS ||--o{ CASH_TRANSFERS : authorizes
    USERS ||--o{ AUDIT_LOGS : creates
6.2 Penjelasan Entitas
Entitas	Penjelasan
USERS	Menyimpan akun internal Administrator, Teller, dan Supervisor beserta role dan statusnya.
CUSTOMERS	Menyimpan profil nasabah atau siswa yang menggunakan rekening Bank Mini.
ACCOUNTS	Menyimpan nomor rekening, saldo awal, saldo berjalan, saldo minimum, dan status rekening nasabah.
SYSTEM_SETTINGS	Menyimpan konfigurasi global, seperti saldo mengendap minimum dan konfigurasi QR Code.
TRANSACTIONS	Menyimpan transaksi setoran dan penarikan yang diproses Teller.
ACCOUNTING_JOURNALS	Menyimpan jurnal double-entry yang dihasilkan dari transaksi operasional.
DAILY_CASH_REPORTS	Menyimpan laporan penutupan kas harian Teller dan hasil rekonsiliasinya.
CASH_TRANSFERS	Menyimpan otorisasi pemindahan kas dari laci Teller ke brankas utama.
AUDIT_LOGS	Menyimpan jejak aktivitas dan perubahan penting untuk kebutuhan audit.
ACCOUNT_CLOSURES	Menyimpan permintaan penutupan rekening karena kelulusan, pencairan saldo oleh Teller, dan keputusan Administrator.
6.3 Aturan Integritas Database
- Seluruh tabel operasional menggunakan engine transaksional.
- Seluruh nominal rupiah menggunakan BIGINT atau tipe integer presisi, bukan floating point.
- Kolom nominal wajib memiliki check constraint > 0 jika berlaku.
- current_balance, cash_difference, dan hasil perhitungan laporan tidak boleh menghasilkan nilai tidak valid.
- Foreign key pada data transaksi, jurnal, laporan, dan audit menggunakan ON DELETE RESTRICT.
- Data transaksi dan jurnal tidak boleh dihapus melalui fungsi operasional Teller.
- TRANSACTIONS.transaction_number, USERS.username, CUSTOMERS.student_number, CUSTOMERS.qr_code_value, dan ACCOUNTS.account_number harus unik.
- Setiap transaksi wajib memiliki tepat dua baris jurnal.
- Total debit dan kredit untuk satu transaksi harus sama.
- Perubahan saldo, transaksi, dan jurnal harus diproses dalam satu database transaction.
- Status transaksi final tidak dapat diubah oleh Teller.
7. Design & Technical Constraints
7.1 Batasan Teknis
- Sistem harus mendukung proses transaksi secara konsisten walaupun terdapat akses bersamaan.
- Operasi saldo harus menggunakan transaksi database dan mekanisme pencegahan race condition.
- Validasi penting tidak boleh hanya dilakukan di frontend.
- Validasi nominal, saldo minimum, role, PIN, dan status rekening wajib dilakukan di server.
- Waktu transaksi dan waktu approval harus menggunakan sumber waktu server.
- Sistem harus mencegah saldo rekening menjadi negatif atau lebih kecil dari saldo minimum. Pengecualian hanya pada penutupan rekening yang disetujui, di mana seluruh saldo termasuk saldo mengendap dicairkan.
- Penutupan rekening wajib disetujui Administrator sebelum rekening ditutup dan penabung menjadi alumni. Rekening dengan permintaan penutupan yang masih menunggu tidak dapat ditransaksikan.
- Sistem harus menjaga konsistensi antara saldo rekening, transaksi, dan jurnal.
- Sistem harus menyediakan rollback jika proses transaksi gagal.
- Sistem harus tetap dapat menampilkan riwayat transaksi dalam jumlah besar melalui pagination.
- Pencarian nomor rekening dan QR Code harus memberikan hasil dengan cepat pada kondisi operasional loket.
- Sistem harus mampu mencatat audit log tanpa menyimpan password atau PIN asli.
- Penghapusan data master tidak boleh menghapus histori transaksi dan jurnal.
- Pengecualian: perintah `bankmini:reset-nasabah` boleh menghapus permanen seluruh histori untuk membersihkan data testing sebelum peluncuran. Akun pengguna (`users`) dan konfigurasi sistem (`system_configs`) tetap utuh; data yang direset mencakup member, penabung, rekening, transaksi, jurnal, laporan kas, permintaan penutupan, OTP lupa PIN, token aplikasi mobile, audit log, sesi login, cache, dan antrean. Perintah ini menulis satu entri audit log setiap kali dijalankan, menolak dijalankan di `APP_ENV=production` tanpa `--allow-production` dengan `--operator`, dan di production wajib mengetik "HAPUS" sebagai konfirmasi (tidak bisa dilewati `--force`).
- Penggunaan browser cache harus dimatikan pada seluruh respons (Cache-Control: no-store) untuk mencegah akses back-button setelah logout.
- Library pihak ketiga untuk fitur front-end (seperti QR Scanner) wajib di-bundle secara lokal untuk mematuhi kebijakan CSP.
7.2 Panduan Desain Antarmuka
- Antarmuka harus sederhana dan berorientasi pada proses loket.
- Halaman Teller harus memprioritaskan pencarian nasabah, nominal transaksi, validasi, dan pencetakan struk.
- Nominal uang harus ditampilkan dalam format Rupiah.
- Field nominal hanya menerima angka positif.
- Sistem harus memberikan pesan kesalahan yang jelas untuk slip tidak lengkap, nominal berbeda, saldo tidak cukup, PIN salah, atau akses ditolak.
- Tombol simpan transaksi harus mencegah pengiriman ganda.
- Status transaksi harus terlihat jelas, seperti Draft, Success, Rejected, atau Locked.
- Halaman Supervisor harus menampilkan perbandingan saldo sistem, uang fisik, dan selisih kas.
- Halaman Nasabah hanya boleh menampilkan data milik nasabah yang sedang login.
- Aksi berisiko seperti approval dan pemindahan kas harus meminta konfirmasi.
- Informasi sensitif harus disamarkan dan tidak ditampilkan dalam log frontend.
7.3 Batasan Alur Bisnis
- Administrator tidak boleh memproses setoran dan penarikan.
- Teller tidak boleh mengedit atau menghapus transaksi final.
- Teller tidak boleh menyetujui laporan hariannya sendiri.
- Supervisor tidak boleh melakukan transaksi loket.
- Nasabah tidak boleh mengakses modul transaksi, jurnal, akun internal, atau data nasabah lain.
- Laporan final tidak dapat dicetak jika terdapat selisih kas.
- Pemindahan kas hanya dapat dilakukan setelah approval Supervisor.
- Transaksi penarikan wajib menggunakan PIN 6-digit nasabah.
- Setiap transaksi harus menghasilkan jurnal akuntansi yang seimbang.
7.4 Asumsi
- Satu nasabah memiliki paling banyak satu rekening aktif.
- Satu transaksi setoran atau penarikan diproses oleh satu Teller.
- Konfigurasi saldo minimum disimpan secara global dan default-nya Rp10.000.
- QR Code berisi nilai yang dapat digunakan untuk menemukan profil nasabah atau rekening terkait.
- Nasabah mengakses dasbor melalui mekanisme autentikasi yang disediakan aplikasi.
- Detail perangkat cetak, kamera, dan format fisik slip dapat disesuaikan dengan fasilitas sekolah.