class Customer {
  const Customer({
    required this.id,
    required this.studentNumber,
    required this.fullName,
    this.className,
    this.phoneNumber,
    this.qrCodeValue,
    required this.status,
  });

  final int id;
  final String studentNumber;
  final String fullName;
  final String? className;
  final String? phoneNumber;
  final String? qrCodeValue;
  final String status;

  factory Customer.fromJson(Map<String, dynamic> json) {
    return Customer(
      id: (json['id'] as num).toInt(),
      studentNumber: json['student_number'] as String? ?? '',
      fullName: json['full_name'] as String? ?? '',
      className: json['class_name'] as String?,
      phoneNumber: json['phone_number'] as String?,
      qrCodeValue: json['qr_code_value'] as String?,
      status: json['status'] as String? ?? 'active',
    );
  }
}
