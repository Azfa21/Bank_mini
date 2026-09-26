class Bank {
  const Bank({required this.name, required this.operationalHours});

  final String name;
  final String operationalHours;

  factory Bank.fromJson(Map<String, dynamic> json) {
    return Bank(
      name: json['name'] as String? ?? 'Bank Mini Sekolah',
      operationalHours:
          json['operational_hours'] as String? ?? '07.30 - 14.00 WIB',
    );
  }
}
