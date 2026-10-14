import 'package:flutter/material.dart';

import 'app_colors.dart';

/// Skala tipografi yang dipakai view web nasabah, dipindahkan apa adanya agar
/// ukuran dan bobot di mobile sama dengan di browser.
///
/// Padanan kelas Tailwind dicatat di tiap konstanta supaya mudah dicari balik
/// ke `resources/views/nasabah/*.blade.php` saat salah satu sisi berubah.
class AppText {
  const AppText._();

  /// `text-[10px]` — caption QR, disclaimer dokumen.
  static const TextStyle caption = TextStyle(
    fontSize: 10,
    fontWeight: FontWeight.w400,
    color: AppColors.stone500,
  );

  /// `text-xs` — label kecil di kartu, nomor halaman.
  static const TextStyle micro = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w500,
    color: AppColors.stone600,
  );

  /// `text-[13px]` — ukuran meta yang paling sering dipakai web: label field,
  /// chip, teks bantu, header tabel.
  static const TextStyle meta = TextStyle(
    fontSize: 13,
    fontWeight: FontWeight.w500,
    color: AppColors.stone600,
  );

  /// `text-[13px] font-semibold`.
  static const TextStyle metaStrong = TextStyle(
    fontSize: 13,
    fontWeight: FontWeight.w600,
    color: AppColors.stone700,
  );

  /// `text-sm` — isi sekunder, sel tabel.
  static const TextStyle body = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: AppColors.stone900,
  );

  /// `text-[15px] font-semibold` — teks utama tombol dan kartu.
  static const TextStyle action = TextStyle(
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: AppColors.ink,
  );

  /// `text-[13px] font-semibold text-brand-900` — eyebrow di atas judul.
  static const TextStyle eyebrow = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.w600,
    color: AppColors.brand900,
  );

  /// `h1 text-2xl font-bold` — judul halaman.
  static const TextStyle title = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.w700,
    color: AppColors.stone900,
  );

  /// `text-[26px] font-bold` — judul halaman autentikasi.
  static const TextStyle titleAuth = TextStyle(
    fontSize: 26,
    fontWeight: FontWeight.w700,
    color: AppColors.stone900,
  );

  /// `text-[32px] font-bold` — saldo berjalan di kartu utama.
  static const TextStyle balance = TextStyle(
    fontSize: 32,
    fontWeight: FontWeight.w700,
    color: AppColors.stone900,
  );

  /// `text-lg font-bold` — nama pemegang rekening pada kartu.
  static const TextStyle cardHolder = TextStyle(
    fontSize: 18,
    fontWeight: FontWeight.w700,
    color: AppColors.white,
  );

  /// `text-[15px] font-bold text-stone-900` — judul bagian.
  static const TextStyle sectionTitle = TextStyle(
    fontSize: 15,
    fontWeight: FontWeight.w700,
    color: AppColors.stone900,
  );

  /// `text-sm font-semibold text-stone-800` — label field.
  static const TextStyle fieldLabel = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.w600,
    color: AppColors.stone800,
  );

  /// `text-[13px] font-semibold` — label kecil pada tombol sekunder.
  static const TextStyle buttonSmall = TextStyle(
    fontSize: 13,
    fontWeight: FontWeight.w600,
  );

  /// Menyalakan angka tabular (padanan `.tnum` di web).
  ///
  /// Alasan: web memakai `font-variant-numeric: tabular-nums` supaya kolom
  /// rupiah dan tanggal rata vertikal dan mudah dipindai teller. Di Flutter
  /// efek yang sama dicapai lewat `FontFeature.tabularFigures()`.
  static TextStyle tnum(TextStyle style) =>
      style.copyWith(fontFeatures: const [FontFeature.tabularFigures()]);
}
