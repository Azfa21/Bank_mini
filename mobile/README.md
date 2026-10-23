# Bank Mini Nasabah (mobile)

Aplikasi nasabah Bank Mini Sekolah: cek saldo, riwayat mutasi, kartu QR virtual, dan rekening koran PDF. Berkomunikasi dengan server Laravel melalui API di `routes/api.php`
(grup `mobile/`), diautentikasi dengan token perangkat (`mobile_tokens`).

Struktur:
- `lib/core/` — konfigurasi, klien API, penyimpanan token, token desain (`app_colors.dart`,
  `app_text.dart` adalah salinan token `resources/css/app.css`; ubah satu sisi, samakan sisi lain).
- `lib/models/`, `lib/providers/`, `lib/services/` — data dan state.
- `lib/screens/`, `lib/widgets/` — layar dan komponen.

## Menjalankan
```bash
flutter pub get
flutter analyze   # analisis statis
flutter test      # test aplikasi
# Alamat API diatur lewat --dart-define; default emulator Android: http://10.0.2.2:8000/api
flutter run --dart-define=API_BASE_URL=http://<host>:<port>/api
```
Konfigurasi API dan nama bank ada di `lib/core/config/app_config.dart`.
