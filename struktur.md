FASE 1 — Autentikasi, RBAC, dan Keamanan Akses (15/15) — Status: Selesai  
Sub-Fitur:
- Login terpusat pengguna internal dan nasabah
- Manajemen sesi atau token pengguna
- Pembatasan akses berdasarkan role
- Keamanan kredensial dan PIN nasabah
- Pemulihan kredensial (Lupa Password)
- Pencatatan aktivitas akses
- Pengamanan header HTTP (CSP, X-Frame-Options, dll)
- Pembatasan laju (Rate Limiting) autentikasi
Tasks:
- Buat halaman login terpusat untuk seluruh pengguna.
- Implementasikan autentikasi dengan password hashing.
- Tambahkan mekanisme session atau token pengguna.
- Implementasikan middleware pembatasan akses berdasarkan role.
- Tambahkan mekanisme pengalihan pengguna tanpa hak akses.
- Buat validasi PIN nasabah 6-digit.
- Implementasikan pembatasan akses nasabah terhadap data pengguna lain.
- Tambahkan validasi otorisasi sebelum query SQL dijalankan.
- Buat penanganan logout dan pengakhiran sesi.
- Implementasikan Security Headers middleware dan pembatasan cache.
- Implementasikan rate limiting untuk endpoint login.
- Buat tabel dan service audit log.
- Buat halaman dan komponen UI pemulihan kredensial (Lupa Password).
- Buat endpoint verifikasi identitas mandiri dan reset password pegawai serta nasabah.
- Implementasikan rate limiting dan audit logging untuk pemulihan kredensial.
FASE 2 — Manajemen Pengguna dan Rekening Nasabah (9/9) — Status: Selesai  
Sub-Fitur:
- Pengelolaan akun Administrator dan Teller
- Pengelolaan anggota (siswa belum punya rekening) dan penabung (pemilik rekening)
- Pembuatan nomor rekening otomatis
- Pengaturan saldo mengendap minimum
- Pemindai QR Code pada halaman pencarian Teller
Tasks:
- Buat halaman daftar akun pengguna internal.
- Buat halaman tambah dan ubah akun pengguna.
- Tambahkan mekanisme pemblokiran dan penghapusan akun sesuai hak Administrator.
- Implementasikan penetapan role Administrator dan Teller.
- Buat halaman anggota dan penabung: anggota tanpa rekening, penabung aktif, dan alumni, dengan memfilter per status.
- Buat MemberImportService untuk impor anggota massal lewat CSV.
- Implementasikan pembuatan nomor rekening berdasarkan tahun ajaran dan nomor urut siswa.
- Tambahkan mekanisme pencarian rekening secara cepat berdasarkan nomor rekening, QR Code, NIS, dan nama.
- Buat halaman konfigurasi sistem: nama bank, saldo mengendap, setoran minimal aktivasi, dan daftar kelas-jurusan untuk kenaikan kelas.
FASE 3 — Operasional Transaksi Teller (11/11) — Status: Selesai  
Sub-Fitur:
- Autentikasi profil nasabah melalui QR Code atau nomor rekening
- Transaksi setoran tunai
- Transaksi penarikan tunai
- Validasi saldo dan nominal transaksi
- Pencetakan struk transaksi
Tasks:
- Buat halaman pencarian profil nasabah melalui nomor rekening.
- Implementasikan pemindaian QR Code melalui kamera atau webcam pada halaman pencarian.
- Implementasikan validasi rekening aktif sebelum transaksi diproses.
- Buat form transaksi setoran dengan nomor slip (opsional) dan nominal sesuai uang fisik yang diterima.
- Implementasikan validasi nominal positif dan keunikan nomor slip.
- Implementasikan penyimpanan transaksi setoran secara atomik (saldo, transaksi, jurnal).
- Buat form transaksi penarikan dengan nominal dan PIN nasabah 6-digit.
- Implementasikan validasi saldo dan saldo mengendap minimum Rp10.000.
- Implementasikan penyimpanan transaksi penarikan secara atomik dengan commit dan rollback.
- Kunci transaksi berstatus final agar Teller tidak dapat mengubah atau menghapusnya.
- Buat pencetakan struk setoran dan penarikan.
FASE 4 — Penutupan Kas dan Approval Administrator (10/10) — Status: Selesai  
Sub-Fitur:
- Rekap kas harian Teller
- Pembuatan laporan harian Teller
- Validasi dan persetujuan Administrator
- Pemindahan tanggung jawab kas ke kas utama
- Siklus penolakan laporan dengan catatan audit
Tasks:
- Buat halaman penutupan kas Teller berdasarkan tanggal kerja.
- Buat endpoint rekap transaksi harian Teller, termasuk dampak jurnal pembalik (koreksi).
- Implementasikan perhitungan saldo awal laci dari mutasi kas yang belum tersapu, total setoran, total penarikan, dan saldo akhir sistem.
- Buat tampilan cetak Laporan Harian Teller.
- Terbitkan laporan berstatus menunggu validasi langsung saat dibuat Teller.
- Buat halaman pemeriksaan laporan untuk Administrator.
- Implementasikan approval laporan oleh Administrator dengan larangan menyetujui laporan milik sendiri.
- Implementasikan penolakan laporan dengan catatan wajib beserta penerbitan ulang dengan nomor laporan yang sama.
- Tolak approval bila arus kas tanggal tersebut berubah setelah laporan dibuat.
- Implementasikan pembukuan pemindahan seluruh kas laci ke kas utama saat approval (Debet 102 Kas Utama, Kredit 101 Kas Teller) beserta audit log.
FASE 5 — Informasi Mandiri Nasabah dan Integritas Akuntansi (8/8) — Status: Selesai  
Sub-Fitur:
- Dasbor saldo nasabah
- Riwayat mutasi rekening
- Jurnal akuntansi transaksi
- Mekanisme double-entry
- Konsistensi dan rollback transaksi
Tasks:
- Buat halaman dasbor saldo berjalan nasabah.
- Buat halaman riwayat mutasi rekening nasabah.
- Tambahkan mekanisme pembatasan data agar nasabah hanya melihat rekening sendiri.
- Implementasikan pencatatan dua baris jurnal untuk setiap transaksi berhasil.
- Tambahkan mekanisme pembukuan Debet Kas 101 dan Kredit Tabungan 201.
- Implementasikan validasi keseimbangan jurnal akuntansi.
- Tambahkan mekanisme transaksi database dengan commit dan rollback.
- Buat halaman audit jurnal akuntansi untuk Administrator.
FASE 6 — Transaksi Lanjutan dan Layanan Nasabah (4/4) — Status: Selesai  
Sub-Fitur:
- Transfer antar rekening nasabah (Overbooking/P2P)
- Cetak buku tabungan fisik (Passbook printing)
- Ekspor rekening koran digital (e-Statement PDF)
- Kartu nasabah virtual & generator QR Code
Tasks:
- Buat endpoint dan modul transaksi transfer antar rekening dengan double-entry journal (Debet 201 Pengirim, Kredit 201 Penerima).
- Buat halaman dan format cetak buku tabungan fisik berdasarkan indeks baris buku.
- Buat fitur cetak rekening koran resmi e-Statement PDF dengan watermark dan verifikasi.
- Buat tampilan kartu nasabah virtual ber-QR Code di dashboard nasabah dan admin.
FASE 7 — Modul Laporan Keuangan dan Akuntansi Lengkap (3/3) — Status: Selesai  
Sub-Fitur:
- Buku Besar (General Ledger)
- Neraca Saldo (Trial Balance) dan Laporan Likuiditas
- Pemindahan Kas Utama (Akun 102 Kas Utama)
Tasks:
- Implementasikan pencatatan jurnal otomatis saat approval laporan harian (Debet 102 Kas Utama, Kredit 101 Kas Teller).
- Buat halaman dan filter Buku Besar per akun (101, 102, 201).
- Buat halaman Neraca Saldo beserta ekspor laporan akuntansi ke CSV.
FASE 8 — Keamanan Transaksi dan Pengalaman Pengguna (3/3) — Status: Selesai  
Sub-Fitur:
- Perlindungan brute-force PIN nasabah (Failed PIN attempts lock)
- Anti-collision sequence generator transaksi dan laporan
- Dashboard analitik
Tasks:
- Implementasikan penguncian akun dan audit log saat salah PIN berulang kali.
- Refactor generator nomor transaksi dan nomor laporan menggunakan tabel document_sequences.
- Tambahkan kartu metrik analitik dan grafik keuangan pada dashboard Administrator.
FASE 9 — Penyempurnaan Berbasis Riset Pengguna (15/15) — Status: Selesai  
Sub-Fitur:
- Struk transaksi lengkap (nama bank, referensi jurnal, terbilang, status)
- Jurnal koreksi/pembalik oleh Administrator
- Pencarian nasabah berdasarkan nama/NIS/nomor rekening
- Registrasi nasabah didampingi Teller
- Buku Besar dan Neraca Saldo
- Konfigurasi produk (nama bank, saldo mengendap, setoran minimal aktivasi)
Tasks:
- Buat helper Terbilang sebagai fondasi isi struk.
- Perbaiki kata terbilang Rupiah agar tidak menyisakan "Nol" berlebih.
- Perkaya struk transaksi: nama bank, nomor referensi jurnal, terbilang, saldo sebelum/sesudah, dan status.
- Buat ReversalService untuk jurnal pembalik dan pemulihan saldo.
- Tambahkan kolom penanda koreksi pada transactions (reversal_of_transaction_id, reversed_at, reversed_by, reversal_reason).
- Buat endpoint koreksi transaksi dengan otorisasi Administrator.
- Tambahkan aksi koreksi dan tampilan riwayat pembalik pada halaman transaksi.
- Tolak koreksi transaksi bila laporan harian tanggal transaksi sudah disetujui.
- Uji integritas koreksi: transfer, saldo tidak mencukupi, dan dampak rekap kas.
- Perluas pencarian nasabah berdasarkan nama, NIS, dan nomor rekening.
- Buat halaman hasil pencarian ketika terdapat lebih dari satu kecocokan.
- Buat MemberRegistrationService dan modul registrasi nasabah untuk Teller.
- Sediakan pembuatan password aplikasi dan PIN transaksi oleh penabung sendiri saat setoran aktivasi (SaverCredentialService, pin_hash dan password_hash hashed).
- Buat laporan Buku Besar dan Neraca Saldo untuk Administrator.
- Perbarui menu navigasi, dashboard Teller, dan halaman konfigurasi sistem Administrator.
FASE 10 — Aplikasi Nasabah Mobile (9/9) — Status: Selesai  
Sub-Fitur:
- Aplikasi Flutter nasabah (mobile/) dengan autentikasi token perangkat
- Dasbor saldo, riwayat mutasi, kartu QR virtual, dan rekening koran PDF dari HP
- Ganti PIN dan lupa password lewat OTP email
Tasks:
- Buat tabel mobile_tokens beserta MobileTokenService (token di-hash, kedaluwarsa, pencatatan last_used_at).
- Buat API grup mobile/: login, logout, me, dashboard, card, mutasi, dan statement di belakang middleware mobile.auth.
- Buat endpoint ganti PIN nasabah lewat aplikasi dengan verifikasi PIN lama.
- Rutekan lupa password nasabah ke alur OTP email yang sama dengan web (request-otp dan reset).
- Bangun Flutter app: splash, login, dasbor, mutasi, detail mutasi, kartu QR, ganti PIN, profil, dan lupa password.
- Buat PDF rekening koran di sisi klien dengan paket pdf dan printing.
- Salin token desain web ke mobile/lib/core/theme/app_colors.dart dan app_text.dart beserta font Instrument Sans.
- Buat test MobileAuth, MobileDashboard, MobileMutasi, dan MobilePinChange.
- Tambahkan penghapusan token aplikasi mobile pada perintah bankmini:reset-nasabah.
FASE 11 — Anggota, Penabung, Kelulusan, dan Kenaikan Kelas (11/11) — Status: Selesai  
Sub-Fitur:
- Pemisahan Anggota (siswa belum punya rekening) dan Penabung (pemilik rekening)
- Impor anggota massal lewat CSV
- Kenaikan kelas per tahun ajaran
- Kelulusan: pencairan saldo di loket, permintaan penutupan rekening, keputusan Administrator, dan arsip angkatan
- Laporan bulanan
Tasks:
- Buat tabel members (anggota, dengan NIS sebagai primary key) dan ubah tabel customers menjadi savers (penabung) dengan soft delete dan tautan member.
- Pisahkan daftar Anggota dan halaman Penabung di modul Administrator.
- Buat MemberController, SaverController, dan pemblokiran penabung (rekening ditahan, login ditutup) tanpa menghapus datanya.
- Buat MemberImportService untuk impor anggota massal lewat CSV dengan pemetaan kolom.
- Buat ClassPromotionService: kenaikan kelas per tahun ajaran, siswa tinggal kelas, dan pengamanan penerapan ganda.
- Buat AccountClosureService dan tabel account_closures: permintaan penutupan rekening, pencairan saldo di atas saldo mengendap di loket, dan keputusan Administrator.
- Buat GraduationController dengan endpoint graduate, not-graduate, approveClosure, rejectClosure, dan graduateMember.
- Buat perintah GraduateBatch (php artisan bankmini:graduate --year=Tahun [--dry-run] [--operator=...]).
- Kunci login alumni di web dan aplikasi mobile.
- Buat MonthlyReportController dan MonthlyReportTest untuk laporan bulanan Administrator.
- Buat QrCodeController dan CardPrintTest untuk cetak kartu nasabah.
FASE 12 — Restrukturisasi Peran dan Model Kas (10/10) — Status: Selesai  
Sub-Fitur:
- Penggabungan role Supervisor ke Administrator; Administrator memeriksa, menyetujui/menolak laporan harian, dan mengotorisasi koreksi, dengan larangan approval laporan milik sendiri
- Model kas utama: setiap laporan disetujui menyapu seluruh kas laci ke Kas Utama (Debet 102, Kredit 101)
- Penutupan kas memakai saldo akhir sistem tanpa perbandingan uang fisik
- Penabung membuat sendiri password aplikasi dan PIN transaksi saat registrasi dan setoran aktivasi
Tasks:
- Buat migration merge_supervisor_role_into_admin dan ubah semua otorisasi approval/koreksi dari Supervisor ke Administrator (middleware role:admin).
- Ganti kolom supervisor_id menjadi approved_by pada cash_transfers.
- Tambahkan password_hash pada tabel savers dan SaverCredentialService agar penabung membuat sendiri password aplikasi dan dapat login di web maupun aplikasi mobile.
- Buat migration key_password_reset_otps_by_account dengan kolom account_type (staff/nasabah).
- Hapus kolom admin fee dari tabel terkait.
- Hapus kolom closing_balance_physical, closing_breakdown, dan cash_difference dari daily_reports; hapus physical_cash_amount dan denomination_breakdown dari transactions.
- Hapus konfigurasi jam operasional dan batas penarikan dari system_configs; konfigurasi tinggal bank_name, minimum_balance, dan min_deposit_to_activate.
- Kembalikan validasi penarikan ke aturan saldo mengendap: saldo akhir tidak boleh jatuh di bawah minimum_balance.
- Atur endpoint DailyReportController (Admin): approve dan reject laporan; approval menyapu seluruh kas laci ke Kas Utama dan menolak bila arus kas tanggalnya berubah setelah laporan dibuat.
- Perbarui halaman penutupan kas Teller, navigasi, dashboard, dan matriks otorisasi untuk struktur role baru (admin, teller, nasabah).
FASE 13 — Permintaan Kas Modal Teller dan Penamaan Kas Utama (8/8) — Status: Selesai  
Sub-Fitur:
- Pengajuan modal kas oleh Teller (uang tunai jaga-jaga penarikan), berulang bila uang laci kurang
- Keputusan Administrator: setujui (langsung cair) atau tolak dengan catatan wajib
- Jurnal pengiriman kas: Debet 101 Kas Teller, Kredit 102 Kas Utama
- Ikhtisar pos kas di dashboard Teller dan panel Administrator
- Pengajuan dan persetujuan kas ditutup setelah laporan penutupan kas hari itu dikirim
Tasks:
- Buat tabel cash_requests beserta kolom cash_request_id pada jurnal_akuntansi dan total_cash_received pada daily_reports.
- Buat CashRequestService: pengajuan tanpa pembatasan permintaan aktif, approval dengan jurnal dalam DB::transaction, penolakan dengan catatan, dan audit log.
- Buat halaman Teller (formulir, saldo laci hari ini, riwayat status) dan panel Administrator (pos kas dari jurnal akun 101/102, keputusan, riwayat).
- Rebita rekap laporan harian agar kas dari kas utama ikut menghitung saldo laci dan penyapuan akhir hari.
- Tampilkan saldo kas laci di dashboard Teller beserta tautan menu Permintaan Kas.
- Ganti seluruh penamaan brankas/khazanah menjadi kas utama pada aplikasi, data destination_location, dan dokumen.
- Tambahkan pengujian CashRequestTest untuk pengajuan, keputusan, jurnal, otorisasi, rekap, dan penyapuan laporan.
- Perbarui prd.md, task.md, dan use case (UC-50) beserta diagramnya.
Asumsi:
- Peran aktif aplikasi web adalah admin, teller, dan nasabah; nasabah mengakses dasbor melalui web atau aplikasi mobile dengan autentikasi masing-masing.
- Detail tabel database dirinci di prd.md (ERD dan aturan integritas).
- Pemindai QR Code menggunakan kamera atau webcam yang tersedia pada perangkat Teller.
