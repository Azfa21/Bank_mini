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
        if (deviceName != null && deviceName.isNotEmpty)
          'device_name': deviceName,
      },
    );

    final data = response['data'] as Map<String, dynamic>;
    final account = data['account'];

    return LoginResult(
      token: data['token'] as String,
      expiresAt: DateTime.tryParse(data['expires_at'] as String? ?? ''),
      customer: Customer.fromJson(data['customer'] as Map<String, dynamic>),
      account: account is Map<String, dynamic>
          ? Account.fromJson(account)
          : null,
    );
  }

  Future<SessionData> me() async {
    final response = await _api.getJson('/mobile/me');
    final data = response['data'] as Map<String, dynamic>;
    final account = data['account'];

    return SessionData(
      customer: Customer.fromJson(data['customer'] as Map<String, dynamic>),
      account: account is Map<String, dynamic>
          ? Account.fromJson(account)
          : null,
    );
  }

  Future<void> logout() async {
    await _api.postJson('/mobile/logout');
  }

  /// Mengembalikan alamat email terdaftar yang menerima kode verifikasi,
  /// atau null bila tidak ada. Nomor siswa yang belum terdaftar sebagai
  /// penabung ditolak server lewat [ApiException], bukan lewat nilai null.
  Future<String?> requestResetOtp({required String studentNumber}) async {
    final response = await _api.postJson(
      '/mobile/forgot-pin/request-otp',
      data: {'account_type': 'nasabah', 'identifier': studentNumber},
    );

    final email = response['email'];
    if (email is String && email.isNotEmpty) {
      return email;
    }
    return null;
  }

  Future<void> resetPin({
    required String studentNumber,
    required String otp,
    required String pin,
  }) async {
    await _api.postJson(
      '/mobile/forgot-pin/reset',
      data: {
        'account_type': 'nasabah',
        'identifier': studentNumber,
        'otp': otp,
        'pin': pin,
        'pin_confirmation': pin,
      },
    );
  }
}
