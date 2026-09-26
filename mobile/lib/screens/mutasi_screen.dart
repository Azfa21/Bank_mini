import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_theme.dart';
import '../core/utils/formatters.dart';
import '../providers/mutasi_provider.dart';
import '../widgets/state_views.dart';
import '../widgets/transaction_tile.dart';
import 'transaction_detail_sheet.dart';

class MutasiScreen extends StatefulWidget {
  const MutasiScreen({super.key});

  @override
  State<MutasiScreen> createState() => _MutasiScreenState();
}

class _MutasiScreenState extends State<MutasiScreen> {
  static const List<({String label, String? value})> _types = [
    (label: 'Semua', value: null),
    (label: 'Setoran', value: 'deposit'),
    (label: 'Penarikan', value: 'withdrawal'),
    (label: 'Transfer', value: 'transfer'),
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MutasiProvider>().load();
    });
  }

  Future<void> _pickRange(MutasiProvider provider) async {
    final now = DateTime.now();
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(now.year - 3),
      lastDate: now,
      initialDateRange: DateTimeRange(
        start: DateTime(now.year, now.month, 1),
        end: now,
      ),
      helpText: 'Pilih rentang tanggal',
    );

    if (picked == null) {
      return;
    }

    await provider.applyFilters(
      type: provider.type,
      dateFrom: Formatters.apiDate(picked.start),
      dateTo: Formatters.apiDate(picked.end),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<MutasiProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Mutasi'),
        actions: [
          IconButton(
            tooltip: 'Filter tanggal',
            onPressed: () => _pickRange(provider),
            icon: const Icon(Icons.date_range_outlined),
          ),
        ],
      ),
      body: Column(
        children: [
          _TypeFilterBar(
            selected: provider.type,
            onSelected: (value) => provider.applyFilters(
              type: value,
              dateFrom: provider.dateFrom,
              dateTo: provider.dateTo,
            ),
          ),
          if (provider.dateFrom != null && provider.dateTo != null)
            _RangeChip(
              from: provider.dateFrom!,
              to: provider.dateTo!,
              onClear: () => provider.applyFilters(
                type: provider.type,
                dateFrom: null,
                dateTo: null,
              ),
            ),
          Expanded(child: _buildList(provider)),
        ],
      ),
    );
  }

  Widget _buildList(MutasiProvider provider) {
    if (provider.loading && provider.transactions.isEmpty) {
      return const AppLoading(message: 'Memuat mutasi...');
    }

    if (provider.error != null && provider.transactions.isEmpty) {
      return AppErrorView(message: provider.error!, onRetry: provider.load);
    }

    if (provider.transactions.isEmpty) {
      return RefreshIndicator(
        color: AppColors.primary,
        onRefresh: provider.refresh,
        child: ListView(
          children: const [
            SizedBox(height: 80),
            AppEmptyView(
              title: 'Belum ada mutasi',
              subtitle: 'Transaksi pada periode ini belum tersedia.',
              icon: Icons.receipt_long_outlined,
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: AppColors.primary,
      onRefresh: provider.refresh,
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification.metrics.pixels >=
              notification.metrics.maxScrollExtent - 240) {
            provider.loadMore();
          }
          return false;
        },
        child: ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: provider.transactions.length + (provider.hasMore ? 1 : 0),
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, index) {
            if (index >= provider.transactions.length) {
              return const Padding(
                padding: EdgeInsets.symmetric(vertical: 18),
                child: Center(
                  child: SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(strokeWidth: 2.4),
                  ),
                ),
              );
            }

            final transaction = provider.transactions[index];
            return TransactionTile(
              transaction: transaction,
              onTap: () => showTransactionDetail(context, transaction),
            );
          },
        ),
      ),
    );
  }
}

class _TypeFilterBar extends StatelessWidget {
  const _TypeFilterBar({required this.selected, required this.onSelected});

  final String? selected;
  final ValueChanged<String?> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: _MutasiScreenState._types.map((type) {
            final isSelected = selected == type.value;
            return Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ChoiceChip(
                label: Text(type.label),
                selected: isSelected,
                onSelected: (_) => onSelected(type.value),
                selectedColor: AppColors.primary.withValues(alpha: 0.14),
                labelStyle: TextStyle(
                  color: isSelected ? AppColors.primary : AppColors.muted,
                  fontWeight: FontWeight.w600,
                  fontSize: 12.5,
                ),
                side: BorderSide(
                  color: isSelected
                      ? AppColors.primary.withValues(alpha: 0.4)
                      : const Color(0xFFCBD5CE),
                ),
                backgroundColor: Colors.white,
                showCheckmark: false,
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

class _RangeChip extends StatelessWidget {
  const _RangeChip({
    required this.from,
    required this.to,
    required this.onClear,
  });

  final String from;
  final String to;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Chip(
          avatar: const Icon(Icons.event, size: 16),
          label: Text('$from s/d $to'),
          onDeleted: onClear,
          deleteIcon: const Icon(Icons.close, size: 16),
          backgroundColor: AppColors.accent.withValues(alpha: 0.12),
          side: BorderSide.none,
        ),
      ),
    );
  }
}
