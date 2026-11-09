import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/utils/formatters.dart';
import '../models/withdrawal_booking.dart';
import '../services/account_service.dart';
import '../widgets/alert_card.dart';
import '../widgets/ledger_card.dart';
import '../widgets/page_header.dart';
import '../widgets/state_views.dart';

class WithdrawalBookingScreen extends StatefulWidget {
  const WithdrawalBookingScreen({super.key});

  @override
  State<WithdrawalBookingScreen> createState() =>
      _WithdrawalBookingScreenState();
}

class _WithdrawalBookingScreenState extends State<WithdrawalBookingScreen> {
  static const int _dailyLimit = 1000000;

  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();

  late Future<WithdrawalBookingOverview> _overview;
  late DateTime _withdrawalDate;
  bool _submitting = false;
  String? _submitError;

  @override
  void initState() {
    super.initState();
    final tomorrow = DateTime.now().add(const Duration(days: 1));
    _withdrawalDate = DateTime(tomorrow.year, tomorrow.month, tomorrow.day);
    _overview = context.read<AccountService>().withdrawalBookings();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    final request = context.read<AccountService>().withdrawalBookings();
    setState(() => _overview = request);
    try {
      await request;
    } catch (_) {}
  }

  Future<void> _chooseDate() async {
    final now = DateTime.now();
    final tomorrow = DateTime(now.year, now.month, now.day + 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: _withdrawalDate.isBefore(tomorrow)
          ? tomorrow
          : _withdrawalDate,
      firstDate: tomorrow,
      lastDate: DateTime(2100),
      helpText: 'Pilih tanggal penarikan',
    );

    if (picked != null && mounted) {
      setState(() => _withdrawalDate = picked);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final amount = int.parse(_amountController.text.trim());
    setState(() {
      _submitting = true;
      _submitError = null;
    });

    try {
      await context.read<AccountService>().requestWithdrawalBooking(
        withdrawalDate: Formatters.apiDate(_withdrawalDate),
        amount: amount,
        notes: _notesController.text,
      );
      if (!mounted) return;
      _amountController.clear();
      _notesController.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Booking dikirim untuk persetujuan teller.'),
        ),
      );
      await _refresh();
    } on ApiException catch (error) {
      if (mounted) setState(() => _submitError = error.message);
    } catch (_) {
      if (mounted) {
        setState(
          () => _submitError =
              'Booking belum terkirim. Periksa koneksi lalu coba lagi.',
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.paper,
      appBar: AppBar(title: const Text('Booking penarikan')),
      body: SafeArea(
        child: RefreshIndicator(
          color: AppColors.brand800,
          onRefresh: _refresh,
          child: FutureBuilder<WithdrawalBookingOverview>(
            future: _overview,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting &&
                  !snapshot.hasData) {
                return const AppLoading(message: 'Memuat booking penarikan...');
              }

              if (snapshot.hasError && !snapshot.hasData) {
                final error = snapshot.error;
                return AppErrorView(
                  message: error is ApiException
                      ? error.message
                      : 'Data booking belum dapat dimuat. Periksa koneksi lalu coba lagi.',
                  onRetry: _refresh,
                );
              }

              final overview = snapshot.data;
              if (overview == null) {
                return const AppEmptyView(title: 'Data booking tidak tersedia');
              }

              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
                children: [
                  const PageHeader(
                    eyebrow: 'RENCANA PENARIKAN',
                    title: 'Booking penarikan',
                    subtitle:
                        'Ajukan lebih awal jika total penarikan sehari melebihi Rp 1.000.000.',
                  ),
                  const SizedBox(height: 16),
                  _dailySummary(overview),
                  const SizedBox(height: 16),
                  _bookingForm(overview),
                  const SizedBox(height: 24),
                  Text('Riwayat booking', style: AppText.sectionTitle),
                  const SizedBox(height: 10),
                  if (overview.bookings.isEmpty)
                    const LedgerCard(
                      child: Text(
                        'Belum ada pengajuan. Booking diperlukan untuk total penarikan harian di atas Rp 1.000.000.',
                        style: AppText.body,
                      ),
                    )
                  else
                    for (final booking in overview.bookings) ...[
                      _BookingCard(booking: booking),
                      const SizedBox(height: 10),
                    ],
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _dailySummary(WithdrawalBookingOverview overview) {
    return LedgerCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Saldo dan penarikan hari ini', style: AppText.metaStrong),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Saldo rekening', style: AppText.meta),
                    const SizedBox(height: 2),
                    Text(
                      Formatters.rupiah(overview.balance),
                      style: AppText.tnum(AppText.sectionTitle),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Saldo mengendap ${Formatters.rupiah(overview.minimumBalance)}',
                      style: AppText.meta,
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Maksimal dapat ditarik', style: AppText.meta),
                    const SizedBox(height: 2),
                    Text(
                      Formatters.rupiah(overview.maxWithdrawable),
                      style: AppText.tnum(AppText.sectionTitle),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'Sisa batas hari ini ${Formatters.rupiah(overview.todayRemaining)}',
                      style: AppText.meta,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _bookingForm(WithdrawalBookingOverview overview) {
    final bool canBook = overview.maxWithdrawable > _dailyLimit;
    final bool isFormEnabled = !_submitting && canBook;

    return LedgerCard(
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Ajukan booking', style: AppText.sectionTitle),
            const SizedBox(height: 4),
            Text(
              'Nominal yang diminta adalah total rencana penarikan pada tanggal tersebut. Teller perlu menyetujui booking sebelum batas harian bertambah.',
              style: AppText.body.copyWith(color: AppColors.stone600),
            ),
            if (!canBook) ...[
              const SizedBox(height: 12),
              AlertCard(
                tone: AlertTone.warning,
                title: 'Saldo belum mencukupi',
                message:
                    'Saldo yang dapat ditarik saat ini ${Formatters.rupiah(overview.maxWithdrawable)} (saldo ${Formatters.rupiah(overview.balance)} dikurangi saldo mengendap ${Formatters.rupiah(overview.minimumBalance)}). Booking hanya dapat diajukan jika saldo mencukupi untuk penarikan di atas Rp 1.000.000.',
              ),
            ],
            const SizedBox(height: 16),
            Text('Tanggal penarikan', style: AppText.fieldLabel),
            const SizedBox(height: 6),
            OutlinedButton.icon(
              onPressed: isFormEnabled ? _chooseDate : null,
              icon: const Icon(Icons.calendar_today_outlined, size: 18),
              label: Text(Formatters.date(_withdrawalDate)),
              style: OutlinedButton.styleFrom(
                alignment: Alignment.centerLeft,
                minimumSize: const Size.fromHeight(48),
              ),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _amountController,
              enabled: isFormEnabled,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'Total rencana penarikan (Rp)',
                helperText: canBook
                    ? 'Batas booking saat ini: Rp 1.000.001 s.d. ${Formatters.rupiah(overview.maxWithdrawable)}.'
                    : 'Saldo belum mencukupi untuk penarikan di atas Rp 1.000.000.',
              ),
              validator: (value) {
                final amount = int.tryParse(value?.trim() ?? '');
                if (amount == null || amount <= 0) {
                  return 'Masukkan nominal dalam angka bulat.';
                }
                if (amount <= _dailyLimit) {
                  return 'Booking hanya untuk total harian di atas Rp 1.000.000.';
                }
                if (amount > overview.balance) {
                  return 'Saldo tidak mencukupi untuk nominal penarikan ini.';
                }
                if (overview.balance - amount < overview.minimumBalance) {
                  return 'Sisa saldo setelah penarikan tidak boleh kurang dari saldo mengendap (${Formatters.rupiah(overview.minimumBalance)}).';
                }
                return null;
              },
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _notesController,
              enabled: isFormEnabled,
              maxLength: 500,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Keterangan untuk teller (opsional)',
                alignLabelWithHint: true,
              ),
            ),
            if (_submitError != null) ...[
              const SizedBox(height: 4),
              AlertCard(
                tone: AlertTone.warning,
                title: 'Booking belum terkirim',
                message: _submitError!,
              ),
            ],
            const SizedBox(height: 12),
            FilledButton(
              onPressed: isFormEnabled ? _submit : null,
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              child: _submitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(
                      canBook
                          ? 'Kirim untuk persetujuan'
                          : 'Saldo belum mencukupi untuk booking',
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  const _BookingCard({required this.booking});

  final WithdrawalBooking booking;

  @override
  Widget build(BuildContext context) {
    final (background, border, foreground) = switch (booking.status) {
      'approved' => (AppColors.olive50, AppColors.olive200, AppColors.olive700),
      'rejected' => (AppColors.red50, AppColors.red200, AppColors.red800),
      _ => (AppColors.amber50, AppColors.amber200, AppColors.amber900),
    };

    return LedgerCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  Formatters.date(booking.withdrawalDate),
                  style: AppText.metaStrong,
                ),
              ),
              Container(
                constraints: const BoxConstraints(minHeight: 32),
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: background,
                  border: Border.all(color: border),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  booking.statusLabel,
                  style: AppText.metaStrong.copyWith(color: foreground),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            Formatters.rupiah(booking.amount),
            style: AppText.tnum(AppText.sectionTitle),
          ),
          if (booking.notes != null && booking.notes!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(booking.notes!, style: AppText.body),
          ],
          if (booking.decisionNote != null &&
              booking.decisionNote!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Catatan teller: ${booking.decisionNote}',
              style: AppText.body.copyWith(color: AppColors.stone600),
            ),
          ],
        ],
      ),
    );
  }
}
