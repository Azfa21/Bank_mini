import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/utils/formatters.dart';
import '../models/dashboard_data.dart';
import '../models/transaction.dart';
import '../providers/dashboard_provider.dart';
import '../providers/home_tab_provider.dart';
import '../router/app_router.dart';
import '../widgets/alert_card.dart';
import '../widgets/ledger_card.dart';
import '../widgets/page_header.dart';
import '../widgets/state_views.dart';
import '../widgets/status_pill.dart';
import '../widgets/transaction_tile.dart';

/// Beranda nasabah, disusun mengikuti dashboard nasabah di web: kepala halaman,
/// lalu satu kartu saldo bergaris buku besar berisi status rekening,
/// transaksi terakhir, dan dua aksi utama.
///
/// Kartu QR tidak diulang di sini karena sudah punya tab Kartu sendiri;
/// menggandakan konten yang sama di dua tab hanya menambah kebisingan.
class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DashboardProvider>().load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<DashboardProvider>();
    final data = provider.data;

    return Scaffold(
      backgroundColor: AppColors.paper,
      body: SafeArea(
        child: RefreshIndicator(
          color: AppColors.brand800,
          onRefresh: provider.load,
          child: _buildBody(provider, data),
        ),
      ),
    );
  }

  Widget _buildBody(DashboardProvider provider, DashboardData? data) {
    if (provider.loading && data == null) {
      return const AppLoading(message: 'Memuat data rekening...');
    }

    if (provider.error != null && data == null) {
      return ListView(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.6,
            child: AppErrorView(
              message: provider.error!,
              onRetry: provider.load,
            ),
          ),
        ],
      );
    }

    if (data == null) {
      return const AppEmptyView(title: 'Data tidak tersedia');
    }

    final account = data.account;
    final last = data.lastTransaction;

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
      children: [
        PageHeader(
          eyebrow: 'TABUNGAN SAYA',
          title: 'Halo, ${data.customer.fullName}',
          subtitle: 'Pantau saldo dan mutasi rekening sekolah Anda.',
        ),
        const SizedBox(height: 20),
        if (account == null)
          const AlertCard(
            tone: AlertTone.warning,
            title: 'Rekening belum aktif',
            message:
                'Datang ke loket Bank Mini sekolah dengan kartu pelajar '
                'untuk pembukaan rekening.',
          )
        else
          _AccountSummary(
            balance: account.balance,
            accountNumber: account.accountNumber,
            isActive: account.status == 'active',
            last: last,
          ),
      ],
    );
  }
}

class _AccountSummary extends StatelessWidget {
  const _AccountSummary({
    required this.balance,
    required this.accountNumber,
    required this.isActive,
    required this.last,
  });

  final int balance;
  final String accountNumber;
  final bool isActive;
  final Transaction? last;

  @override
  Widget build(BuildContext context) {
    return LedgerCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Saldo berjalan', style: AppText.meta),
          const SizedBox(height: 4),
          Text(
            Formatters.rupiah(balance),
            style: AppText.tnum(AppText.balance),
          ),
          const SizedBox(height: 8),
          Text(
            'No. rekening $accountNumber',
            style: AppText.tnum(AppText.meta),
          ),
          const SizedBox(height: 12),
          Align(
            alignment: Alignment.centerLeft,
            child: StatusPill(
              isActive: isActive,
              label: isActive ? 'Rekening aktif' : 'Rekening tidak aktif',
            ),
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Divider(height: 1),
          ),
          Text('Transaksi terakhir', style: AppText.metaStrong),
          const SizedBox(height: 8),
          if (last == null)
            Text(
              'Belum ada transaksi. Setoran pertama bisa dilakukan di loket '
              'sekolah.',
              style: AppText.body.copyWith(color: AppColors.stone600),
            )
          else
            TransactionTile(transaction: last!),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () => context.read<HomeTabController>().select(1),
            child: const Text('Lihat riwayat mutasi'),
          ),
          const SizedBox(height: 10),
          OutlinedButton(
            onPressed: () => context.push(AppRouter.statement),
            style: OutlinedButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
            child: const Text('Unduh rekening koran'),
          ),
        ],
      ),
    );
  }
}
