class Bank {
  const Bank({required this.name});

  final String name;

  factory Bank.fromJson(Map<String, dynamic> json) {
    return Bank(
      name: json['name'] as String? ?? 'Bank Mini Sekolah',
    );
  }
}
