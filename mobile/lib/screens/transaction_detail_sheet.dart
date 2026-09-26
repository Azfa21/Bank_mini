import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/transaction.dart';

Future<void> showTransactionDetail(
  BuildContext context,
  Transaction transaction,
) {
  return showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.white,
    showDragHandle: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
    ),
    builder: (context) => _TransactionDetail(transaction: transaction),
  );
}

class _TransactionDetail extends StatelessWidget {
  const _TransactionDetail({required this.transaction});

  final Transaction transaction;

  @override
  Widget build(BuildContext context) {
    final incoming = transaction.isIncoming;
    final color = incoming ? AppColors.income : AppColors.expense;

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
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF1F2A24),
                ),
              ),
            ),
            const SizedBox(height: 6),
            Center(
              child: Text(
                '${incoming ? '+' : '-'}${Formatters.rupiah(transaction.amount)}',
                style: TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.w800,
                  color: color,
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
                value: transaction.counterpart!.name ??
                    transaction.counterpart!.accountNumber,
              ),
            if (transaction.adminFee > 0)
              _Row(
                label: 'Biaya Admin',
                value: Formatters.rupiah(transaction.adminFee),
              ),
            _Row(
              label: 'Saldo Sebelum',
              value: Formatters.rupiah(transaction.balanceBefore),
            ),
            _Row(
              label: 'Saldo Sesudah',
              value: Formatters.rupiah(transaction.balanceAfter),
            ),
            _Row(label: 'Status', value: _statusLabel(transaction.status)),
            if (transaction.notes != null && transaction.notes!.isNotEmpty)
              _Row(label: 'Catatan', value: transaction.notes!),
            if (transaction.isReversal && transaction.reversalReason != null)
              _Row(label: 'Alasan Koreksi', value: transaction.reversalReason!),
          ],
        ),
      ),
    );
  }

  String _statusLabel(String status) {
    return switch (status) {
      'success' => 'Berhasil',
      'reversed' => 'Dikoreksi',
      'rejected' => 'Ditolak',
      'draft' => 'Draf',
      _ => status,
    };
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 7),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(color: AppColors.muted, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: Color(0xFF1F2A24),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
