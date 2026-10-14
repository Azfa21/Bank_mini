import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';

/// Padanan `.ledger-rule` di web: garis tebal 3px di tepi atas satu kartu, pada
/// halaman itu satu-satunya kartu yang paling penting.
///
/// Ini motif identitas buku tabungan sekolah. Karena hanya muncul sekali per
/// halaman, garis itu benar-benar menandai hierarki, bukan hiasan.
///
/// Implementasi memakai strip di dalam `ClipRRect`, bukan `Border(top:)`,
/// supaya lengkung sudut atas mengikuti radius kartu persis seperti CSS.
class LedgerCard extends StatelessWidget {
  const LedgerCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(20),
    this.showRule = true,
  });

  final Widget child;

  /// Padding isi kartu. Web memakai `p-5 sm:p-6`, jadi 20 di layar kecil.
  final EdgeInsetsGeometry padding;

  /// Matikan untuk kartu biasa yang tetap memakai resep yang sama tanpa rule.
  final bool showRule;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: AppRadius.cardAll,
        border: showRule
            ? const Border(
                left: BorderSide(color: AppColors.stone200),
                right: BorderSide(color: AppColors.stone200),
                bottom: BorderSide(color: AppColors.stone200),
              )
            : Border.all(color: AppColors.stone200),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (showRule)
            const SizedBox(
              height: 3,
              child: ColoredBox(color: AppColors.brand800),
            ),
          Padding(padding: padding, child: child),
        ],
      ),
    );
  }
}
