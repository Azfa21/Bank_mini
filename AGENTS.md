# Bank Mini Sekolah

Aplikasi perbankan MINI untuk lingkungan sekolah: loket teller, administrator (pemroses
approval laporan dan koreksi), dan rekening tabungan siswa (nasabah; web plus aplikasi
Flutter). Ada dua runtime: web (Laravel, Tailwind CSS v4, Blade) dan aplikasi nasabah
(direktori `mobile/`). Peran aktif di web: `admin`, `teller`, `nasabah`; peran lama
`supervisor` sudah digabungkan ke `admin` (halaman daftar pengguna tidak lagi menampilkannya).

## Perintah

```bash
php artisan test              # seluruh test suite
php artisan test tests/Feature # feature test saja
php vendor/bin/pint            # format kode
php vendor/bin/phpstan analyse # analisis statis
npm run build                  # kompilasi aset frontend web
cd mobile && flutter analyze   # analisis statis aplikasi nasabah
cd mobile && flutter test      # test aplikasi nasabah
```

## Struktur

- `app/Services/` — logika bisnis. Invariant transaksional (misalnya konsumsi OTP satu kali,
 aturan saldo) WAJIB dijaga di dalam `DB::transaction`.
- `resources/css/app.css` — token desain dan utilitas (`paper`, `ink`, `brand-*`, `olive-*`,
  `.ledger-rule`, `.tnum`, `.tap-target`). Setiap keputusan desain di file ini disertai alasan
  dalam komentar. Ikuti pola itu saat menambah token baru.
- `resources/views/auth/` — shell autentikasi. Halaman login dan pemulihan kredensial memakai
  tanda, warna, dan jarak yang sama agar terbaca sebagai satu sistem.
- `tests/Feature/`, `tests/Unit/` — test. `tests/Support/` berisi helper yang dijalankan sebagai
  proses terpisah (bukan test PHPUnit).
- `mobile/lib/` — aplikasi nasabah (Flutter). `core/theme/app_colors.dart` dan
  `core/theme/app_text.dart` adalah salinan token `resources/css/app.css`; ubah satu sisi, lalu
  samakan sisi lain. `mobile/lib/widgets/` adalah padanan komponen Blade.

## Konvensi

- Copy ditulis dalam Bahasa Indonesia.
- Setiap testimonial, statistik, dan klaim harus berasal dari data nyata. Jangan fabrikasi.
- Tautan dan tombol harus punya tujuan nyata. Jangan kirim kontrol mati.

<!-- antislop:start -->
## antislop
For UI, copy, people, mobile layout, or code comments work, read `antislop.md` (core) and then the skill for the task:
- UI / visual: `skills/antislop-ui/SKILL.md`
- Copy & text: `skills/antislop-copywriting/SKILL.md`
- People: `skills/antislop-human/SKILL.md`
- Mobile / responsive: `skills/antislop-layoutmobile/SKILL.md`
- Code comments: `skills/antislop-code/SKILL.md`
Before starting, ask the user when antislop applies: during the work, or after it is done.
To update antislop later: download `antislop.md` again, or run `npx antislop-ai --update` if it was installed as skill folders.
<!-- antislop:end -->
