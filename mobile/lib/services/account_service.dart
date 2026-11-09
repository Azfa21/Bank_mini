import '../core/api/api_client.dart';
import '../models/card_info.dart';
import '../models/dashboard_data.dart';
import '../models/statement.dart';
import '../models/transaction.dart';
import '../models/withdrawal_booking.dart';

class AccountService {
  AccountService(this._api);

  final ApiClient _api;

  Future<DashboardData> dashboard() async {
    final response = await _api.getJson('/mobile/dashboard');

    return DashboardData.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<CardInfo> card() async {
    final response = await _api.getJson('/mobile/card');

    return CardInfo.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<TransactionPage> mutasi({
    String? type,
    String? dateFrom,
    String? dateTo,
    int page = 1,
    int perPage = 15,
  }) async {
    final response = await _api.getJson(
      '/mobile/mutasi',
      query: {
        'page': page,
        'per_page': perPage,
        if (type != null && type.isNotEmpty) 'type': type,
        if (dateFrom != null && dateFrom.isNotEmpty) 'date_from': dateFrom,
        if (dateTo != null && dateTo.isNotEmpty) 'date_to': dateTo,
      },
    );

    return TransactionPage.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<StatementData> statement({
    required String dateFrom,
    required String dateTo,
  }) async {
    final response = await _api.getJson(
      '/mobile/statement',
      query: {'date_from': dateFrom, 'date_to': dateTo},
    );

    return StatementData.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<WithdrawalBookingOverview> withdrawalBookings() async {
    final response = await _api.getJson('/mobile/withdrawal-bookings');

    return WithdrawalBookingOverview.fromJson(
      response['data'] as Map<String, dynamic>,
    );
  }

  Future<WithdrawalBooking> requestWithdrawalBooking({
    required String withdrawalDate,
    required int amount,
    String? notes,
  }) async {
    final response = await _api.postJson(
      '/mobile/withdrawal-bookings',
      data: {
        'withdrawal_date': withdrawalDate,
        'amount': amount,
        if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
      },
    );

    return WithdrawalBooking.fromJson(response['data'] as Map<String, dynamic>);
  }
}
