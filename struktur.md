FASE 1 — Autentikasi, RBAC, dan Keamanan Akses (12/12) — Status: Selesai  
Sub-Fitur:
- Login terpusat pengguna internal dan nasabah
- Manajemen sesi atau token pengguna
- Pembatasan akses berdasarkan role
- Keamanan kredensial dan PIN nasabah
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
FASE 2 — Manajemen Pengguna dan Rekening Nasabah (9/9) — Status: Selesai  
Sub-Fitur:
- Pengelolaan akun Administrator, Teller, dan Supervisor
- Pengelolaan profil nasabah atau siswa
- Pembuatan nomor rekening otomatis
- PlanPengaturan saldo mengendap minimum·auto coding
- Konfigurasi perangkat pemindai QR Code
D:\RPL\mdTasks:
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
Asumsi:
- Role Nasabah mengakses dasbor dan riwayat mutasi melalui autentikasi atau identitas rekening yang telah ditentukan sistem.
- Detail tabel database tidak dirinci karena struktur ini belum mencakup skema basis data.
- Pemindai QR Code menggunakan kamera atau webcam yang tersedia pada perangkat Teller.