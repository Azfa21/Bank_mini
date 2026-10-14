import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/transaction.dart';
import '../widgets/status_pill.dart';

Future<void> showTransactionDetail(
  BuildContext context,
  Transaction transaction,
) {
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: AppColors.white,
    showDragHandle: true,
    shape: const RoundedRectangleBorder(borderRadius: AppRadius.sheetTop),
    builder: (context) => _TransactionDetail(transaction: transaction),
  );
}

class _TransactionDetail extends StatelessWidget {
  const _TransactionDetail({required this.transaction});

  final Transaction transaction;

  @override
  Widget build(BuildContext context) {
    final incoming = transaction.isIncoming;
    final color = incoming ? AppColors.brand800 : AppColors.red700;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Text(
                transaction.typeLabel,
                style: AppText.title.copyWith(fontSize: 16),
              ),
            ),
            const SizedBox(height: 6),
            Center(
              child: Text(
                '${incoming ? '+' : '-'}${Formatters.rupiah(transaction.amount)}',
                style: AppText.tnum(
                  TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 18),
            const Divider(height: 1),
            const SizedBox(height: 14),
            _Row(
              label: 'Tanggal',
              value: Formatters.dateTime(transaction.transactionAt),
            ),
            if (transaction.receiptNumber != null)
              _Row(label: 'No. Transaksi', value: transaction.receiptNumber!),
            if (transaction.slipNumber != null)
              _Row(label: 'No. Slip', value: transaction.slipNumber!),
            if (transaction.counterpart != null)
              _Row(
                label: incoming ? 'Dari' : 'Ke',
                value:
                    transaction.counterpart!.name ??
                    transaction.counterpart!.accountNumber,
              ),
            _Row(
              label: 'Saldo Sebelum',
              value: Formatters.rupiah(transaction.balanceBefore),
            ),
            _Row(
              label: 'Saldo Sesudah',
              value: Formatters.rupiah(transaction.balanceAfter),
            ),
            _Row(
              label: 'Status',
              valueWidget: TransactionStatusPill(status: transaction.status),
            ),
            if (transaction.notes != null && transaction.notes!.isNotEmpty)
              _Row(label: 'Catatan', value: transaction.notes!),
            if (transaction.isReversal && transaction.reversalReason != null)
              _Row(label: 'Alasan Koreksi', value: transaction.reversalReason!),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, this.value, this.valueWidget});

  final String label;
  final String? value;
  final Widget? valueWidget;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 120, child: Text(label, style: AppText.meta)),
          Expanded(
            child:
                valueWidget ??
                Text(
                  value ?? '-',
                  textAlign: TextAlign.right,
                  style: AppText.tnum(AppText.metaStrong),
                ),
          ),
        ],
      ),
    );
  }
}
