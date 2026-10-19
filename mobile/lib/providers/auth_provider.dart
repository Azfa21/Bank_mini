import 'package:flutter/foundation.dart';

import '../core/api/api_exception.dart';
import '../core/storage/token_storage.dart';
import '../models/account.dart';
import '../models/customer.dart';
import '../services/auth_service.dart';

enum AuthStatus { unknown, unauthenticated, authenticated }

class AuthProvider extends ChangeNotifier {
  AuthProvider(this._authService, this._tokenStorage);

  final AuthService _authService;
  final TokenStorage _tokenStorage;

  AuthStatus _status = AuthStatus.unknown;
  Customer? _customer;
  Account? _account;
  bool _submitting = false;

  AuthStatus get status => _status;
  Customer? get customer => _customer;
  Account? get account => _account;
  bool get isSubmitting => _submitting;
  bool get isAuthenticated => _status == AuthStatus.authenticated;

  Future<void> bootstrap() async {
    final token = await _tokenStorage.read();

    if (token == null || token.isEmpty) {
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    try {
      final session = await _authService.me();
      _customer = session.customer;
      _account = session.account;
      _status = AuthStatus.authenticated;
    } catch (_) {
      await _tokenStorage.clear();
      _status = AuthStatus.unauthenticated;
    }

    notifyListeners();
  }

  Future<void> login({
    required String studentNumber,
    required String pin,
  }) async {
    _submitting = true;
    notifyListeners();

    try {
      final result = await _authService.login(
        studentNumber: studentNumber,
        pin: pin,
        deviceName: 'android',
      );

      await _tokenStorage.write(result.token);
      _customer = result.customer;
      _account = result.account;
      _status = AuthStatus.authenticated;
    } finally {
      _submitting = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    try {
      await _authService.logout();
    } on ApiException {
      // Abaikan kegagalan jaringan saat logout; sesi lokal tetap dihapus.
    }

    await _clearSession();
  }

  Future<String?> requestResetOtp({required String studentNumber}) async {
    return _authService.requestResetOtp(studentNumber: studentNumber);
  }

  Future<void> resetPin({
    required String studentNumber,
    required String otp,
    required String pin,
  }) async {
    await _authService.resetPin(
      studentNumber: studentNumber,
      otp: otp,
      pin: pin,
    );
  }

  Future<void> handleUnauthorized() async {
    if (_status == AuthStatus.unauthenticated) {
      return;
    }

    await _clearSession();
  }

  void refreshAccount(Account? account) {
    _account = account;
    notifyListeners();
  }

  Future<void> _clearSession() async {
    await _tokenStorage.clear();
    _customer = null;
    _account = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }
}
