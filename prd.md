PRD — Sistem Informasi E-Teller Bank Mini Sekolah
1. Overview
Tujuan Aplikasi
Membangun aplikasi Sistem Informasi E-Teller Bank Mini Sekolah berbasis client-server untuk mengelola rekening nasabah, transaksi setoran dan penarikan tunai, rekonsiliasi kas, persetujuan laporan oleh Administrator, serta pencatatan jurnal akuntansi secara aman dan terintegrasi.
Masalah yang Diselesaikan
- Pencatatan transaksi loket yang masih manual dan rawan kesalahan.
- Ketidaksesuaian antara uang fisik dan saldo digital.
- Risiko penarikan melebihi saldo atau melanggar saldo mengendap minimum.
- Tidak adanya pemisahan tanggung jawab antara Administrator, Teller, dan Nasabah.
- Tidak tersedianya audit trail dan jurnal akuntansi yang konsisten.
- Kesulitan pencarian profil nasabah secara cepat di loket.
Target Pengguna
1. Administrator
- Mengelola akun internal, rekening nasabah, konfigurasi sistem, dan audit jurnal.
- Memeriksa laporan harian, menyetujui atau menolaknya, dan mengotorisasi koreksi transaksi; tidak boleh menyetujui laporan miliknya sendiri.
- Tidak dapat melakukan transaksi setoran maupun penarikan.
2. Piket Teller
- Melayani pencarian nasabah, setoran tunai, penarikan tunai, dan pembuatan laporan harian.
- Tidak dapat mengubah atau menghapus transaksi yang telah tersimpan.
3. Supervisor / Kepala Bank Mini
- Peran ini digabungkan ke Administrator (migration merge_supervisor_role_into_admin).
4. Nasabah / Siswa
- Melihat saldo dan riwayat mutasi rekening sendiri lewat web atau aplikasi mobile.
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
- Sistem memiliki tiga role aktif:
- Administrator
- Teller
- Nasabah
- Catatan: peran Supervisor digabungkan ke Administrator; kewenangan approval laporan dan koreksi transaksi kini milik Administrator.
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
- Administrator tidak dapat menyetujui laporan hariannya sendiri.
- Jejak aktivitas penting wajib dicatat dalam audit log.
- Sistem menerapkan Security Headers (CSP, no-store cache) untuk mencegah clickjacking dan browser caching halaman terautentikasi.
- Sistem membatasi penggunaan akses kamera hanya pada origin sendiri (Permissions-Policy: camera=(self)).
2.4 Persyaratan Validasi Data
- Nominal transaksi harus lebih besar dari Rp0.
- Penarikan hanya dapat dilakukan jika:
- Saldo mencukupi.
- Saldo setelah penarikan tidak kurang dari Rp10.000.
- PIN nasabah valid.
- Nomor slip transaksi bersifat unik per rekening untuk mencegah pengiriman ganda.
- Rekening harus ditemukan dan berstatus aktif sebelum transaksi diproses.
- Laporan harian tidak disetujui bila arus kas tanggal tersebut berubah setelah laporan dibuat; Administrator menolak laporan dan mewajibkan Teller menghitung ulang.
- Foreign key wajib menjaga histori transaksi dan jurnal.
- Penghapusan data master yang masih direferensikan transaksi harus ditolak.
2.5 Persyaratan Akuntansi
- Setiap transaksi berhasil harus menghasilkan dua baris jurnal akuntansi.
- Jurnal wajib seimbang antara debit dan kredit.
- Setoran dan penarikan harus memperbarui saldo rekening dan jurnal dalam satu transaksi database.
- Jika salah satu proses gagal, seluruh perubahan harus dibatalkan melalui rollback.
- Kode akun yang digunakan:
- Kas Teller: 101
- Kas Brankas: 102
- Tabungan: 201
3. Core Features
FASE 1 — Autentikasi, RBAC, dan Keamanan Akses
Status
Selesai
Sub-Fitur
- Login terpusat pengguna internal dan nasabah
- Manajemen sesi atau token pengguna
- Pembatasan akses berdasarkan role
- Keamanan kredensial dan PIN nasabah
- Pemulihan kredensial mandiri (Lupa Password)
- Pencatatan aktivitas akses
Requirements Fitur
1. Login terpusat
- Pengguna memasukkan username atau nomor siswa dan password untuk masuk aplikasi; PIN transaksi tidak berperan di halaman login.
- Sistem memeriksa status akun.
- Sistem mengarahkan pengguna ke halaman sesuai role.
2. Manajemen sesi atau token pengguna
- Sistem membuat sesi setelah login berhasil.
- Sesi memiliki waktu kedaluwarsa.
- Logout harus menghapus atau membatalkan sesi aktif.
3. Pembatasan akses berdasarkan role
- Administrator dapat mengakses manajemen sistem.
- Teller dapat mengakses transaksi loket.
- Administrator dapat mengakses manajemen sistem, validasi laporan, dan approval.
- Nasabah hanya dapat mengakses data rekening sendiri.
4. Keamanan kredensial dan PIN nasabah
- Password dan PIN disimpan dalam bentuk hash.
- Percobaan autentikasi gagal dapat dicatat.
- PIN harus diverifikasi sebelum penarikan diproses.
5. Pencatatan aktivitas akses
- Sistem mencatat login, logout, penolakan akses, dan aktivitas penting.
6. Pemulihan kredensial mandiri (Lupa Password)
- Pengguna internal (Staff) dapat memulihkan password dengan verifikasi username dan nama lengkap terdaftar, dengan password baru minimal 8 karakter.
- Nasabah (Siswa) dapat membuat atau memulihkan password aplikasi dengan verifikasi nomor siswa dan kode verifikasi email terdaftar, dengan password baru minimal 8 karakter.
- PIN transaksi tidak dipulihkan lewat alur mandiri ini; pengaturan ulang PIN tetap melalui Administrator.
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
Selesai
Sub-Fitur
- Pengelolaan akun Administrator dan Teller
- Pengelolaan profil nasabah atau siswa
- Pembuatan nomor rekening otomatis
- Pengaturan saldo mengendap minimum
- Konfigurasi perangkat pemindai QR Code
Requirements Fitur
1. Pengelolaan akun internal
- Administrator dapat menambah, melihat, memperbarui, memblokir, dan menghapus akun.
- Role yang tersedia adalah Administrator dan Teller.
- Akun yang diblokir tidak dapat login.
2. Pengelolaan profil nasabah
- Administrator dapat membuat dan mengelola data nasabah atau siswa.
- Daftar Anggota hanya menampilkan siswa yang belum memiliki rekening penabung dan belum lulus. Siswa yang sudah menjadi penabung dikelola di daftar Penabung, dan yang sudah lulus di halaman Kelulusan.
- Status rekening anggota diatur sistem: `account_inactive` saat didaftarkan, lalu `account_active` setelah aktivasi di Teller. Administrator tidak mengubahnya lewat form Anggota.
- Status penabung: `active` (dapat bertransaksi), `blocked` (dihentikan sementara), dan `graduated` (alumni, rekening sudah ditutup).
- Penabung tidak dihapus dari daftar. Untuk menghentikan aktivitasnya sementara, Administrator memblokir penabung: rekeningnya tidak dapat ditransaksikan dan penabung tidak dapat login.
- Profil nasabah dapat dihubungkan dengan satu rekening aktif.
- Data penabung, rekening, dan riwayat transaksi disimpan permanen, termasuk setelah siswa lulus. Tidak ada penghapusan baris; yang berubah hanya status dan kolom `graduated_at`.
- Saat siswa lulus, Teller mencairkan saldo di atas saldo mengendap di loket lewat penutupan rekening (saldo mengendap tidak bisa dicairkan dan tetap tersimpan di rekening), lalu Administrator memutuskan dari halaman Kelulusan. Persetujuan menutup rekening (`closed`) dan menandai penabung sebagai alumni (`graduated`).
- Penolakan penutupan mengembalikan saldo dan mengaktifkan kembali rekening. Alumni tidak dapat login, baik di web maupun aplikasi nasabah.
- Administrator dapat mengarsipkan satu angkatan sekaligus dari halaman Kelulusan. Anggota tanpa rekening dan penabung bersaldo nol langsung diarsipkan; penabung yang masih bersaldo menunggu penyelesaian di loket.
- Siswa yang tidak lulus ditandai dari halaman Kelulusan. Pencairan yang sudah dilakukan dibatalkan, rekening tetap aktif, dan angkatan siswa digeser ke tahun berikutnya agar tidak ikut diproses pada angkatan ini.
- Perintah `php artisan bankmini:graduate --year=Tahun [--dry-run]` menyiapkan arsip angkatan yang sama dari CLI.
3. Pembuatan nomor rekening otomatis
- Nomor rekening dibuat berdasarkan tahun ajaran dan nomor urut siswa.
- Nomor rekening harus unik.
4. Pengaturan saldo mengendap minimum
- Nilai default saldo mengendap minimum adalah Rp10.000.
- Administrator dapat mengatur nilai tersebut melalui konfigurasi sistem.
5. Penggunaan pemindai QR Code
- Scanner QR di browser Teller di-bundle secara lokal (mematuhi kebijakan CSP).
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
- saver_id
- academic_year
- opening_balance
- balance
- minimum_balance
- status
Konfigurasi:
- minimum_balance
- bank_name
- min_deposit_to_activate
- class_majors (daftar jurusan untuk saran isian kelas)
FASE 3 — Operasional Transaksi Teller
Status
Selesai
Sub-Fitur
- Autentikasi profil nasabah melalui QR Code atau nomor rekening
- Transaksi setoran tunai
- Transaksi penarikan tunai
- Validasi saldo dan nominal transaksi
- Pencetakan struk transaksi
Requirements Fitur
1. Autentikasi profil nasabah
- Teller memindai QR Code atau memasukkan nomor rekening.
- Sistem menampilkan profil nasabah dan saldo berjalan.
- Sistem tidak menampilkan data nasabah lain di luar hasil pencarian yang sah.
2. Transaksi setoran tunai
- Teller memeriksa kelengkapan slip setoran.
- Teller memasukkan nominal pada slip; nomor slip opsional.
- Sistem menolak transaksi jika nominal tidak valid atau nomor slip yang diisi sudah terpakai.
- Sistem menambah saldo setelah transaksi berhasil.
- Transaksi yang tersimpan dikunci dari perubahan.
3. Transaksi penarikan tunai
- Teller memasukkan nominal penarikan.
- Sistem memeriksa saldo rekening.
- Sistem memeriksa saldo mengendap minimum.
- Nasabah memasukkan PIN 6-digit.
- Sistem mengurangi saldo setelah seluruh validasi berhasil.
4. Validasi saldo dan nominal
- Semua validasi dilakukan di sisi server.
- Nominal tidak boleh bernilai nol atau negatif.
- Rekening harus aktif.
- Penarikan tidak boleh menghasilkan saldo di bawah saldo mengendap.
5. Pencetakan struk
- Struk berisi nomor transaksi, tanggal, jenis transaksi, rekening, nominal, dan saldo akhir.
- Struk dapat dicetak setelah transaksi berhasil.
Field Wajib
- receipt_number
- account_id
- teller_id
- type
- slip_number
- amount
- balance_before
- balance_after
- status
- transaction_at
FASE 4 — Penutupan Kas dan Approval Administrator
Status
Selesai
Sub-Fitur
- Rekap kas harian Teller
- Pembuatan laporan harian Teller
- Validasi dan persetujuan Administrator
- Pemindahan tanggung jawab kas ke brankas utama
- Penolakan laporan dan audit transaksi
Requirements Fitur
1. Rekap transaksi harian
- Sistem mengelompokkan transaksi berdasarkan Teller dan tanggal kerja, termasuk dampak jurnal pembalik.
- Sistem menghitung total setoran dan total penarikan.
2. Rekap saldo laci
- Rumus saldo akhir:
Saldo Awal (kas yang belum tersapu) + Total Setoran - Total Penarikan
- Saldo akhir sistem menjadi isi laporan; loket tidak lagi menghitung uang fisik.
3. Pembuatan laporan
- Teller dapat membuat dan mencetak Laporan Harian Teller.
- Laporan berstatus menunggu validasi Administrator.
4. Approval Administrator
- Administrator memeriksa transaksi, total kas, dan laporan.
- Bila arus kas tanggal tersebut berubah setelah laporan dibuat, sistem menolak approval dan mewajibkan Teller menghitung ulang.
- Laporan berstatus menunggu validasi dapat disetujui atau ditolak dengan catatan wajib.
- Pembuat laporan tidak dapat menyetujui laporan miliknya sendiri.
5. Pemindahan kas ke brankas
- Approval memindahkan seluruh kas laci Teller ke brankas utama.
- Sistem membukukan Debet 102 Kas Brankas dan Kredit 101 Kas Teller, dan mencatat waktu, pengguna, dan nominal pemindahan.
6. Penolakan laporan
- Laporan yang ditolak dapat dibuat ulang oleh Teller dengan nomor laporan yang sama.
- Administrator dapat melihat detail transaksi dan audit log untuk investigasi.
Field Wajib
- report_number
- report_date
- teller_id
- opening_balance
- total_deposit
- total_withdrawal
- closing_balance_system
- report_status
- approved_by
- submitted_at
- approved_at
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
- Administrator dapat melihat jurnal.
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
FASE 6 — Transaksi Lanjutan dan Layanan Nasabah
Status
Selesai
Sub-Fitur
- Transfer antar rekening nasabah (Overbooking/P2P)
- Cetak buku tabungan fisik (Passbook printing)
- Ekspor rekening koran digital (e-Statement PDF)
- Kartu nasabah virtual & generator QR Code
Requirements Fitur
1. Transfer antar rekening
- Teller memasukkan rekening tujuan dan nominal di loket.
- Jurnal double-entry: Debet 201 rekening pengirim, Kredit 201 rekening penerima.
- Validasi saldo mengendap tetap berlaku pada rekening pengirim.
2. Buku tabungan fisik
- Cetak berdasarkan indeks baris buku dengan opsi baris awal per halaman.
3. Rekening koran
- PDF resmi dengan watermark dan halaman verifikasi.
4. Kartu nasabah virtual
- Tampilan kartu ber-QR Code di dasbor nasabah dan profil Administrator.
FASE 7 — Modul Laporan Keuangan dan Akuntansi Lengkap
Status
Selesai
Sub-Fitur
- Buku Besar (General Ledger)
- Neraca Saldo (Trial Balance) dan indikator likuiditas kas
- Jurnal pemindahan kas brankas (Akun 102 Kas Brankas)
Requirements Fitur
1. Buku Besar
- Filter per kode akun (101, 102, 201) dan rentang tanggal.
2. Neraca Saldo
- Perbandingan debit-kredit per akun dan ekspor CSV laporan akuntansi.
3. Jurnal pemindahan kas
- Terbit otomatis saat approval laporan harian (Debet 102 Kas Brankas, Kredit 101 Kas Teller).
FASE 8 — Keamanan Transaksi dan Pengalaman Pengguna
Status
Selesai
Sub-Fitur
- Perlindungan brute-force PIN nasabah (Failed PIN attempts lock)
- Anti-collision sequence generator transaksi dan laporan
- Dashboard analitik
Requirements Fitur
1. Proteksi PIN
- Akun penabung terkunci setelah beberapa kali salah PIN berulang dan setiap percobaan dicatat audit log.
2. Sequence anti-collision
- Nomor transaksi (Teller) dan nomor laporan dibuat lewat tabel document_sequences.
3. Dashboard analitik
- Kartu metrik dan grafik keuangan pada dashboard Administrator.
FASE 9 — Penyempurnaan Berbasis Riset Pengguna (Revisi Besar)
Status
Selesai
Latar Belakang
Hasil kuesioner pengguna (Teller, Nasabah, Administrator) menunjukkan kebutuhan
yang belum tercakup: kelengkapan struk, jurnal koreksi/pembalik, pencarian nasabah
yang lebih cepat, Neraca Saldo, konfigurasi produk Bank Mini, dan registrasi nasabah
yang didampingi Teller.
Sub-Fitur
- Struk transaksi lengkap (nomor referensi jurnal, terbilang, status)
- Jurnal koreksi/pembalik oleh Administrator
- Pencarian nasabah berdasarkan nama/NIS/nomor rekening
- Registrasi nasabah didampingi Teller
- Neraca Saldo dan Buku Besar
- Konfigurasi produk (nama bank, saldo mengendap, setoran minimal aktivasi)
Requirements Fitur
1. Struk transaksi
- Struk memuat nama bank, nomor struk, nomor referensi jurnal, tanggal & waktu, jenis transaksi,
  nomor rekening, nama nasabah, NIS, nomor slip, nama Teller, nominal, terbilang,
  saldo sebelum, saldo sesudah, dan status transaksi.
2. Jurnal koreksi/pembalik
- Administrator dapat mengoreksi transaksi berstatus sukses dengan alasan wajib.
- Sistem membuat jurnal pembalik, memulihkan saldo, dan menandai transaksi asal sebagai "reversed".
- Transaksi yang sudah dikoreksi tidak dapat dikoreksi ulang.
- Koreksi ditolak jika laporan harian tanggal transaksi telah disetujui dan kas telah dipindahkan ke brankas.
3. Pencarian nasabah
- Teller dapat mencari berdasarkan nomor rekening, NIS, nama lengkap, atau QR Code.
- Pencarian yang menghasilkan lebih dari satu nasabah menampilkan daftar hasil.
4. Registrasi nasabah
- Teller dapat mendaftarkan nasabah baru didampingi pada loket.
- Nomor siswa dan nomor rekening dibuat otomatis oleh sistem.
- Pada setoran aktivasi, penabung membuat sendiri password aplikasi dan PIN transaksi. Password dipakai untuk masuk aplikasi dan bisa dipulihkan lewat kode verifikasi email; PIN hanya dipakai saat transaksi di loket.
5. Neraca Saldo
- Administrator dapat melihat Buku Besar dan Neraca Saldo (aset, kewajiban) dengan status keseimbangan.
- Bank Mini tidak mencatat pendapatan maupun beban karena tidak mencari keuntungan; dana murni untuk menabung.
6. Konfigurasi produk
- Administrator mengatur nama bank, saldo mengendap, dan setoran minimal aktivasi anggota.
Field Wajib
- transactions.reversal_of_transaction_id
- transactions.reversed_at, transactions.reversed_by, transactions.reversal_reason
- system_configs (bank_name, minimum_balance, min_deposit_to_activate)
Catatan
- Rincian uang kertas & logam untuk penutupan kas yang ditelusuri dari hasil riset sudah tidak dipakai: perbandingan uang fisik, selisih kas, dan rincian pecahan dihapus (kolom denomination_breakdown, closing_breakdown, cash_difference, dan physical_cash_amount sudah dibuang dari database), dan konfigurasi jam operasional serta batas penarikan tanpa pesanan tidak lagi dipakai.
4. User Flow
4.1 Flow Login Pengguna
1. Pengguna membuka halaman login.
2. Pengguna memasukkan kredensial.
3. Sistem memeriksa akun, password, status, dan role.
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
 6. Teller memasukkan nominal setoran sesuai uang fisik yang diterima, dengan nomor slip opsional.
 7. Sistem memvalidasi nominal positif dan rekening aktif; nomor slip bila diisi harus unik.
 8. Jika validasi gagal, sistem menolak penyimpanan dan menampilkan penyebabnya.
 9. Jika sesuai, Teller mengonfirmasi transaksi.
10. Sistem memulai database transaction.
11. Sistem menyimpan transaksi setoran.
12. Sistem menambah saldo rekening.
13. Sistem membuat dua baris jurnal akuntansi.
14. Sistem memvalidasi keseimbangan jurnal.
15. Jika berhasil, sistem melakukan commit.
16. Sistem mengunci transaksi.
17. Sistem menampilkan dan mencetak struk setoran.
18. Teller menyerahkan buku tabungan dan struk kepada nasabah.
4.3 Flow Penarikan Tunai
 1. Teller login.
 2. Teller membuka modul transaksi penarikan.
 3. Teller memindai QR Code atau memasukkan nomor rekening.
 4. Teller memeriksa kelengkapan slip penarikan.
 5. Teller memasukkan nominal penarikan.
 6. Sistem memeriksa status rekening dan saldo.
 7. Sistem menghitung saldo setelah penarikan.
 8. Jika saldo tidak mencukupi atau jatuh di bawah saldo mengendap, sistem menolak transaksi.
 9. Jika saldo mencukupi, nasabah memasukkan PIN 6-digit.
10. Sistem memverifikasi PIN.
11. Jika PIN salah, sistem menolak transaksi.
12. Jika PIN benar, Teller mengonfirmasi transaksi.
13. Sistem mengurangi saldo rekening.
14. Sistem mencatat transaksi dan dua baris jurnal.
15. Sistem melakukan commit jika semua validasi berhasil.
16. Sistem mencetak struk penarikan.
17. Teller menyerahkan uang tunai, buku tabungan, dan struk kepada nasabah.
4.4 Flow Penutupan Kas dan Approval
 1. Teller login dan membuka modul penutupan kas.
 2. Teller memilih tanggal kerja.
 3. Sistem mengambil seluruh transaksi Teller pada tanggal tersebut, termasuk dampak jurnal pembalik (koreksi).
 4. Sistem menghitung total setoran dan total penarikan.
 5. Sistem menghitung saldo awal laci dari mutasi kas yang belum tersapu, lalu saldo akhir sistem.
 6. Teller membuat Laporan Harian Teller berstatus menunggu validasi.
 7. Teller mencetak dan menyerahkan laporan kepada Administrator.
 8. Administrator memeriksa transaksi dan laporan.
 9. Bila arus kas tanggal tersebut berubah setelah laporan dibuat, Administrator menolak laporan dan Teller menghitung ulang.
10. Jika valid, Administrator menyetujui laporan.
11. Sistem mengubah status laporan menjadi disetujui.
12. Sistem menyapu seluruh kas laci ke Brankas Utama dan membukukan Debet 102 Kas Brankas, Kredit 101 Kas Teller.
13. Sistem mengarsipkan laporan dan mencatat approval.
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

    Teller->>Client: Masukkan nominal setoran (nomor slip opsional)
    Client->>API: Kirim data setoran
    API->>Auth: Validasi session dan role
    Auth-->>API: Akses diizinkan
    API->>API: Validasi nominal, rekening aktif, dan nomor slip unik

    alt Validasi gagal
        API-->>Client: Tolak transaksi dan tampilkan penyebabnya
        Client-->>Teller: Tampilkan pesan kesalahan
    else Validasi berhasil
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
        VARCHAR email UK
        VARCHAR password
        ENUM role
        ENUM status
        DATETIME last_login_at
        DATETIME created_at
        DATETIME updated_at
    }

    MEMBERS {
        VARCHAR nis PK
        VARCHAR full_name
        VARCHAR class_name
        VARCHAR graduation_year
        ENUM status
        DATETIME graduated_at
        BIGINT created_by FK
        DATETIME created_at
        DATETIME updated_at
    }

    SAVERS {
        BIGINT id PK
        VARCHAR student_number UK
        BIGINT member_id FK
        VARCHAR full_name
        VARCHAR class_name
        VARCHAR phone_number
        VARCHAR email
        VARCHAR pin_hash
        VARCHAR password_hash
        VARCHAR qr_code_value UK
        ENUM status
        DATETIME graduated_at
        INT failed_pin_attempts
        DATETIME locked_until
        DATETIME deleted_at
        DATETIME created_at
    }

    ACCOUNTS {
        BIGINT id PK
        BIGINT customer_id FK
        VARCHAR account_number UK
        VARCHAR academic_year
        BIGINT opening_balance
        BIGINT balance
        BIGINT minimum_balance
        ENUM status
        TIMESTAMP opened_at
        DATETIME created_at
        DATETIME updated_at
    }

    SYSTEM_CONFIGS {
        BIGINT id PK
        VARCHAR key UK
        VARCHAR value
        VARCHAR updated_by FK
        DATETIME created_at
        DATETIME updated_at
    }

    TRANSACTIONS {
        BIGINT id PK
        VARCHAR receipt_number UK
        VARCHAR slip_number UK
        BIGINT account_id FK
        BIGINT teller_id FK
        ENUM type
        BIGINT amount
        BIGINT balance_before
        BIGINT balance_after
        ENUM status
        BIGINT reversal_of_transaction_id FK
        DATETIME reversed_at
        BIGINT reversed_by FK
        VARCHAR reversal_reason
        DATETIME transaction_at
        DATETIME created_at
    }

    JURNAL_AKUNTANSI {
        BIGINT id PK
        VARCHAR journal_number UK
        BIGINT transaction_id FK
        BIGINT cash_transfer_id FK
        VARCHAR account_code
        ENUM entry_type
        BIGINT debit_amount
        BIGINT credit_amount
        VARCHAR description
        ENUM status
        DATETIME journal_at
        DATETIME created_at
    }

    DAILY_REPORTS {
        BIGINT id PK
        VARCHAR report_number UK
        DATE report_date
        BIGINT teller_id FK
        BIGINT opening_balance
        BIGINT total_deposit
        BIGINT total_withdrawal
        BIGINT closing_balance_system
        ENUM status
        BIGINT approved_by FK
        DATETIME submitted_at
        DATETIME approved_at
        TEXT approval_notes
        DATETIME created_at
    }

    CASH_TRANSFERS {
        BIGINT id PK
        BIGINT report_id FK
        BIGINT approved_by FK
        BIGINT amount
        VARCHAR source_location
        VARCHAR destination_location
        ENUM status
        DATETIME transferred_at
        VARCHAR notes
        DATETIME created_at
    }

    ACCOUNT_CLOSURES {
        BIGINT id PK
        BIGINT account_id FK
        BIGINT saver_id FK
        BIGINT requested_by FK
        BIGINT payout_transaction_id FK
        BIGINT payout_amount
        VARCHAR status
        BIGINT decided_by FK
        DATETIME decided_at
        TEXT notes
        TEXT decision_notes
        DATETIME created_at
        DATETIME updated_at
    }

    DOCUMENT_SEQUENCES {
        BIGINT id PK
        VARCHAR key UK
        BIGINT last_number
        DATETIME created_at
        DATETIME updated_at
    }

    MOBILE_TOKENS {
        BIGINT id PK
        BIGINT customer_id FK
        VARCHAR name
        VARCHAR token_hash UK
        TIMESTAMP last_used_at
        TIMESTAMP expires_at
        TIMESTAMP created_at
    }

    PASSWORD_RESET_OTPS {
        BIGINT id PK
        ENUM account_type
        BIGINT account_id
        VARCHAR otp_hash
        TINYINT attempts
        TIMESTAMP expires_at
        TIMESTAMP consumed_at
        DATETIME created_at
        DATETIME updated_at
    }

    AUDIT_LOGS {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR event
        VARCHAR audit_type
        BIGINT auditable_id
        VARCHAR auditable_type
        TEXT metadata
        VARCHAR ip_address
        DATETIME created_at
    }

    USERS ||--o{ DAILY_REPORTS : submits
    USERS ||--o{ DAILY_REPORTS : approves
    MEMBERS ||--o{ SAVERS : becomes
    SAVERS ||--o| ACCOUNTS : owns
    ACCOUNTS ||--o{ TRANSACTIONS : has
    USERS ||--o{ TRANSACTIONS : processes
    TRANSACTIONS ||--o| TRANSACTIONS : reverses
    TRANSACTIONS ||--o| JURNAL_AKUNTANSI : generates
    ACCOUNTS ||--o{ ACCOUNT_CLOSURES : requests
    SAVERS ||--o{ ACCOUNT_CLOSURES : requests
    ACCOUNT_CLOSURES ||--o| TRANSACTIONS : payout
    DAILY_REPORTS ||--o| CASH_TRANSFERS : authorizes
    CASH_TRANSFERS ||--|{ JURNAL_AKUNTANSI : generates
    SAVERS ||--o{ MOBILE_TOKENS : owns
    SAVERS ||--o{ PASSWORD_RESET_OTPS : requests
    USERS ||--o{ PASSWORD_RESET_OTPS : requests
    USERS ||--o{ AUDIT_LOGS : creates
6.2 Penjelasan Entitas
Entitas	Penjelasan
USERS	Menyimpan akun internal Administrator dan Teller beserta role, email, dan statusnya.
MEMBERS	Menyimpan daftar siswa (anggota) yang belum memiliki rekening penabung, termasuk kelas dan tahun kelulusannya.
SAVERS	Menyimpan profil penabung (bekas customers): NIS, kelas, kontak, password aplikasi, PIN transaksi (hashed), QR Code, status, penanda kelulusan, data brute-force PIN, dan soft delete.
ACCOUNTS	Menyimpan nomor rekening, tahun ajaran, saldo awal, saldo berjalan, saldo mengendap minimum, dan status rekening penabung.
SYSTEM_CONFIGS	Menyimpan konfigurasi global: bank_name, minimum_balance (saldo mengendap), dan min_deposit_to_activate.
DOCUMENT_SEQUENCES	Menyimpan konter nomor dokumen anti-collision untuk nomor transaksi dan nomor laporan.
TRANSACTIONS	Menyimpan transaksi setoran, penarikan, transfer, dan jurnal pembalik (reversal) yang diproses di loket, lengkap dengan saldo sebelum/sesudah dan status.
JURNAL_AKUNTANSI	Menyimpan baris double-entry dari transaksi operasional maupun pemindahan kas brankas.
DAILY_REPORTS	Menyimpan laporan penutupan kas harian Teller (status draft, submitted, approved, rejected) beserta persetujuan Administrator.
CASH_TRANSFERS	Menyimpan pemindahan kas dari laci Teller ke brankas utama yang terbit saat laporan disetujui.
ACCOUNT_CLOSURES	Menyimpan permintaan penutupan rekening karena kelulusan, pencairan saldo oleh Teller, dan keputusan Administrator (approve/reject).
MOBILE_TOKENS	Menyimpan token perangkat aplikasi nasabah (disimpan sebagai hash) beserta kedaluwarsa.
PASSWORD_RESET_OTPS	Menyimpan OTP pemulihan password untuk akun staff dan nasabah, terkunci per akun.
AUDIT_LOGS	Menyimpan jejak aktivitas dan perubahan penting untuk kebutuhan audit.
6.3 Aturan Integritas Database
- Seluruh tabel operasional menggunakan engine transaksional.
- Seluruh nominal rupiah menggunakan BIGINT atau tipe integer presisi, bukan floating point; nominal yang bisa minus (selisih laporan, admin fee historis) menggunakan BIGINT bertanda.
- Kolom nominal wajib memiliki check constraint > 0 jika berlaku.
- Saldo rekening dan hasil perhitungan laporan tidak boleh menghasilkan nilai tidak valid.
- Foreign key pada data transaksi, jurnal, laporan, dan audit menggunakan ON DELETE RESTRICT.
- Data transaksi dan jurnal tidak boleh dihapus melalui fungsi operasional Teller.
- TRANSACTIONS.receipt_number, TRANSACTIONS.slip_number, USERS.username, USERS.email, SAVERS.student_number, SAVERS.qr_code_value, SAVERS.email, ACCOUNTS.account_number, dan MOBILE_TOKENS.token_hash harus unik.
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
- Sistem harus mencegah saldo rekening menjadi negatif atau lebih kecil dari saldo minimum, juga pada penutupan rekening: pencairan penutupan hanya boleh menghabiskan saldo di atas saldo mengendap, dan sisanya tetap tersimpan di rekening yang ditutup.
- Penutupan rekening wajib disetujui Administrator sebelum rekening ditutup dan penabung menjadi alumni. Rekening dengan permintaan penutupan yang masih menunggu tidak dapat ditransaksikan.
- Sistem harus menjaga konsistensi antara saldo rekening, transaksi, dan jurnal.
- Sistem harus menyediakan rollback jika proses transaksi gagal.
- Sistem harus tetap dapat menampilkan riwayat transaksi dalam jumlah besar melalui pagination.
- Pencarian nomor rekening dan QR Code harus memberikan hasil dengan cepat pada kondisi operasional loket.
- Sistem harus mampu mencatat audit log tanpa menyimpan password atau PIN asli.
- Penghapusan data master tidak boleh menghapus histori transaksi dan jurnal.
- Pengecualian: perintah `bankmini:reset-nasabah` boleh menghapus permanen seluruh histori untuk membersihkan data testing sebelum peluncuran. Akun pengguna (`users`) dan konfigurasi sistem (`system_configs`) tetap utuh; data yang direset mencakup member, penabung, rekening, transaksi, jurnal, laporan kas, permintaan penutupan, OTP pemulihan password, token aplikasi mobile, audit log, sesi login, cache, dan antrean. Perintah ini menulis satu entri audit log setiap kali dijalankan, dan ditolak sepenuhnya pada `APP_ENV=production` tanpa ada flag yang dapat melewatkannya: pembersihan data testing wajib dilakukan saat environment masih lokal atau staging sebelum peluncuran, dan di production perintah ini hanya bisa membaca jumlah baris melalui `--dry-run`.
- Penggunaan browser cache harus dimatikan pada seluruh respons (Cache-Control: no-store) untuk mencegah akses back-button setelah logout.
- Library pihak ketiga untuk fitur front-end (seperti QR Scanner) wajib di-bundle secara lokal untuk mematuhi kebijakan CSP.
7.2 Panduan Desain Antarmuka
- Antarmuka harus sederhana dan berorientasi pada proses loket.
- Halaman Teller harus memprioritaskan pencarian nasabah, nominal transaksi, validasi, dan pencetakan struk.
- Nominal uang harus ditampilkan dalam format Rupiah.
- Field nominal hanya menerima angka positif.
- Sistem harus memberikan pesan kesalahan yang jelas untuk slip tidak lengkap, nominal tidak valid, saldo tidak cukup, PIN salah, atau akses ditolak.
- Tombol simpan transaksi harus mencegah pengiriman ganda.
- Status transaksi harus terlihat jelas, seperti Menunggu, Sukses, Ditolak, atau Dibalik (koreksi).
- Halaman Administrator menampilkan detail laporan harian beserta catatan approval atau penolakan.
- Halaman Nasabah hanya boleh menampilkan data milik nasabah yang sedang login.
- Aksi berisiko seperti approval dan pemindahan kas harus meminta konfirmasi.
- Informasi sensitif harus disamarkan dan tidak ditampilkan dalam log frontend.
7.3 Batasan Alur Bisnis
- Administrator tidak boleh memproses setoran dan penarikan.
- Teller tidak boleh mengedit atau menghapus transaksi final.
- Teller tidak boleh menyetujui laporan hariannya sendiri.
- Administrator tidak boleh melakukan transaksi loket.
- Nasabah tidak boleh mengakses modul transaksi, jurnal, akun internal, atau data nasabah lain.
- Laporan yang sudah disetujui tidak dapat berubah; bila arus kas tanggalnya berubah, approval ditolak.
- Pemindahan kas hanya terbit saat approval laporan oleh Administrator.
- Transaksi penarikan wajib menggunakan PIN 6-digit nasabah.
- Setiap transaksi harus menghasilkan jurnal akuntansi yang seimbang.
7.4 Asumsi
- Satu nasabah memiliki paling banyak satu rekening aktif.
- Satu transaksi setoran atau penarikan diproses oleh satu Teller.
- Konfigurasi saldo minimum disimpan secara global dan default-nya Rp10.000.
- QR Code berisi nilai yang dapat digunakan untuk menemukan profil nasabah atau rekening terkait.
- Nasabah mengakses dasbor melalui mekanisme autentikasi yang disediakan aplikasi.
- Detail perangkat cetak, kamera, dan format fisik slip dapat disesuaikan dengan fasilitas sekolah.