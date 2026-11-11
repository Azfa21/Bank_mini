import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../providers/auth_provider.dart';
import '../router/app_router.dart';
import '../widgets/alert_card.dart';
import '../widgets/ledger_card.dart';
import '../widgets/page_header.dart';

class ForgotPinScreen extends StatefulWidget {
  const ForgotPinScreen({super.key});

  @override
  State<ForgotPinScreen> createState() => _ForgotPinScreenState();
}

class _ForgotPinScreenState extends State<ForgotPinScreen> {
  final _formKey = GlobalKey<FormState>();
  final _studentNumberController = TextEditingController();
  final _otpController = TextEditingController();
  final _pinController = TextEditingController();
  final _confirmController = TextEditingController();
  int _step = 1;
  bool _submitting = false;
  String? _error;
  String? _notice;

  @override
  void dispose() {
    _studentNumberController.dispose();
    _otpController.dispose();
    _pinController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _requestOtp() async {
    if (_step == 1 && !_formKey.currentState!.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final email = await context.read<AuthProvider>().requestPinResetOtp(
        studentNumber: _studentNumberController.text.trim(),
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _step = 2;
        _notice = email != null && email.isNotEmpty
            ? 'Kode verifikasi dikirim ke $email. Cek kotak masuk itu, lalu '
                  'masukkan kode dan PIN transaksi baru.'
            : 'Masukkan kode verifikasi yang dikirim ke email terdaftar akun ini.';
      });
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal mengirim kode verifikasi. Coba lagi.');
      }
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
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await context.read<AuthProvider>().resetPinWithOtp(
        studentNumber: _studentNumberController.text.trim(),
        otp: _otpController.text.trim(),
        pin: _pinController.text,
      );

      if (!mounted) {
        return;
      }

      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('PIN transaksi berhasil diubah'),
          content: const Text(
            'Gunakan PIN baru saat transaksi di loket teller.',
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
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal mengubah PIN. Silakan coba lagi.');
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  void _goBack() {
    if (_step == 2) {
      setState(() {
        _step = 1;
        _notice = null;
        _error = null;
      });
    } else {
      context.go(AppRouter.login);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: _submitting ? null : _goBack,
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const PageHeader(
                    eyebrow: 'PEMULIHAN PIN',
                    title: 'Lupa PIN transaksi',
                    subtitle:
                        'Masukkan nomor siswa dari kartu rekening untuk '
                        'menerima kode verifikasi dan membuat PIN baru.',
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
                          if (_notice != null) ...[
                            AlertCard(tone: AlertTone.olive, message: _notice!),
                            const SizedBox(height: 18),
                          ],
                          if (_step == 1)
                            ..._buildStepOne()
                          else
                            ..._buildStepTwo(),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _buildStepOne() {
    return [
      _Field(
        controller: _studentNumberController,
        label: 'Nomor siswa',
        hint: 'Contoh: 2023001',
        keyboardType: TextInputType.number,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        validator: (value) {
          final studentNumber = value?.trim() ?? '';
          if (studentNumber.isEmpty) {
            return 'Nomor siswa wajib diisi.';
          }
          if (studentNumber.length < 4) {
            return 'Nomor siswa terlalu pendek.';
          }
          return null;
        },
      ),
      const SizedBox(height: 24),
      FilledButton(
        onPressed: _submitting ? null : _requestOtp,
        child: _submitting
            ? const _ButtonSpinner()
            : const Text('Kirim Kode Verifikasi'),
      ),
    ];
  }

  List<Widget> _buildStepTwo() {
    return [
      _Field(
        controller: _studentNumberController,
        label: 'Nomor siswa',
        readOnly: true,
      ),
      const SizedBox(height: 16),
      _Field(
        controller: _otpController,
        label: 'Kode verifikasi',
        hint: '6 digit dari email',
        keyboardType: TextInputType.number,
        autofillHints: const [AutofillHints.oneTimeCode],
        maxLength: 6,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        validator: (value) {
          if (value == null || value.length != 6) {
            return 'Kode verifikasi harus tepat 6 digit angka.';
          }
          return null;
        },
      ),
      const SizedBox(height: 16),
      _PinField(controller: _pinController, label: 'PIN baru'),
      const SizedBox(height: 16),
      _PinField(
        controller: _confirmController,
        label: 'Konfirmasi PIN baru',
        validator: (value) {
          if (value == null || value.length != 6) {
            return 'Konfirmasi PIN baru harus tepat 6 digit angka.';
          }
          if (value != _pinController.text) {
            return 'Konfirmasi PIN baru tidak cocok.';
          }
          return null;
        },
      ),
      const SizedBox(height: 24),
      FilledButton(
        onPressed: _submitting ? null : _resetPin,
        child: _submitting
            ? const _ButtonSpinner()
            : const Text('Verifikasi & Simpan PIN'),
      ),
      const SizedBox(height: 4),
      TextButton(
        onPressed: _submitting ? null : _requestOtp,
        child: const Text('Kirim ulang kode'),
      ),
    ];
  }
}

class _ButtonSpinner extends StatelessWidget {
  const _ButtonSpinner();

  @override
  Widget build(BuildContext context) {
    return const SizedBox(
      width: 22,
      height: 22,
      child: CircularProgressIndicator(
        strokeWidth: 2.4,
        color: AppColors.white,
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    this.hint,
    this.keyboardType,
    this.autofillHints,
    this.maxLength,
    this.inputFormatters,
    this.readOnly = false,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final TextInputType? keyboardType;
  final Iterable<String>? autofillHints;
  final int? maxLength;
  final List<TextInputFormatter>? inputFormatters;
  final bool readOnly;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppText.fieldLabel),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          autofillHints: autofillHints,
          maxLength: maxLength,
          inputFormatters: inputFormatters,
          readOnly: readOnly,
          decoration: InputDecoration(hintText: hint, counterText: ''),
          validator:
              validator ??
              (value) => value == null || value.trim().isEmpty
                  ? '$label wajib diisi.'
                  : null,
        ),
      ],
    );
  }
}

class _PinField extends StatelessWidget {
  const _PinField({
    required this.controller,
    required this.label,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String? Function(String?)? validator;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: AppText.fieldLabel),
        const SizedBox(height: 6),
        TextFormField(
          controller: controller,
          obscureText: true,
          keyboardType: TextInputType.number,
          maxLength: 6,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            hintText: '6 digit angka',
            counterText: '',
          ),
          validator:
              validator ??
              (value) {
                if (value == null || value.isEmpty) {
                  return '$label wajib diisi.';
                }
                if (value.length != 6) {
                  return '$label harus tepat 6 digit angka.';
                }
                return null;
              },
        ),
      ],
    );
  }
}
