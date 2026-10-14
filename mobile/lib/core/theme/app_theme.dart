import 'package:flutter/material.dart';

import 'app_colors.dart';
import 'app_text.dart';

/// Radius dipakai konsisten lintas layar. Variasi radius adalah alat hierarki,
/// bukan dekorasi: kartu paling penting dapat 16, kontrol 12, navigasi 8.
class AppRadius {
  const AppRadius._();

  static const double nav = 8;
  static const double control = 12;
  static const double card = 16;
  static const double sheet = 22;

  static const BorderRadius navAll = BorderRadius.all(Radius.circular(nav));
  static const BorderRadius controlAll = BorderRadius.all(
    Radius.circular(control),
  );
  static const BorderRadius cardAll = BorderRadius.all(Radius.circular(card));

  /// Lembar rincian transaksi: hanya sudut atas yang membulat supaya terbaca
  /// sebagai panel yang naik dari bawah, bukan kartu terpisah.
  static const BorderRadius sheetTop = BorderRadius.only(
    topLeft: Radius.circular(sheet),
    topRight: Radius.circular(sheet),
  );
}

/// Ukuran minimum area sentuh. Web memaksa 44px lewat `.tap-target`; mobile
/// memakai batas yang sama supaya jari siswa tidak salah tekan.
class AppTapTarget {
  const AppTapTarget._();

  static const double minimum = 44;
  static const double primary = 48;
}

class AppTheme {
  const AppTheme._();

  /// Satu-satunya family. Disamakan dengan `--font-sans` di `app.css`.
  static const String fontFamily = 'Instrument Sans';

  static ThemeData light() {
    final scheme =
        ColorScheme.fromSeed(
          seedColor: AppColors.brand800,
          brightness: Brightness.light,
        ).copyWith(
          primary: AppColors.brand800,
          onPrimary: AppColors.white,
          secondary: AppColors.olive700,
          onSecondary: AppColors.white,
          surface: AppColors.white,
          onSurface: AppColors.ink,
          error: AppColors.red800,
          onError: AppColors.white,
          outline: AppColors.stone300,
          outlineVariant: AppColors.stone200,
          shadow: AppColors.black,
        );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      fontFamily: fontFamily,
      scaffoldBackgroundColor: AppColors.paper,
      // Semua teks bawaan mewarisi warna ini; widget yang butuh warna lain
      // menyetel style-nya sendiri.
      textTheme: const TextTheme(
        bodyLarge: AppText.action,
        bodyMedium: AppText.body,
        bodySmall: AppText.meta,
        titleLarge: AppText.title,
        titleMedium: AppText.sectionTitle,
        labelLarge: AppText.action,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.white,
        foregroundColor: AppColors.ink,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        iconTheme: IconThemeData(color: AppColors.brand800),
        titleTextStyle: TextStyle(
          fontFamily: fontFamily,
          fontSize: 16,
          fontWeight: FontWeight.w600,
          color: AppColors.ink,
        ),
        shape: Border(bottom: BorderSide(color: AppColors.stone200)),
      ),
      cardTheme: const CardThemeData(
        color: AppColors.white,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: AppRadius.cardAll,
          side: BorderSide(color: AppColors.stone200),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        hintStyle: const TextStyle(
          fontFamily: fontFamily,
          color: AppColors.stone500,
          fontSize: 15,
        ),
        labelStyle: AppText.fieldLabel,
        floatingLabelStyle: const TextStyle(
          fontFamily: fontFamily,
          color: AppColors.brand800,
          fontWeight: FontWeight.w600,
        ),
        errorStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 13,
          fontWeight: FontWeight.w500,
          color: AppColors.red800,
        ),
        border: OutlineInputBorder(
          borderRadius: AppRadius.controlAll,
          borderSide: const BorderSide(color: AppColors.stone300),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlAll,
          borderSide: const BorderSide(color: AppColors.stone300),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlAll,
          borderSide: const BorderSide(color: AppColors.brand800, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlAll,
          borderSide: const BorderSide(color: AppColors.red700),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: AppRadius.controlAll,
          borderSide: const BorderSide(color: AppColors.red800, width: 2),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: AppColors.brand800,
          foregroundColor: AppColors.white,
          disabledBackgroundColor: AppColors.brand800.withValues(alpha: 0.45),
          disabledForegroundColor: AppColors.white.withValues(alpha: 0.8),
          minimumSize: const Size.fromHeight(AppTapTarget.primary),
          padding: const EdgeInsets.symmetric(horizontal: 20),
          shape: const RoundedRectangleBorder(
            borderRadius: AppRadius.controlAll,
          ),
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.stone700,
          minimumSize: const Size.fromHeight(AppTapTarget.minimum),
          padding: const EdgeInsets.symmetric(horizontal: 16),
          side: const BorderSide(color: AppColors.stone300),
          shape: const RoundedRectangleBorder(
            borderRadius: AppRadius.controlAll,
          ),
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.brand800,
          minimumSize: const Size(AppTapTarget.minimum, AppTapTarget.minimum),
          textStyle: const TextStyle(
            fontFamily: fontFamily,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.white,
        selectedColor: AppColors.brand50,
        disabledColor: AppColors.stone100,
        side: const BorderSide(color: AppColors.stone300),
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.controlAll),
        labelStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: AppColors.stone700,
        ),
        secondaryLabelStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: AppColors.brand900,
        ),
        showCheckmark: false,
      ),
      dividerTheme: const DividerThemeData(
        color: AppColors.stone100,
        thickness: 1,
        space: 1,
      ),
      listTileTheme: const ListTileThemeData(
        iconColor: AppColors.brand800,
        titleTextStyle: AppText.meta,
        subtitleTextStyle: TextStyle(
          fontFamily: fontFamily,
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: AppColors.ink,
        ),
      ),
      dialogTheme: const DialogThemeData(
        backgroundColor: AppColors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: AppRadius.cardAll),
        titleTextStyle: AppText.sectionTitle,
        contentTextStyle: AppText.body,
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: AppRadius.sheetTop),
        showDragHandle: true,
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: AppColors.ink,
        contentTextStyle: const TextStyle(
          fontFamily: fontFamily,
          fontSize: 14,
          color: AppColors.white,
        ),
        behavior: SnackBarBehavior.floating,
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.controlAll),
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.brand800,
      ),
      datePickerTheme: const DatePickerThemeData(
        backgroundColor: AppColors.white,
        surfaceTintColor: Colors.transparent,
        headerBackgroundColor: AppColors.brand800,
        headerForegroundColor: AppColors.white,
        shape: RoundedRectangleBorder(borderRadius: AppRadius.cardAll),
      ),
      timePickerTheme: const TimePickerThemeData(
        backgroundColor: AppColors.white,
      ),
      splashFactory: InkSparkle.splashFactory,
      materialTapTargetSize: MaterialTapTargetSize.padded,
    );
  }
}
