import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/config/app_config.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../providers/auth_provider.dart';
import '../router/app_router.dart';
import '../widgets/alert_card.dart';
import '../widgets/ledger_card.dart';

/// Halaman masuk nasabah, memakai cangkang yang sama dengan halaman login web:
/// latar kertas, blok merek, lalu satu kartu bergaris buku besar.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nisController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  String? _error;

  @override
  void dispose() {
    _nisController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() => _error = null);

    try {
      await context.read<AuthProvider>().login(
        studentNumber: _nisController.text.trim(),
        password: _passwordController.text,
      );
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Login gagal. Silakan coba lagi.');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final submitting = context.watch<AuthProvider>().isSubmitting;

    return Scaffold(
      backgroundColor: AppColors.paper,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _BrandBlock(),
                  const SizedBox(height: 24),
                  const Text(
                    'Masuk ke rekening Anda',
                    style: AppText.titleAuth,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Password aplikasi dibuat saat aktivasi rekening di loket, '
                    'bukan PIN transaksi.',
                    style: AppText.body.copyWith(color: AppColors.stone600),
                  ),
                  const SizedBox(height: 20),
                  LedgerCard(
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (_error != null) ...[
                            AlertCard(tone: AlertTone.error, message: _error!),
                            const SizedBox(height: 18),
                          ],
                          const Text(
                            'Nomor Induk Siswa',
                            style: AppText.fieldLabel,
                          ),
                          const SizedBox(height: 6),
                          TextFormField(
                            controller: _nisController,
                            keyboardType: TextInputType.number,
                            textInputAction: TextInputAction.next,
                            autofillHints: const [AutofillHints.username],
                            decoration: const InputDecoration(
                              hintText: 'Contoh: 2023001',
                            ),
                            validator: (value) {
                              if (value == null || value.trim().isEmpty) {
                                return 'Nomor siswa wajib diisi.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 18),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Password', style: AppText.fieldLabel),
                              TextButton(
                                onPressed: submitting
                                    ? null
                                    : () => context.go(AppRouter.forgotPassword),
                                style: TextButton.styleFrom(
                                  padding: EdgeInsets.zero,
                                  minimumSize: const Size(0, 32),
                                  tapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                ),
                                child: const Text('Lupa password?'),
                              ),
                            ],
                          ),
                          const SizedBox(height: 2),
                          TextFormField(
                            controller: _passwordController,
                            obscureText: _obscurePassword,
                            autofillHints: const [AutofillHints.password],
                            decoration: InputDecoration(
                              hintText: 'Password aplikasi Anda',
                              suffixIcon: TextButton(
                                onPressed: () => setState(
                                  () => _obscurePassword = !_obscurePassword,
                                ),
                                child: Text(
                                  _obscurePassword ? 'Tampilkan' : 'Sembunyikan',
                                ),
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Password wajib diisi.';
                              }
                              if (value.length < 8) {
                                return 'Password minimal 8 karakter.';
                              }
                              return null;
                            },
                            onFieldSubmitted: (_) => _submit(),
                          ),
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: submitting ? null : _submit,
                            child: submitting
                                ? const SizedBox(
                                    width: 22,
                                    height: 22,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.4,
                                      color: AppColors.white,
                                    ),
                                  )
                                : const Text('Masuk'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    '© 2026 Bank Mini Skansaba. Seluruh hak cipta dilindungi.',
                    textAlign: TextAlign.center,
                    style: AppText.meta,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _BrandBlock extends StatelessWidget {
  const _BrandBlock();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
          ),
          clipBehavior: Clip.antiAlias,
          child: SvgPicture.asset(
            'assets/images/bank-mini-logo.svg',
            width: 48,
            height: 48,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                AppConfig.bankName,
                style: AppText.action.copyWith(color: AppColors.stone900),
              ),
              const SizedBox(height: 2),
              const Text('Tabungan siswa sekolah', style: AppText.meta),
            ],
          ),
        ),
      ],
    );
  }
}
