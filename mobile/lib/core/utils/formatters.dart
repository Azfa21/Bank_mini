import 'package:intl/intl.dart';

/// Format angka dan tanggal yang harus sama persis dengan sisi web.
///
/// Web memakai `number_format($nilai, 0, ',', '.')` dengan awalan `Rp ` dan
/// tanggal PHP `d/m/Y` / `d/m/Y H:i`. Helper di sini meniru keluaran itu agar
/// angka di aplikasi dan di struk/rekening koran tidak berbeda.
class Formatters {
  const Formatters._();

  /// Pola ribuan saja, tanpa simbol, tanpa desimal. Disusun eksplisit (bukan
  /// `NumberFormat.currency`) supaya tidak bergantung pada pola mata uang
  /// locale yang bisa menyisipkan kode mata uang di akhir.
  static final NumberFormat _thousands = NumberFormat('#,##0', 'id_ID');

  static final DateFormat _date = DateFormat('dd/MM/yyyy', 'id_ID');
  static final DateFormat _dateTime = DateFormat('dd/MM/yyyy HH:mm', 'id_ID');

  /// `Rp 1.234.567`
  static String rupiah(int value) => 'Rp ${_thousands.format(value)}';

  /// `+Rp 50.000` / `-Rp 25.000`. Tanda selalu eksplisit karena arah uang
  /// adalah informasi utama baris mutasi.
  static String signedRupiah(int value, {required bool isIncoming}) {
    final sign = isIncoming ? '+' : '-';
    return '$sign${rupiah(value.abs())}';
  }

  /// `02/10/2026`
  static String date(DateTime? value) =>
      value == null ? '-' : _date.format(value.toLocal());

  /// `02/10/2026 14:30`
  static String dateTime(DateTime? value) =>
      value == null ? '-' : _dateTime.format(value.toLocal());

  /// Format yang diminta endpoint API untuk filter tanggal.
  static String apiDate(DateTime value) =>
      DateFormat('yyyy-MM-dd').format(value);
}
