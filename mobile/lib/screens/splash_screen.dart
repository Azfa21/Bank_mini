import 'package:flutter/material.dart';

import '../core/config/app_config.dart';
import '../core/theme/app_colors.dart';

/// Layar pembuka. Latar terakota penuh dengan logo kertas di tengah.
///
/// Spinner dipertahankan karena `AuthProvider.bootstrap()` benar-benar sedang
/// memeriksa token tersimpan; ini progres nyata, bukan animasi hiasan.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.brand800,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _LogoMark(size: 84),
            SizedBox(height: 20),
            Text(
              AppConfig.appName,
              style: TextStyle(
                color: AppColors.white,
                fontSize: 20,
                fontWeight: FontWeight.w700,
              ),
            ),
            SizedBox(height: 6),
            Text(
              'Tabungan siswa sekolah',
              style: TextStyle(color: AppColors.brand100, fontSize: 13),
            ),
            SizedBox(height: 28),
            SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(
                strokeWidth: 2.4,
                color: AppColors.brand100,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LogoMark extends StatelessWidget {
  const _LogoMark({required this.size});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(size * 0.28),
      ),
      child: Icon(
        Icons.account_balance_rounded,
        color: AppColors.brand800,
        size: size * 0.52,
      ),
    );
  }
}
