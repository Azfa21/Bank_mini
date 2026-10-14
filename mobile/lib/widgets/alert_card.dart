import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';

/// Makna kartu pesan. Empat nada ini persis yang dipakai web: brand untuk
/// konfirmasi/info, merah untuk galat, amber untuk peringatan yang butuh
/// tindakan siswa, olive untuk sukses pada alur pemulihan kredensial.
enum AlertTone { brand, error, warning, olive }

/// Kartu pesan sebaris. Menggantikan `SnackBar` mengambang untuk pesan yang
/// perlu tetap terbaca (misalnya "kode sudah dikirim" atau "rekening belum
/// aktif"), karena pesan sementara mudah terlewat.
class AlertCard extends StatelessWidget {
  const AlertCard({
    super.key,
    required this.message,
    this.tone = AlertTone.brand,
    this.title,
    this.icon,
  });

  final String message;
  final AlertTone tone;
  final String? title;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final (background, border, foreground, defaultIcon) = switch (tone) {
      AlertTone.brand => (
        AppColors.brand50,
        AppColors.brand200,
        AppColors.brand900,
        Icons.check_circle_outline_rounded,
      ),
      AlertTone.error => (
        AppColors.red50,
        AppColors.red200,
        AppColors.red800,
        Icons.error_outline_rounded,
      ),
      AlertTone.warning => (
        AppColors.amber50,
        AppColors.amber200,
        AppColors.amber900,
        Icons.info_outline_rounded,
      ),
      AlertTone.olive => (
        AppColors.olive50,
        AppColors.olive200,
        AppColors.olive700,
        Icons.mark_email_read_outlined,
      ),
    };

    return Semantics(
      liveRegion: true,
      container: true,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: background,
          border: Border.all(color: border),
          borderRadius: AppRadius.cardAll,
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon ?? defaultIcon, size: 20, color: foreground),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (title != null) ...[
                    Text(
                      title!,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: foreground,
                      ),
                    ),
                    const SizedBox(height: 2),
                  ],
                  Text(
                    message,
                    style: TextStyle(fontSize: 14, color: foreground),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
