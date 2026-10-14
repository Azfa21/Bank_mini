import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

class AppNavItem {
  const AppNavItem({
    required this.label,
    required this.icon,
    required this.selectedIcon,
  });

  final String label;
  final IconData icon;
  final IconData selectedIcon;
}

/// Navigasi bawah yang meniru tab navigasi web: item aktif memakai teks
/// `brand-900` dan garis tegas, item lain `stone-600`.
///
/// Material 3 `NavigationBar` diganti karena indikator pill-nya tidak ada di
/// sistem web, dan bentuk pill membuat bentuk ini terasa seperti aplikasi lain.
/// Garis atas pada item aktif adalah penanda keadaan, bukan hiasan.
class AppBottomNav extends StatelessWidget {
  const AppBottomNav({
    super.key,
    required this.items,
    required this.selectedIndex,
    required this.onSelected,
  });

  final List<AppNavItem> items;
  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(
        color: AppColors.white,
        border: Border(top: BorderSide(color: AppColors.stone200)),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 60,
          child: Row(
            children: [
              for (var index = 0; index < items.length; index++)
                Expanded(
                  child: _NavButton(
                    item: items[index],
                    selected: index == selectedIndex,
                    onTap: () => onSelected(index),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final AppNavItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.brand900 : AppColors.stone600;

    return Semantics(
      selected: selected,
      button: true,
      label: item.label,
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            SizedBox(
              height: 2,
              child: selected
                  ? const ColoredBox(
                      color: AppColors.brand800,
                      child: SizedBox.expand(),
                    )
                  : null,
            ),
            const Spacer(),
            Icon(
              selected ? item.selectedIcon : item.icon,
              size: 22,
              color: selected ? AppColors.brand800 : color,
            ),
            const SizedBox(height: 2),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
                color: color,
              ),
            ),
            const Spacer(),
          ],
        ),
      ),
    );
  }
}
