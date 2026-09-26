class Account {
  const Account({
    required this.id,
    required this.accountNumber,
    this.academicYear,
    required this.openingBalance,
    required this.balance,
    required this.minimumBalance,
    required this.status,
    this.openedAt,
  });

  final int id;
  final String accountNumber;
  final String? academicYear;
  final int openingBalance;
  final int balance;
  final int minimumBalance;
  final String status;
  final DateTime? openedAt;

  factory Account.fromJson(Map<String, dynamic> json) {
    return Account(
      id: (json['id'] as num).toInt(),
      accountNumber: json['account_number'] as String? ?? '',
      academicYear: json['academic_year'] as String?,
      openingBalance: (json['opening_balance'] as num?)?.toInt() ?? 0,
      balance: (json['balance'] as num?)?.toInt() ?? 0,
      minimumBalance: (json['minimum_balance'] as num?)?.toInt() ?? 0,
      status: json['status'] as String? ?? 'active',
      openedAt: DateTime.tryParse(json['opened_at'] as String? ?? ''),
    );
  }
}
