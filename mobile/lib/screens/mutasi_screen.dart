import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/utils/formatters.dart';
import '../providers/mutasi_provider.dart';
import '../widgets/page_header.dart';
import '../widgets/state_views.dart';
import '../widgets/transaction_tile.dart';
import 'transaction_detail_sheet.dart';

/// Mutasi rekening nasabah. Filter jenis dan rentang tanggal meniru form
/// filter di halaman web, diterjemahkan menjadi chip agar nyaman dijangkau
/// ibu jari.
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
      backgroundColor: AppColors.paper,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 16),
              child: PageHeader(
                eyebrow: 'RIWAYAT UANG',
                title: 'Mutasi rekening',
                subtitle:
                    'Setoran masuk dan penarikan keluar dibedakan '
                    'oleh warna dan tanda.',
                trailing: IconButton(
                  tooltip: 'Filter tanggal',
                  onPressed: () => _pickRange(provider),
                  icon: const Icon(
                    Icons.date_range_outlined,
                    color: AppColors.brand800,
                  ),
                ),
              ),
            ),
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
      final hasFilter =
          provider.type != null ||
          (provider.dateFrom != null && provider.dateTo != null);

      return RefreshIndicator(
        color: AppColors.brand800,
        onRefresh: provider.refresh,
        child: ListView(
          children: [
            const SizedBox(height: 80),
            AppEmptyView(
              title: 'Belum ada mutasi',
              subtitle: hasFilter
                  ? 'Tidak ada transaksi pada filter ini. Coba rentang '
                        'tanggal lain.'
                  : 'Setoran pertama bisa dilakukan di loket sekolah.',
              icon: Icons.receipt_long_outlined,
              action: hasFilter
                  ? OutlinedButton(
                      onPressed: () => provider.applyFilters(
                        type: null,
                        dateFrom: null,
                        dateTo: null,
                      ),
                      child: const Text('Hapus filter'),
                    )
                  : null,
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      color: AppColors.brand800,
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
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
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
      width: double.infinity,
      color: AppColors.white,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
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
                selectedColor: AppColors.brand50,
                backgroundColor: AppColors.white,
                side: BorderSide(
                  color: isSelected ? AppColors.brand200 : AppColors.stone300,
                ),
                labelStyle: TextStyle(
                  color: isSelected ? AppColors.brand900 : AppColors.stone600,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
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

  String _display(String value) => Formatters.date(DateTime.tryParse(value));

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Container(
          padding: const EdgeInsets.only(left: 12),
          decoration: BoxDecoration(
            color: AppColors.brand50,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: AppColors.brand200),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(
                Icons.event_outlined,
                size: 16,
                color: AppColors.brand900,
              ),
              const SizedBox(width: 6),
              Text(
                '${_display(from)} s/d ${_display(to)}',
                style: AppText.tnum(AppText.metaStrong),
              ),
              SizedBox(
                width: 36,
                height: 44,
                child: IconButton(
                  padding: EdgeInsets.zero,
                  iconSize: 16,
                  tooltip: 'Hapus filter tanggal',
                  onPressed: onClear,
                  icon: const Icon(Icons.close, color: AppColors.brand900),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
