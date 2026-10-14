import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/transaction.dart';
import 'status_pill.dart';

/// Satu baris mutasi. Warna uang mengikuti aturan web: kredit memakai terakota
/// (brand), debit memakai merah, dan selalu disertai tanda + / - supaya arah
/// uang tidak bergantung pada warna saja.
class TransactionTile extends StatelessWidget {
  const TransactionTile({super.key, required this.transaction, this.onTap});

  final Transaction transaction;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final incoming = transaction.isIncoming;
    final amountColor = incoming ? AppColors.brand800 : AppColors.red700;
    final sign = incoming ? '+' : '-';

    return InkWell(
      onTap: onTap,
      borderRadius: AppRadius.cardAll,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: AppRadius.cardAll,
          border: Border.all(color: AppColors.stone200),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: amountColor.withValues(alpha: 0.10),
                borderRadius: AppRadius.controlAll,
              ),
              child: Icon(_icon(), color: amountColor, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _title(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: AppText.body.copyWith(
                      fontWeight: FontWeight.w600,
                      color: AppColors.ink,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Text(
                        Formatters.dateTime(transaction.transactionAt),
                        style: AppText.micro,
                      ),
                      if (transaction.status != 'success') ...[
                        const SizedBox(width: 8),
                        TransactionStatusPill(status: transaction.status),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '$sign${Formatters.rupiah(transaction.amount)}',
                  style: AppText.tnum(
                    TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: amountColor,
                    ),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Saldo ${Formatters.rupiah(transaction.balanceAfter)}',
                  style: AppText.tnum(
                    const TextStyle(fontSize: 11, color: AppColors.stone500),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _title() {
    if (transaction.type == 'transfer' && transaction.counterpart != null) {
      final name = transaction.counterpart!.name;
      final number = transaction.counterpart!.accountNumber;
      final target = name != null && name.isNotEmpty ? name : number;
      return transaction.isIncoming
          ? 'Transfer dari $target'
          : 'Transfer ke $target';
    }

    return transaction.typeLabel;
  }

  IconData _icon() {
    if (transaction.isReversal) {
      return Icons.undo_rounded;
    }

    return switch (transaction.type) {
      'deposit' => Icons.south_west_rounded,
      'withdrawal' => Icons.north_east_rounded,
      'transfer' => Icons.swap_horiz_rounded,
      _ => Icons.receipt_long_outlined,
    };
  }
}
