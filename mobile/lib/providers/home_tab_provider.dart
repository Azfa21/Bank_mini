import 'package:flutter/foundation.dart';

/// Mengatur tab aktif di `HomeShell`.
///
/// Dibuat terpisah agar tombol di dalam Beranda (misalnya "Lihat riwayat
/// mutasi") bisa memindah tab, bukan sekadar jadi tombol tanpa tujuan.
class HomeTabController extends ChangeNotifier {
  int _index = 0;

  int get index => _index;

  void select(int value) {
    if (value == _index) {
      return;
    }
    _index = value;
    notifyListeners();
  }
}
