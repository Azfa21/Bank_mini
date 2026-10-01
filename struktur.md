FASE 1 — Autentikasi, RBAC, dan Keamanan Akses (15/15) — Status: Selesai  
Sub-Fitur:
- Login terpusat pengguna internal dan nasabah
- Manajemen sesi atau token pengguna
- Pembatasan akses berdasarkan role
- Keamanan kredensial dan PIN nasabah
- Pemulihan kredensial (Lupa Password & Reset PIN)
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
- Buat halaman dan komponen UI pemulihan kredensial (Lupa Password & Reset PIN).
- Buat endpoint verifikasi identitas mandiri dan reset password pegawai serta PIN nasabah.
- Implementasikan rate limiting dan audit logging untuk pemulihan kredensial.
FASE 2 — Manajemen Pengguna dan Rekening Nasabah (9/9) — Status: Selesai  
Sub-Fitur:
- Pengelolaan akun Administrator, Teller, dan Supervisor
- Pengelolaan profil nasabah atau siswa
- Pembuatan nomor rekening otomatis
- Pengaturan saldo mengendap minimum
- Konfigurasi perangkat pemindai QR Code
Tasks:
- Buat halaman daftar akun pengguna internal.
- Buat halaman tambah dan ubah akun pengguna.
- Tambahkan mekanisme pemblokiran dan penghapusan akun sesuai hak Administrator.
- Implementasikan penetapan role Administrator, Teller, dan Supervisor.
- Buat halaman data profil nasabah atau siswa.
- Implementasikan pembuatan nomor rekening berdasarkan tahun ajaran dan nomor urut siswa.
- Tambahkan mekanisme pencarian rekening berdasarkan nomor rekening.
- Buat halaman konfigurasi saldo mengendap minimum.
- Buat halaman konfigurasi konektivitas perangkat pemindai QR Code.
FASE 3 — Operasional Transaksi Teller (11/11) — Status: Selesai  
Sub-Fitur:
- Autentikasi profil nasabah melalui QR Code atau nomor rekening
- Transaksi setoran tunai
- Transaksi penarikan tunai
- Validasi saldo dan nominal uang fisik
- Pencetakan struk transaksi
Tasks:
- Buat halaman pencarian profil nasabah melalui nomor rekening.
- Implementasikan pemindaian QR Code melalui kamera atau webcam.
- Tambahkan mekanisme validasi kelengkapan slip setoran.
- Implementasikan pencocokan nominal slip dengan uang fisik setoran.
- Implementasikan penyimpanan transaksi setoran secara atomik.
- Tambahkan mekanisme penambahan saldo rekening setelah setoran valid.
- Tambahkan mekanisme validasi kelengkapan slip penarikan.
- Implementasikan validasi PIN nasabah sebelum penarikan diproses.
- Implementasikan validasi saldo dan saldo mengendap minimum Rp10.000.
- Implementasikan penyimpanan transaksi penarikan secara atomik.
- Buat pencetakan struk setoran dan penarikan.
FASE 4 — Penutupan Kas dan Approval Supervisor (10/10) — Status: Selesai  
Sub-Fitur:
- Rekonsiliasi kas harian Teller
- Pembuatan laporan harian Teller
- Validasi dan persetujuan Supervisor
- Pemindahan tanggung jawab kas ke brankas utama
- Penyelesaian selisih dan audit transaksi
Tasks:
- Buat halaman rekap transaksi harian Teller.
- Implementasikan perhitungan total setoran dan total penarikan.
- Implementasikan perhitungan saldo akhir dengan rumus saldo awal ditambah total setoran dikurangi total penarikan.
- Tambahkan mekanisme input dan validasi uang fisik di laci Teller.
- Tambahkan mekanisme penguncian cetak laporan saat terdapat selisih kas.
- Buat pencetakan draf Laporan Harian Teller.
- Buat halaman pemeriksaan laporan untuk Supervisor.
- Implementasikan mekanisme approval laporan oleh Supervisor.
- Implementasikan pembukuan pemindahan tanggung jawab kas ke brankas utama.
- Buat pencatatan real-time log dan audit jurnal akuntansi.
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
- Buat halaman audit jurnal akuntansi untuk Administrator dan Supervisor.
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
- Integrasi Jurnal Pemindahan Kas Brankas (Akun 102 Kas Brankas)
Tasks:
- Implementasikan pencatatan jurnal otomatis saat approval kas brankas (Debet 102 Kas Brankas, Kredit 101 Kas Teller).
- Buat halaman dan filter Buku Besar per akun (101, 102, 201).
- Buat halaman Neraca Saldo dan fitur ekspor CSV/Excel laporan akuntansi.
FASE 8 — Keamanan Transaksi dan Pengalaman Pengguna (3/3) — Status: Selesai  
Sub-Fitur:
- Perlindungan brute-force PIN nasabah (Failed PIN attempts lock)
- Anti-collision sequence generator transaksi dan laporan
- Dashboard analitik & indikator likuiditas
Tasks:
- Implementasikan penguncian akun dan audit log saat salah PIN berulang kali.
- Refactor generator nomor transaksi dan nomor laporan dengan sequence anti-collision.
- Tambahkan widget analitik keuangan dan grafik performa pada dashboard.
FASE 9 — Penyempurnaan Berbasis Riset Pengguna (24/24) — Status: Selesai  
Sub-Fitur:
- Rincian uang kertas & logam pada transaksi dan penutupan kas
- Biaya administrasi penarikan (akun pendapatan 401)
- Struk transaksi lengkap (nama bank, referensi jurnal, terbilang, biaya admin, status)
- Jurnal koreksi/pembalik oleh Supervisor
- Pencarian nasabah berdasarkan nama/NIS/nomor rekening
- Registrasi nasabah didampingi Teller
- Laporan Laba Rugi dan Neraca (akun 401)
- Konfigurasi produk (nama bank, jam operasional, batas penarikan tanpa pesanan)
Tasks:
- Buat helper CashDenomination dan Terbilang sebagai fondasi fitur revisi.
- Buat migration kolom admin_fee, denomination_breakdown, dan kolom reversal pada transactions.
- Buat migration kolom closing_breakdown dan total_admin_fee pada daily_reports serta default konfigurasi produk.
- Implementasikan perhitungan biaya administrasi penarikan dan jurnal pendapatan akun 401.
- Implementasikan validasi batas maksimal penarikan tanpa pesanan.
- Buat komponen Blade input rincian pecahan uang kertas & logam.
- Hubungkan form setoran, penarikan, dan penutupan kas dengan rincian pecahan.
- Simpan dan tampilkan rincian pecahan pada laporan harian beserta halaman cetak.
- Perkaya struk transaksi dengan informasi bank, referensi jurnal, terbilang, biaya admin, dan status.
- Buat ReversalService untuk jurnal pembalik dan pemulihan saldo.
- Tambahkan kolom penanda koreksi pada transaksi asal.
- Buat endpoint koreksi transaksi dengan otorisasi Supervisor.
- Tambahkan aksi koreksi pada halaman detail laporan Supervisor.
- Tolak koreksi transaksi bila laporan harian tanggal tersebut sudah disetujui.
- Uji integritas koreksi: transfer, saldo tidak mencukupi, dampak rekap kas, dan tampilan mutasi/buku tabungan.
- Perbaiki kata terbilang Rupiah agar tidak menyisakan "Nol" berlebih.
- Perbaiki down() migrasi customers dan daily_reports agar proses rollback berjalan.
- Verifikasi menghapus dan menyetel ulang seluruh migrasi pada database uji.
- Perluas pencarian nasabah berdasarkan nama, NIS, dan nomor rekening.
- Buat halaman hasil pencarian ketika terdapat lebih dari satu kecocokan.
- Buat CustomerRegistrationService dan modul registrasi nasabah untuk Teller.
- Tambahkan akun pendapatan 401 ke chart of accounts.
- Buat laporan Laba Rugi dan Neraca untuk Administrator dan Supervisor.
- Perluas halaman konfigurasi sistem Administrator.
- Perbarui menu navigasi dan dashboard Teller untuk fitur baru.
Asumsi:
- Role Nasabah mengakses dasbor dan riwayat mutasi melalui autentikasi atau identitas rekening yang telah ditentukan sistem.
- Detail tabel database tidak dirinci karena struktur ini belum mencakup skema basis data.
- Pemindai QR Code menggunakan kamera atau webcam yang tersedia pada perangkat Teller.