import 'package:flutter/material.dart';

/// Palet aplikasi, disalin satu per satu dari `resources/css/app.css` di proyek
/// web Bank Mini. Warna di sini adalah kontrak lintas platform: selama token
/// web dan token Flutter sama, halaman web dan aplikasi mobile terbaca sebagai
/// satu sistem.
///
/// Setiap kelompok disertai alasan singkat, mengikuti pola yang dipakai di
/// `app.css`. Angka kontras dihitung terhadap permukaan tempat warna itu
/// biasanya dipakai, jadi jangan ubah nilainya tanpa mengukur ulang.
class AppColors {
  const AppColors._();

  // Brand: terakota tanah liat sebagai identitas buku tabungan sekolah.
  // Alasan web: hangat dan tidak generik. Dipakai untuk aksi, chrome, dan
  // semua angka kredit sehingga arah uang dikenali dari warna, bukan hanya
  // tanda + / -.
  static const Color brand50 = Color(0xFFFBF4EF);
  static const Color brand100 = Color(0xFFF5E4D9);
  static const Color brand200 = Color(0xFFEAC8B4);
  static const Color brand300 = Color(0xFFDCA88C);
  static const Color brand400 = Color(0xFFCB8362);
  static const Color brand500 = Color(0xFFB96A45);
  static const Color brand600 = Color(0xFFA45434);
  static const Color brand700 = Color(0xFF8E4428);
  static const Color brand800 = Color(
    0xFF743722,
  ); // teks brand-900 di atas brand-50 = 8.4:1
  static const Color brand900 = Color(
    0xFF5E2D1C,
  ); // brand-100 di atas brand-900 = 9.1:1
  static const Color brand950 = Color(0xFF401D11);

  // Olive: menandai makna positif (status aktif, sukses, ekspor) agar tetap
  // beda dari aksen terakota. Web sengaja bolong di 300/400/900/950; jangan
  // diisi sembarang supaya tidak menyimpang dari sistem.
  static const Color olive50 = Color(0xFFF4F5EC);
  static const Color olive100 = Color(0xFFE7EAD4);
  static const Color olive200 = Color(0xFFCFD5AC);
  static const Color olive500 = Color(0xFF7C8A4A);
  static const Color olive600 = Color(0xFF697540);
  static const Color olive700 = Color(
    0xFF566136,
  ); // teks olive-700 di atas olive-50 = 6.2:1
  static const Color olive800 = Color(0xFF454D2C);

  // Kertas dan tinta: latar pasir hangat agar angka loket terbaca lama tanpa
  // silau, dengan tinta cokelat tua sebagai teks utama.
  // Web juga mendefinisikan `--color-ink-soft`, tetapi tidak pernah dipakai di
  // satu pun view. Token itu sengaja tidak diport supaya tidak jadi kode mati.
  static const Color paper = Color(0xFFF7F1E8);
  static const Color ink = Color(0xFF2A2018); // ink di atas paper = 13:1

  // Netral stone. Nilai ini adalah padanan hex terdekat dari `stone-*` Tailwind
  // v4 (yang ditulis dalam oklch di CSS). Dipakai untuk garis, label sekunder,
  // dan teks bantu.
  static const Color stone50 = Color(0xFFFAFAF9);
  static const Color stone100 = Color(0xFFF5F5F4);
  static const Color stone200 = Color(0xFFE7E5E4); // garis tepi kartu
  static const Color stone300 = Color(0xFFD6D3D1); // garis tepi input
  static const Color stone400 = Color(0xFFA8A29E);
  static const Color stone500 = Color(0xFF78716C);
  static const Color stone600 = Color(
    0xFF57534E,
  ); // teks bantu di atas paper = 6.8:1
  static const Color stone700 = Color(0xFF44403C);
  static const Color stone800 = Color(0xFF292524); // garis header dokumen
  static const Color stone900 = Color(0xFF1C1917);

  // Merah: debit dan galat. Angka merah selalu didampingi tanda minus, jadi
  // warna bukan satu-satunya pembawa makna (aman untuk buta warna).
  static const Color red50 = Color(0xFFFEF2F2);
  static const Color red200 = Color(0xFFFECACA);
  static const Color red700 = Color(
    0xFFB91C1C,
  ); // red-700 di atas red-50 = 6.4:1
  static const Color red800 = Color(0xFF991B1B);

  // Amber: peringatan yang butuh tindakan siswa, terutama rekening belum aktif.
  static const Color amber50 = Color(0xFFFFFBEB);
  static const Color amber200 = Color(0xFFFDE68A);
  static const Color amber900 = Color(
    0xFF78350F,
  ); // amber-900 di atas amber-50 = 9.3:1

  static const Color white = Color(0xFFFFFFFF);
  static const Color black = Color(0xFF000000);
}
