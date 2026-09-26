import 'account.dart';

class TransactionCounterpart {
  const TransactionCounterpart({required this.accountNumber, this.name});

  final String accountNumber;
  final String? name;

  factory TransactionCounterpart.fromJson(Map<String, dynamic> json) {
    return TransactionCounterpart(
      accountNumber: json['account_number'] as String? ?? '',
      name: json['name'] as String?,
    );
  }
}

class Transaction {
  const Transaction({
    required this.id,
    this.receiptNumber,
    this.slipNumber,
    required this.type,
    required this.typeLabel,
    required this.direction,
    required this.amount,
    required this.adminFee,
    required this.balanceBefore,
    required this.balanceAfter,
    required this.status,
    required this.isReversal,
    this.reversalReason,
    this.notes,
    this.counterpart,
    this.transactionAt,
  });

  final int id;
  final String? receiptNumber;
  final String? slipNumber;
  final String type;
  final String typeLabel;
  final String direction;
  final int amount;
  final int adminFee;
  final int balanceBefore;
  final int balanceAfter;
  final String status;
  final bool isReversal;
  final String? reversalReason;
  final String? notes;
  final TransactionCounterpart? counterpart;
  final DateTime? transactionAt;

  bool get isIncoming => direction == 'in';

  factory Transaction.fromJson(Map<String, dynamic> json) {
    final counterpart = json['counterpart'];

    return Transaction(
      id: (json['id'] as num).toInt(),
      receiptNumber: json['receipt_number'] as String?,
      slipNumber: json['slip_number'] as String?,
      type: json['type'] as String? ?? '',
      typeLabel: json['type_label'] as String? ?? '',
      direction: json['direction'] as String? ?? 'out',
      amount: (json['amount'] as num?)?.toInt() ?? 0,
      adminFee: (json['admin_fee'] as num?)?.toInt() ?? 0,
      balanceBefore: (json['balance_before'] as num?)?.toInt() ?? 0,
      balanceAfter: (json['balance_after'] as num?)?.toInt() ?? 0,
      status: json['status'] as String? ?? '',
      isReversal: json['is_reversal'] as bool? ?? false,
      reversalReason: json['reversal_reason'] as String?,
      notes: json['notes'] as String?,
      counterpart: counterpart is Map<String, dynamic>
          ? TransactionCounterpart.fromJson(counterpart)
          : null,
      transactionAt:
          DateTime.tryParse(json['transaction_at'] as String? ?? ''),
    );
  }
}

class TransactionMeta {
  const TransactionMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
    this.from,
    this.to,
  });

  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;
  final int? from;
  final int? to;

  bool get hasMore => currentPage < lastPage;

  factory TransactionMeta.fromJson(Map<String, dynamic> json) {
    return TransactionMeta(
      currentPage: (json['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (json['last_page'] as num?)?.toInt() ?? 1,
      perPage: (json['per_page'] as num?)?.toInt() ?? 15,
      total: (json['total'] as num?)?.toInt() ?? 0,
      from: (json['from'] as num?)?.toInt(),
      to: (json['to'] as num?)?.toInt(),
    );
  }
}

class TransactionPage {
  const TransactionPage({
    required this.transactions,
    required this.meta,
    this.account,
  });

  final List<Transaction> transactions;
  final TransactionMeta meta;
  final Account? account;

  factory TransactionPage.fromJson(Map<String, dynamic> json) {
    final account = json['account'];
    final transactions = (json['transactions'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(Transaction.fromJson)
        .toList();

    return TransactionPage(
      transactions: transactions,
      meta: TransactionMeta.fromJson(
        json['meta'] as Map<String, dynamic>? ?? const {},
      ),
      account: account is Map<String, dynamic> ? Account.fromJson(account) : null,
    );
  }
}
