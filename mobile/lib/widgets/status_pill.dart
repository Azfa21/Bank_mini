import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

/// Pill status rekening, meniru resep web: `brand-50/brand-200/brand-900` untuk
/// aktif, `red-50/red-200/red-800` untuk tidak aktif, dengan glyph ● atau ■.
///
/// Glyph adalah sinyal tambahan yang sengaja dibuat `aria-hidden` di web, jadi
/// di sini pun ia tidak ikut dibaca pembaca layar.
class StatusPill extends StatelessWidget {
  const StatusPill({super.key, required this.isActive, required this.label});

  final bool isActive;
  final String label;

  @override
  Widget build(BuildContext context) {
    final background = isActive ? AppColors.brand50 : AppColors.red50;
    final border = isActive ? AppColors.brand200 : AppColors.red200;
    final foreground = isActive ? AppColors.brand900 : AppColors.red800;

    return Semantics(
      container: true,
      label: 'Status rekening: $label',
      child: ExcludeSemantics(
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: background,
            border: Border.all(color: border),
            borderRadius: BorderRadius.circular(999),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                isActive ? '●' : '■',
                style: TextStyle(color: foreground, fontSize: 11, height: 1),
              ),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: foreground,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Pill status untuk transaksi (Berhasil, Dikoreksi, Ditolak, Draf).
///
/// Warnanya mengikuti makna: berhasil pakai olive (positif), ditolak merah,
/// dikoreksi amber, draf netral. Ini penanda keadaan nyata, bukan badge hiasan.
class TransactionStatusPill extends StatelessWidget {
  const TransactionStatusPill({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, background, border, foreground) = switch (status) {
      'success' => (
        'Berhasil',
        AppColors.olive50,
        AppColors.olive200,
        AppColors.olive700,
      ),
      'reversed' => (
        'Dikoreksi',
        AppColors.amber50,
        AppColors.amber200,
        AppColors.amber900,
      ),
      'rejected' => (
        'Ditolak',
        AppColors.red50,
        AppColors.red200,
        AppColors.red800,
      ),
      _ => ('Draf', AppColors.stone100, AppColors.stone200, AppColors.stone700),
    };

    return ExcludeSemantics(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: background,
          border: Border.all(color: border),
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: foreground,
          ),
        ),
      ),
    );
  }
}
