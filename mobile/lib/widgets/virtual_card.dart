import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';

/// Kartu nasabah virtual, padanan `partials.virtual-card` di web: permukaan
/// brand-900, label brand-100, dan QR putih di pojok kanan atas.
///
/// Nilai QR memakai aturan fallback yang sama dengan web: `qr_code_value`
/// bila ada, kalau tidak `BMS:` diikuti nomor rekening. Saldo bersifat
/// opsional karena endpoint kartu mobile tidak mengirimnya.
class VirtualCard extends StatelessWidget {
  const VirtualCard({
    super.key,
    required this.bankName,
    required this.fullName,
    required this.studentNumber,
    this.className,
    this.accountNumber,
    this.qrCodeValue,
    this.balance,
  });

  final String bankName;
  final String fullName;
  final String studentNumber;
  final String? className;
  final String? accountNumber;
  final String? qrCodeValue;
  final int? balance;

  @override
  Widget build(BuildContext context) {
    final qrValue =
        qrCodeValue ??
        (accountNumber == null || accountNumber!.isEmpty
            ? null
            : 'BMS:$accountNumber');

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: AppColors.brand900,
        borderRadius: AppRadius.cardAll,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(
                            color: AppColors.white.withValues(alpha: 0.15),
                            borderRadius: AppRadius.navAll,
                          ),
                          padding: const EdgeInsets.all(4),
                          clipBehavior: Clip.antiAlias,
                          child: SvgPicture.asset(
                            'assets/images/bank-mini-logo.svg',
                            width: 20,
                            height: 20,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            bankName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: AppText.body.copyWith(
                              color: AppColors.white,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Kartu tabungan nasabah',
                      style: AppText.micro.copyWith(color: AppColors.brand100),
                    ),
                  ],
                ),
              ),
              if (qrValue != null) ...[
                const SizedBox(width: 16),
                _expandableQr(context, qrValue),
              ],
            ],
          ),
          const SizedBox(height: 24),
          Text(
            'Pemegang rekening',
            style: AppText.micro.copyWith(color: AppColors.brand100),
          ),
          const SizedBox(height: 2),
          Text(fullName, style: AppText.cardHolder),
          const SizedBox(height: 16),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _label('No. rekening'),
                    Text(
                      accountNumber == null || accountNumber!.isEmpty
                          ? '-'
                          : accountNumber!,
                      style: AppText.tnum(
                        const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.white,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    _label('Kelas dan no. siswa'),
                    Text(
                      '${className ?? '-'} / $studentNumber',
                      style: AppText.tnum(
                        const TextStyle(fontSize: 14, color: AppColors.white),
                      ),
                    ),
                  ],
                ),
              ),
              if (balance != null)
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    _label('Saldo'),
                    Text(
                      Formatters.rupiah(balance!),
                      style: AppText.tnum(
                        const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: AppColors.white,
                        ),
                      ),
                    ),
                  ],
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _label(String text) {
    return Text(text, style: AppText.micro.copyWith(color: AppColors.brand100));
  }

  Widget _expandableQr(BuildContext context, String qrValue) {
    void showExpandedQr() {
      showDialog<void>(
        context: context,
        builder: (dialogContext) {
          final screen = MediaQuery.sizeOf(dialogContext);
          final qrSize = (screen.width - 80).clamp(120.0, 320.0).toDouble();

          return Dialog(
            insetPadding: const EdgeInsets.symmetric(
              horizontal: 20,
              vertical: 20,
            ),
            constraints: const BoxConstraints(maxWidth: 400),
            backgroundColor: AppColors.paper,
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          'Kode QR rekening',
                          style: AppText.sectionTitle,
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.of(dialogContext).pop(),
                        tooltip: 'Tutup kode QR',
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                  Text(
                    fullName,
                    textAlign: TextAlign.center,
                    style: AppText.action,
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.white,
                      borderRadius: AppRadius.cardAll,
                    ),
                    child: Semantics(
                      label: 'Kode QR rekening milik $fullName',
                      child: QrImageView(
                        data: qrValue,
                        version: QrVersions.auto,
                        size: qrSize,
                        gapless: true,
                        backgroundColor: AppColors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Tunjukkan kode ini kepada teller untuk dipindai.',
                    textAlign: TextAlign.center,
                    style: AppText.body.copyWith(color: AppColors.stone600),
                  ),
                ],
              ),
            ),
          );
        },
      );
    }

    return Semantics(
      button: true,
      label: 'Perbesar kode QR milik $fullName',
      onTap: showExpandedQr,
      child: ExcludeSemantics(
        child: Material(
          color: AppColors.white,
          borderRadius: AppRadius.controlAll,
          child: InkWell(
            onTap: showExpandedQr,
            borderRadius: AppRadius.controlAll,
            child: Padding(
              padding: const EdgeInsets.all(6),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  QrImageView(
                    data: qrValue,
                    version: QrVersions.auto,
                    size: 88,
                    gapless: true,
                    backgroundColor: AppColors.white,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.open_in_full,
                        size: 12,
                        color: AppColors.brand800,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'Perbesar',
                        style: AppText.caption.copyWith(
                          color: AppColors.brand800,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
