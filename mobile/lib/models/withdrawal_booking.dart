class WithdrawalBooking {
  const WithdrawalBooking({
    required this.id,
    required this.withdrawalDate,
    required this.amount,
    required this.status,
    required this.statusLabel,
    this.notes,
    this.decisionNote,
  });

  final int id;
  final DateTime withdrawalDate;
  final int amount;
  final String status;
  final String statusLabel;
  final String? notes;
  final String? decisionNote;

  factory WithdrawalBooking.fromJson(Map<String, dynamic> json) {
    return WithdrawalBooking(
      id: (json['id'] as num).toInt(),
      withdrawalDate: DateTime.parse(json['withdrawal_date'] as String),
      amount: (json['amount'] as num).toInt(),
      status: json['status'] as String? ?? 'pending',
      statusLabel: json['status_label'] as String? ?? 'Menunggu persetujuan',
      notes: json['notes'] as String?,
      decisionNote: json['decision_note'] as String?,
    );
  }
}

class WithdrawalBookingOverview {
  const WithdrawalBookingOverview({
    required this.dailyLimit,
    required this.todayWithdrawn,
    required this.todayLimit,
    required this.todayRemaining,
    required this.bookings,
  });

  final int dailyLimit;
  final int todayWithdrawn;
  final int todayLimit;
  final int todayRemaining;
  final List<WithdrawalBooking> bookings;

  factory WithdrawalBookingOverview.fromJson(Map<String, dynamic> json) {
    final rows = json['bookings'] as List<dynamic>? ?? const [];

    return WithdrawalBookingOverview(
      dailyLimit: (json['daily_limit'] as num?)?.toInt() ?? 1000000,
      todayWithdrawn: (json['today_withdrawn'] as num?)?.toInt() ?? 0,
      todayLimit: (json['today_limit'] as num?)?.toInt() ?? 1000000,
      todayRemaining: (json['today_remaining'] as num?)?.toInt() ?? 1000000,
      bookings: rows
          .whereType<Map<String, dynamic>>()
          .map(WithdrawalBooking.fromJson)
          .toList(),
    );
  }
}
