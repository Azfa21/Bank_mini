import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../models/dashboard_data.dart';
import '../providers/auth_provider.dart';
import '../providers/dashboard_provider.dart';
import '../router/app_router.dart';
import '../widgets/state_views.dart';
import '../widgets/transaction_tile.dart';

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
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: provider.load,
        child: _buildBody(provider, data),
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
            child: AppErrorView(message: provider.error!, onRetry: provider.load),
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
      padding: EdgeInsets.zero,
      children: [
        _Header(data: data),
        Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _BalanceCard(
                balance: account?.balance,
                accountNumber: account?.accountNumber,
                minimumBalance: account?.minimumBalance,
              ),
              const SizedBox(height: 16),
              _SectionTitle(title: 'Transaksi Terakhir'),
              const SizedBox(height: 10),
              if (last == null)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: AppEmptyView(
                    title: 'Belum ada transaksi',
                    icon: Icons.receipt_long_outlined,
                  ),
                )
              else
                TransactionTile(transaction: last),
              const SizedBox(height: 20),
              _InfoCard(
                bankName: data.bank.name,
                operationalHours: data.bank.operationalHours,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final customer = context.watch<AuthProvider>().customer ?? data.customer;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 26),
      decoration: const BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(26),
          bottomRight: Radius.circular(26),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.account_balance_rounded,
                  color: Colors.white, size: 22),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  data.bank.name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              IconButton(
                onPressed: () => context.go(AppRouter.statement),
                icon: const Icon(Icons.description_outlined, color: Colors.white),
                tooltip: 'e-Statement',
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Text(
            'Selamat datang,',
            style: TextStyle(color: Colors.white70, fontSize: 13),
          ),
          const SizedBox(height: 2),
          Text(
            customer.fullName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            'NIS ${customer.studentNumber}'
            '${customer.className != null ? ' • ${customer.className}' : ''}',
            style: const TextStyle(color: Colors.white70, fontSize: 12.5),
          ),
        ],
      ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({
    required this.balance,
    required this.accountNumber,
    required this.minimumBalance,
  });

  final int? balance;
  final String? accountNumber;
  final int? minimumBalance;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8E4)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F0F5132),
            blurRadius: 18,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Saldo Rekening',
            style: TextStyle(color: AppColors.muted, fontSize: 13),
          ),
          const SizedBox(height: 6),
          Text(
            balance == null ? '-' : Formatters.rupiah(balance!),
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              color: Color(0xFF10241A),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              const Icon(Icons.credit_card_rounded,
                  size: 16, color: AppColors.muted),
              const SizedBox(width: 6),
              Text(
                accountNumber ?? 'Belum ada rekening',
                style: const TextStyle(
                  color: AppColors.muted,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          if (minimumBalance != null) ...[
            const SizedBox(height: 6),
            Text(
              'Saldo mengendap minimum ${Formatters.rupiah(minimumBalance!)}',
              style: const TextStyle(color: AppColors.muted, fontSize: 11.5),
            ),
          ],
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 15,
          fontWeight: FontWeight.w700,
          color: Color(0xFF1F2A24),
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.bankName,
    required this.operationalHours,
  });

  final String bankName;
  final String operationalHours;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.accent.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          const Icon(Icons.schedule_rounded, color: AppColors.accent),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Jam Operasional',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: Color(0xFF3A4A42),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '$operationalHours • $bankName',
                  style: const TextStyle(fontSize: 12, color: AppColors.muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
