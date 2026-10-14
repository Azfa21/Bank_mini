import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/statement.dart';
import '../router/app_router.dart';
import '../services/account_service.dart';
import '../services/statement_pdf_service.dart';
import '../widgets/state_views.dart';
import '../widgets/transaction_tile.dart';
import 'transaction_detail_sheet.dart';

/// Rekening koran, padanan halaman statement web: pilih periode, ringkasan,
/// lalu rincian transaksi dan tombol cetak/simpan PDF.
class StatementScreen extends StatefulWidget {
  const StatementScreen({super.key});

  @override
  State<StatementScreen> createState() => _StatementScreenState();
}

class _StatementScreenState extends State<StatementScreen> {
  late DateTimeRange _range;
  StatementData? _data;
  bool _loading = true;
  bool _pdfBusy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _range = DateTimeRange(start: DateTime(now.year, now.month, 1), end: now);
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final data = await context.read<AccountService>().statement(
        dateFrom: Formatters.apiDate(_range.start),
        dateTo: Formatters.apiDate(_range.end),
      );
      if (mounted) {
        setState(() => _data = data);
      }
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal memuat rekening koran.');
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _pickRange() async {
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: now,
      initialDateRange: _range,
      helpText: 'Pilih periode rekening koran',
    );

    if (picked != null) {
      setState(() => _range = picked);
      await _load();
    }
  }

  Future<void> _exportPdf() async {
    final data = _data;
    if (data == null) {
      return;
    }

    setState(() => _pdfBusy = true);

    try {
      final bytes = await const StatementPdfService().build(data);
      await Printing.sharePdf(
        bytes: bytes,
        filename: 'estatement-${data.account.accountNumber}.pdf',
      );
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Gagal membuat PDF rekening koran.'),
            backgroundColor: AppColors.red800,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _pdfBusy = false);
      }
    }
  }

  void _goBack() {
    if (context.canPop()) {
      context.pop();
    } else {
      context.go(AppRouter.home);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.paper,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          tooltip: 'Kembali',
          onPressed: _goBack,
        ),
        title: const Text('Rekening koran'),
        actions: [
          IconButton(
            tooltip: 'Pilih periode',
            onPressed: _loading ? null : _pickRange,
            icon: const Icon(Icons.date_range_outlined),
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const AppLoading(message: 'Menyiapkan rekening koran...');
    }

    if (_error != null) {
      return AppErrorView(message: _error!, onRetry: _load);
    }

    final data = _data;
    if (data == null) {
      return const AppEmptyView(title: 'Data tidak tersedia');
    }

    return Column(
      children: [
        _PeriodBar(range: _range, verificationCode: data.verificationCode),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              _SummaryGrid(data: data),
              const SizedBox(height: 16),
              const Align(
                alignment: Alignment.centerLeft,
                child: Text('Rincian transaksi', style: AppText.sectionTitle),
              ),
              const SizedBox(height: 10),
              if (data.transactions.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: AppEmptyView(
                    title: 'Tidak ada transaksi',
                    subtitle:
                        'Belum ada mutasi pada periode ini. Coba '
                        'rentang tanggal lain.',
                    icon: Icons.receipt_long_outlined,
                  ),
                )
              else
                for (final transaction in data.transactions) ...[
                  TransactionTile(
                    transaction: transaction,
                    onTap: () => showTransactionDetail(context, transaction),
                  ),
                  const SizedBox(height: 10),
                ],
            ],
          ),
        ),
        SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
            child: FilledButton.icon(
              onPressed: _pdfBusy ? null : _exportPdf,
              icon: _pdfBusy
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.2,
                        color: AppColors.white,
                      ),
                    )
                  : const Icon(Icons.picture_as_pdf_outlined),
              label: Text(
                _pdfBusy ? 'Menyiapkan PDF...' : 'Cetak atau simpan PDF',
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _PeriodBar extends StatelessWidget {
  const _PeriodBar({required this.range, required this.verificationCode});

  final DateTimeRange range;
  final String verificationCode;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      color: AppColors.white,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${Formatters.date(range.start)} - ${Formatters.date(range.end)}',
            style: AppText.body.copyWith(
              fontWeight: FontWeight.w700,
              color: AppColors.ink,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            'Kode verifikasi: $verificationCode',
            style: AppText.tnum(AppText.caption),
          ),
        ],
      ),
    );
  }
}

/// Ringkasan empat angka rekening koran. Warna mengikuti aturan uang web:
/// masuk terakota, keluar merah.
class _SummaryGrid extends StatelessWidget {
  const _SummaryGrid({required this.data});

  final StatementData data;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _SummaryCard(
                label: 'Total Setoran',
                value: data.summary.totalDeposit,
                color: AppColors.brand800,
                icon: Icons.south_west_rounded,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _SummaryCard(
                label: 'Total Penarikan',
                value: data.summary.totalWithdrawal,
                color: AppColors.red700,
                icon: Icons.north_east_rounded,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _SummaryCard(
                label: 'Transfer Masuk',
                value: data.summary.totalTransferIn,
                color: AppColors.brand800,
                icon: Icons.swap_horiz_rounded,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _SummaryCard(
                label: 'Transfer Keluar',
                value: data.summary.totalTransferOut,
                color: AppColors.red700,
                icon: Icons.swap_horiz_rounded,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  final String label;
  final int value;
  final Color color;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: AppRadius.cardAll,
        border: Border.all(color: AppColors.stone200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(height: 8),
          Text(label, style: AppText.micro),
          const SizedBox(height: 3),
          Text(
            Formatters.rupiah(value),
            style: AppText.tnum(
              const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
                color: AppColors.ink,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
