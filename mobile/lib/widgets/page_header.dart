import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';

/// Kepala halaman yang sama dipakai ketiga halaman nasabah di web:
/// eyebrow pendek, judul, lalu satu baris penjelas. Pola berulang ini yang
/// membuat halaman terbaca sebagai satu sistem, bukan tiga tampilan terpisah.
class PageHeader extends StatelessWidget {
  const PageHeader({
    super.key,
    required this.eyebrow,
    required this.title,
    this.subtitle,
    this.trailing,
  });

  final String eyebrow;
  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final text = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(eyebrow, style: AppText.eyebrow),
        const SizedBox(height: 4),
        Text(title, style: AppText.title),
        if (subtitle != null) ...[
          const SizedBox(height: 4),
          Text(
            subtitle!,
            style: AppText.body.copyWith(color: AppColors.stone600),
          ),
        ],
      ],
    );

    if (trailing == null) {
      return text;
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: text),
        trailing!,
      ],
    );
  }
}
