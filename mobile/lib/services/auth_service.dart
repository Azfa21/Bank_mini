import '../core/api/api_client.dart';
import '../models/account.dart';
import '../models/customer.dart';

class LoginResult {
  const LoginResult({
    required this.token,
    required this.customer,
    this.account,
    this.expiresAt,
  });

  final String token;
  final Customer customer;
  final Account? account;
  final DateTime? expiresAt;
}

class SessionData {
  const SessionData({required this.customer, this.account});

  final Customer customer;
  final Account? account;
}

class AuthService {
  AuthService(this._api);

  final ApiClient _api;

  Future<LoginResult> login({
    required String studentNumber,
    required String pin,
    String? deviceName,
  }) async {
    final response = await _api.postJson(
      '/mobile/login',
      data: {
        'student_number': studentNumber,
        'pin': pin,
        if (deviceName != null && deviceName.isNotEmpty) 'device_name': deviceName,
      },
    );

    final data = response['data'] as Map<String, dynamic>;
    final account = data['account'];

    return LoginResult(
      token: data['token'] as String,
      expiresAt: DateTime.tryParse(data['expires_at'] as String? ?? ''),
      customer: Customer.fromJson(data['customer'] as Map<String, dynamic>),
      account:
          account is Map<String, dynamic> ? Account.fromJson(account) : null,
    );
  }

  Future<SessionData> me() async {
    final response = await _api.getJson('/mobile/me');
    final data = response['data'] as Map<String, dynamic>;
    final account = data['account'];

    return SessionData(
      customer: Customer.fromJson(data['customer'] as Map<String, dynamic>),
      account:
          account is Map<String, dynamic> ? Account.fromJson(account) : null,
    );
  }

  Future<void> logout() async {
    await _api.postJson('/mobile/logout');
  }

  Future<void> requestResetOtp({required String email}) async {
    await _api.postJson(
      '/mobile/forgot-pin/request-otp',
      data: {
        'account_type': 'nasabah',
        'email': email,
      },
    );
  }

  Future<void> resetPin({
    required String email,
    required String otp,
    required String pin,
  }) async {
    await _api.postJson(
      '/mobile/forgot-pin/reset',
      data: {
        'account_type': 'nasabah',
        'email': email,
        'otp': otp,
        'pin': pin,
        'pin_confirmation': pin,
      },
    );
  }
}
