import 'package:flutter/foundation.dart';

import '../core/api/api_exception.dart';
import '../models/dashboard_data.dart';
import '../services/account_service.dart';

class DashboardProvider extends ChangeNotifier {
  DashboardProvider(this._service);

  final AccountService _service;

  DashboardData? _data;
  bool _loading = false;
  String? _error;

  DashboardData? get data => _data;
  bool get loading => _loading;
  String? get error => _error;

  Future<void> load() async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      _data = await _service.dashboard();
    } on ApiException catch (error) {
      _error = error.message;
    } catch (_) {
      _error = 'Gagal memuat data. Silakan coba lagi.';
    } finally {
      _loading = false;
      notifyListeners();
    }
  }
}
