import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';
import '../router/app_router.dart';
import '../widgets/page_header.dart';
import '../widgets/status_pill.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Keluar akun'),
        content: const Text('Anda yakin ingin keluar dari aplikasi?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      await context.read<AuthProvider>().logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final customer = auth.customer;
    final account = auth.account;

    return Scaffold(
      backgroundColor: AppColors.paper,
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
          children: [
            const PageHeader(
              eyebrow: 'AKUN SAYA',
              title: 'Profil',
              subtitle: 'Data rekening dan sesi masuk Anda.',
            ),
            const SizedBox(height: 20),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppColors.white,
                borderRadius: AppRadius.cardAll,
                border: Border.all(color: AppColors.stone200),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: AppColors.brand50,
                    child: Text(
                      _initials(customer?.fullName ?? ''),
                      style: const TextStyle(
                        color: AppColors.brand800,
                        fontWeight: FontWeight.w700,
                        fontSize: 18,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          customer?.fullName ?? '-',
                          style: AppText.action.copyWith(
                            fontSize: 17,
                            color: AppColors.stone900,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'NIS ${customer?.studentNumber ?? '-'}',
                          style: AppText.tnum(AppText.meta),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            _InfoGroup(
              items: [
                _InfoItem(
                  icon: Icons.groups_outlined,
                  label: 'Kelas',
                  value: _value(customer?.className ?? '-'),
                ),
                _InfoItem(
                  icon: Icons.phone_outlined,
                  label: 'No. HP',
                  value: _value(customer?.phoneNumber ?? '-'),
                ),
                _InfoItem(
                  icon: Icons.credit_card_rounded,
                  label: 'No. Rekening',
                  value: _value(account?.accountNumber ?? '-', numeric: true),
                ),
                _InfoItem(
                  icon: Icons.verified_outlined,
                  label: 'Status Rekening',
                  value: account == null
                      ? _value('-')
                      : StatusPill(
                          isActive: account.status == 'active',
                          label: _statusLabel(account.status),
                        ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _ActionTile(
              icon: Icons.description_outlined,
              label: 'Rekening koran',
              onTap: () => context.push(AppRouter.statement),
            ),
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: () => _confirmLogout(context),
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Keluar'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.red700,
                minimumSize: const Size.fromHeight(48),
                side: const BorderSide(color: AppColors.red200),
              ),
            ),
          ],
        ),
      ),
    );
  }

  static Widget _value(String text, {bool numeric = false}) {
    final style = AppText.body.copyWith(
      fontWeight: FontWeight.w600,
      color: AppColors.ink,
    );

    return Text(text, style: numeric ? AppText.tnum(style) : style);
  }

  static String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) {
      return '?';
    }
    if (parts.length == 1) {
      return parts.first.substring(0, 1).toUpperCase();
    }
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1))
        .toUpperCase();
  }

  static String _statusLabel(String status) {
    return switch (status) {
      'active' => 'Rekening aktif',
      'blocked' => 'Diblokir',
      'closed' => 'Ditutup',
      _ => status,
    };
  }
}

class _InfoGroup extends StatelessWidget {
  const _InfoGroup({required this.items});

  final List<_InfoItem> items;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: AppRadius.cardAll,
        border: Border.all(color: AppColors.stone200),
      ),
      child: Column(
        children: [
          for (var i = 0; i < items.length; i++) ...[
            items[i],
            if (i < items.length - 1) const Divider(height: 1, indent: 52),
          ],
        ],
      ),
    );
  }
}

class _InfoItem extends StatelessWidget {
  const _InfoItem({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final Widget value;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      visualDensity: VisualDensity.compact,
      shape: RoundedRectangleBorder(borderRadius: AppRadius.cardAll),
      leading: Icon(icon, color: AppColors.brand800),
      title: Text(label, style: AppText.meta),
      subtitle: value,
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: AppRadius.cardAll,
        border: Border.all(color: AppColors.stone200),
      ),
      child: ListTile(
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: AppRadius.cardAll),
        leading: Icon(icon, color: AppColors.brand800),
        title: Text(
          label,
          style: AppText.body.copyWith(
            fontWeight: FontWeight.w600,
            color: AppColors.ink,
          ),
        ),
        trailing: const Icon(Icons.chevron_right, color: AppColors.stone500),
      ),
    );
  }
}
