import 'package:intl/intl.dart';

class Formatters {
  const Formatters._();

  static final NumberFormat _currency = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  static final DateFormat _date = DateFormat('dd MMM yyyy', 'id_ID');
  static final DateFormat _dateTime = DateFormat('dd MMM yyyy, HH:mm', 'id_ID');

  static String rupiah(int value) => _currency.format(value);

  static String date(DateTime? value) =>
      value == null ? '-' : _date.format(value.toLocal());

  static String dateTime(DateTime? value) =>
      value == null ? '-' : _dateTime.format(value.toLocal());

  static String apiDate(DateTime value) =>
      DateFormat('yyyy-MM-dd').format(value);
}
