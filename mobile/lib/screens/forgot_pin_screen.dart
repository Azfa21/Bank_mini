import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_theme.dart';
import '../providers/auth_provider.dart';
import '../router/app_router.dart';

class ForgotPinScreen extends StatefulWidget {
  const ForgotPinScreen({super.key});

  @override
  State<ForgotPinScreen> createState() => _ForgotPinScreenState();
}

class _ForgotPinScreenState extends State<ForgotPinScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _otpController = TextEditingController();
  final _pinController = TextEditingController();
  final _confirmController = TextEditingController();
  int _step = 1;
  bool _submitting = false;

  @override
  void dispose() {
    _emailController.dispose();
    _otpController.dispose();
    _pinController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _requestOtp() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() => _submitting = true);

    try {
      await context.read<AuthProvider>().requestResetOtp(
            email: _emailController.text.trim(),
          );

      if (!mounted) {
        return;
      }

      setState(() => _step = 2);

      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(
          const SnackBar(
            content: Text('Kode verifikasi telah dikirim ke email Anda.'),
            behavior: SnackBarBehavior.floating,
          ),
        );
    } on ApiException catch (error) {
      _showError(error.message);
    } catch (_) {
      _showError('Gagal mengirim kode verifikasi. Silakan coba lagi.');
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _resetPin() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() => _submitting = true);

    try {
      await context.read<AuthProvider>().resetPin(
            email: _emailController.text.trim(),
            otp: _otpController.text.trim(),
            pin: _pinController.text,
          );

      if (!mounted) {
        return;
      }

      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('PIN Berhasil Diubah'),
          content: const Text(
            'Silakan masuk kembali menggunakan PIN baru Anda.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Mengerti'),
            ),
          ],
        ),
      );

      if (mounted) {
        context.go(AppRouter.login);
      }
    } on ApiException catch (error) {
      _showError(error.message);
    } catch (_) {
      _showError('Gagal mengubah PIN. Silakan coba lagi.');
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  void _showError(String message) {
    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: AppColors.expense,
          behavior: SnackBarBehavior.floating,
        ),
      );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Reset PIN'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            if (_step == 2) {
              setState(() => _step = 1);
            } else {
              context.go(AppRouter.login);
            }
          },
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.accent.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline, color: AppColors.accent),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _step == 1
                            ? 'Masukkan email yang terdaftar. Kami akan mengirim kode verifikasi ke email tersebut.'
                            : 'Masukkan kode verifikasi dari email dan PIN baru 6 digit angka.',
                        style: const TextStyle(fontSize: 12.5),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 22),
              if (_step == 1) ..._buildStepOne() else ..._buildStepTwo(),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _buildStepOne() {
    return [
      _Field(
        controller: _emailController,
        label: 'Email Terdaftar',
        hint: 'Contoh: ani.wijaya@example.test',
        icon: Icons.email_outlined,
        keyboardType: TextInputType.emailAddress,
        validator: (value) {
          final email = value?.trim() ?? '';
          if (email.isEmpty) {
            return 'Email wajib diisi.';
          }
          if (!email.contains('@') || !email.contains('.')) {
            return 'Format email tidak valid.';
          }
          return null;
        },
      ),
      const SizedBox(height: 28),
      FilledButton(
        onPressed: _submitting ? null : _requestOtp,
        child: _submitting
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.4,
                  color: Colors.white,
                ),
              )
            : const Text('Kirim Kode Verifikasi'),
      ),
    ];
  }

  List<Widget> _buildStepTwo() {
    return [
      _Field(
        controller: _emailController,
        label: 'Email Terdaftar',
        icon: Icons.email_outlined,
        keyboardType: TextInputType.emailAddress,
        readOnly: true,
      ),
      const SizedBox(height: 16),
      _Field(
        controller: _otpController,
        label: 'Kode Verifikasi (6 Digit)',
        hint: 'Masukkan kode dari email',
        icon: Icons.password_outlined,
        keyboardType: TextInputType.number,
        maxLength: 6,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
      ),
      const SizedBox(height: 16),
      _PinField(
        controller: _pinController,
        label: 'PIN Baru',
      ),
      const SizedBox(height: 16),
      _PinField(
        controller: _confirmController,
        label: 'Konfirmasi PIN Baru',
        validator: (value) {
          if (value != _pinController.text) {
            return 'Konfirmasi PIN tidak cocok.';
          }
          return null;
        },
      ),
      const SizedBox(height: 28),
      FilledButton(
        onPressed: _submitting ? null : _resetPin,
        child: _submitting
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.4,
                  color: Colors.white,
                ),
              )
            : const Text('Simpan PIN Baru'),
      ),
      const SizedBox(height: 8),
      TextButton(
        onPressed: _submitting ? null : _requestOtp,
        child: const Text('Kirim ulang kode'),
      ),
    ];
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.hint,
    this.keyboardType,
    this.maxLength,
    this.inputFormatters,
    this.readOnly = false,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final IconData icon;
  final TextInputType? keyboardType;
  final int? maxLength;
  final List<TextInputFormatter>? inputFormatters;
  final bool readOnly;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 13,
            color: Color(0xFF3A4A42),
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          maxLength: maxLength,
          inputFormatters: inputFormatters,
          readOnly: readOnly,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: Icon(icon),
            counterText: '',
          ),
          validator: validator ??
              (value) => value == null || value.trim().isEmpty
                  ? '$label wajib diisi.'
                  : null,
        ),
      ],
    );
  }
}

class _PinField extends StatefulWidget {
  const _PinField({
    required this.controller,
    required this.label,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String? Function(String?)? validator;

  @override
  State<_PinField> createState() => _PinFieldState();
}

class _PinFieldState extends State<_PinField> {
  bool _obscure = true;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          widget.label,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 13,
            color: Color(0xFF3A4A42),
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: widget.controller,
          keyboardType: TextInputType.number,
          obscureText: _obscure,
          maxLength: 6,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: InputDecoration(
            hintText: '6 digit angka',
            counterText: '',
            prefixIcon: const Icon(Icons.lock_outline_rounded),
            suffixIcon: IconButton(
              icon: Icon(
                _obscure
                    ? Icons.visibility_outlined
                    : Icons.visibility_off_outlined,
              ),
              onPressed: () => setState(() => _obscure = !_obscure),
            ),
          ),
          validator: widget.validator ??
              (value) {
                if (value == null || value.isEmpty) {
                  return 'PIN wajib diisi.';
                }
                if (value.length != 6) {
                  return 'PIN harus tepat 6 digit.';
                }
                return null;
              },
        ),
      ],
    );
  }
}
