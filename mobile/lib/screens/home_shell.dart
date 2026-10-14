import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../providers/home_tab_provider.dart';
import '../widgets/app_bottom_nav.dart';

/// Cangkang empat tab: Beranda, Mutasi, Kartu, Profil.
///
/// Empat tab ini sudah lengkap: setiap tujuan punya halaman nyata, jadi tidak
/// ada menu yang menuju ke tempat yang belum ada.
class HomeShell extends StatelessWidget {
  const HomeShell({super.key, required this.tabs});

  final List<Widget> tabs;

  static const List<AppNavItem> items = [
    AppNavItem(
      label: 'Beranda',
      icon: Icons.home_outlined,
      selectedIcon: Icons.home_rounded,
    ),
    AppNavItem(
      label: 'Mutasi',
      icon: Icons.receipt_long_outlined,
      selectedIcon: Icons.receipt_long_rounded,
    ),
    AppNavItem(
      label: 'Kartu',
      icon: Icons.qr_code_2_outlined,
      selectedIcon: Icons.qr_code_2_rounded,
    ),
    AppNavItem(
      label: 'Profil',
      icon: Icons.person_outline_rounded,
      selectedIcon: Icons.person_rounded,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    final controller = context.watch<HomeTabController>();

    return Scaffold(
      backgroundColor: AppColors.paper,
      body: IndexedStack(index: controller.index, children: tabs),
      bottomNavigationBar: AppBottomNav(
        items: items,
        selectedIndex: controller.index,
        onSelected: controller.select,
      ),
    );
  }
}
