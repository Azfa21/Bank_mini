class CardInfo {
  const CardInfo({
    required this.bankName,
    this.accountNumber,
    required this.studentNumber,
    required this.fullName,
    this.className,
    this.qrCodeValue,
    this.status,
  });

  final String bankName;
  final String? accountNumber;
  final String studentNumber;
  final String fullName;
  final String? className;
  final String? qrCodeValue;
  final String? status;

  factory CardInfo.fromJson(Map<String, dynamic> json) {
    return CardInfo(
      bankName: json['bank_name'] as String? ?? 'Bank Mini Sekolah',
      accountNumber: json['account_number'] as String?,
      studentNumber: json['student_number'] as String? ?? '',
      fullName: json['full_name'] as String? ?? '',
      className: json['class_name'] as String?,
      qrCodeValue: json['qr_code_value'] as String?,
      status: json['status'] as String?,
    );
  }
}
