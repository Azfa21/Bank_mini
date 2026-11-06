class AppConfig {
  const AppConfig._();

  /// Alamat API. Di emulator Android, `10.0.2.2` adalah alias host loopback.
  /// Ganti saat build rilis:
  /// `--dart-define=API_BASE_URL=https://bankmini.sekolah.example/api`
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://bankmini-skansaba.my.id/api',
  );

  /// Nama aplikasi di layar pembuka dan judul jendela.
  static const String appName = 'Bank Mini Nasabah';

  /// Nama bank untuk layar sebelum masuk.
  ///
  /// Web membacanya dari `SystemConfig::value('bank_name')` di server. Endpoint
  /// konfigurasi hanya bisa diakses setelah login, jadi sebelum login nama ini
  /// memakai nilai default yang sama dengan server. Setelah masuk, halaman
  /// memakai `bank.name` dari respons API.
  static const String bankName = 'Bank Mini Sekolah';
}
