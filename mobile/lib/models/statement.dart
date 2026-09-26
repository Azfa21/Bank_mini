import 'account.dart';
import 'transaction.dart';

class StatementSummary {
  const StatementSummary({
    required this.totalDeposit,
    required this.totalWithdrawal,
    required this.totalTransferOut,
    required this.totalTransferIn,
  });

  final int totalDeposit;
  final int totalWithdrawal;
  final int totalTransferOut;
  final int totalTransferIn;

  factory StatementSummary.fromJson(Map<String, dynamic> json) {
    return StatementSummary(
      totalDeposit: (json['total_deposit'] as num?)?.toInt() ?? 0,
      totalWithdrawal: (json['total_withdrawal'] as num?)?.toInt() ?? 0,
      totalTransferOut: (json['total_transfer_out'] as num?)?.toInt() ?? 0,
      totalTransferIn: (json['total_transfer_in'] as num?)?.toInt() ?? 0,
    );
  }
}

class StatementData {
  const StatementData({
    required this.account,
    required this.fullName,
    required this.studentNumber,
    this.className,
    required this.dateFrom,
    required this.dateTo,
    required this.verificationCode,
    required this.summary,
    required this.transactions,
  });

  final Account account;
  final String fullName;
  final String studentNumber;
  final String? className;
  final String dateFrom;
  final String dateTo;
  final String verificationCode;
  final StatementSummary summary;
  final List<Transaction> transactions;

  factory StatementData.fromJson(Map<String, dynamic> json) {
    final customer = json['customer'] as Map<String, dynamic>? ?? const {};
    final transactions = (json['transactions'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(Transaction.fromJson)
        .toList();

    return StatementData(
      account: Account.fromJson(json['account'] as Map<String, dynamic>),
      fullName: customer['full_name'] as String? ?? '',
      studentNumber: customer['student_number'] as String? ?? '',
      className: customer['class_name'] as String?,
      dateFrom: json['date_from'] as String? ?? '',
      dateTo: json['date_to'] as String? ?? '',
      verificationCode: json['verification_code'] as String? ?? '',
      summary: StatementSummary.fromJson(
        json['summary'] as Map<String, dynamic>? ?? const {},
      ),
      transactions: transactions,
    );
  }
}
