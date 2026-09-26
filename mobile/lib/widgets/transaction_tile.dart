import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/transaction.dart';

class TransactionTile extends StatelessWidget {
  const TransactionTile({super.key, required this.transaction, this.onTap});

  final Transaction transaction;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final incoming = transaction.isIncoming;
    final color = incoming ? AppColors.income : AppColors.expense;
    final sign = incoming ? '+' : '-';

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFFE6EBE8)),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(_icon(), color: color, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _title(),
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 14.5,
                      color: Color(0xFF1F2A24),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    Formatters.dateTime(transaction.transactionAt),
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '$sign${Formatters.rupiah(transaction.amount)}',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 14,
                    color: color,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  'Saldo ${Formatters.rupiah(transaction.balanceAfter)}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.muted,
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
    if (transaction.type == 'transfer' &&
        transaction.counterpart != null) {
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
