import 'account.dart';
import 'bank.dart';
import 'customer.dart';
import 'transaction.dart';

class DashboardData {
  const DashboardData({
    required this.customer,
    this.account,
    required this.bank,
    this.lastTransaction,
  });

  final Customer customer;
  final Account? account;
  final Bank bank;
  final Transaction? lastTransaction;

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    final account = json['account'];
    final last = json['last_transaction'];

    return DashboardData(
      customer: Customer.fromJson(json['customer'] as Map<String, dynamic>),
      account:
          account is Map<String, dynamic> ? Account.fromJson(account) : null,
      bank: Bank.fromJson(json['bank'] as Map<String, dynamic>? ?? const {}),
      lastTransaction:
          last is Map<String, dynamic> ? Transaction.fromJson(last) : null,
    );
  }
}
