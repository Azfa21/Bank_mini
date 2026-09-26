import 'package:flutter/foundation.dart';

import '../core/api/api_exception.dart';
import '../models/account.dart';
import '../models/transaction.dart';
import '../services/account_service.dart';

class MutasiProvider extends ChangeNotifier {
  MutasiProvider(this._service);

  final AccountService _service;

  final List<Transaction> _transactions = [];
  TransactionMeta? _meta;
  Account? _account;
  bool _loading = false;
  bool _loadingMore = false;
  String? _error;

  String? _type;
  String? _dateFrom;
  String? _dateTo;

  List<Transaction> get transactions => List.unmodifiable(_transactions);
  TransactionMeta? get meta => _meta;
  Account? get account => _account;
  bool get loading => _loading;
  bool get loadingMore => _loadingMore;
  String? get error => _error;
  bool get hasMore => _meta?.hasMore ?? false;
  String? get type => _type;
  String? get dateFrom => _dateFrom;
  String? get dateTo => _dateTo;

  Future<void> load() => _load();

  Future<void> refresh() => _load();

  Future<void> applyFilters({String? type, String? dateFrom, String? dateTo}) {
    _type = type;
    _dateFrom = dateFrom;
    _dateTo = dateTo;

    return _load();
  }

  Future<void> loadMore() async {
    if (_loadingMore || !hasMore) {
      return;
    }

    _loadingMore = true;
    notifyListeners();

    try {
      final page = await _service.mutasi(
        type: _type,
        dateFrom: _dateFrom,
        dateTo: _dateTo,
        page: (_meta?.currentPage ?? 1) + 1,
      );

      _transactions.addAll(page.transactions);
      _meta = page.meta;
      _account = page.account ?? _account;
    } on ApiException catch (error) {
      _error = error.message;
    } catch (_) {
      _error = 'Gagal memuat data berikutnya.';
    } finally {
      _loadingMore = false;
      notifyListeners();
    }
  }

  Future<void> _load() async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final page = await _service.mutasi(
        type: _type,
        dateFrom: _dateFrom,
        dateTo: _dateTo,
        page: 1,
      );

      _transactions
        ..clear()
        ..addAll(page.transactions);
      _meta = page.meta;
      _account = page.account;
    } on ApiException catch (error) {
      _error = error.message;
    } catch (_) {
      _error = 'Gagal memuat mutasi. Silakan coba lagi.';
    } finally {
      _loading = false;
      notifyListeners();
    }
  }
}
