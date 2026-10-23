import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_colors.dart';
import '../core/theme/app_text.dart';
import '../providers/auth_provider.dart';
import '../widgets/alert_card.dart';
import '../widgets/ledger_card.dart';
import '../widgets/page_header.dart';

/// Ganti PIN transaksi dari aplikasi. Sesi tetap jalan karena PIN bukan
/// kredensial pembuka sesi — keliru sampai tiga kali pun sesi tidak dicabut,
/// yang dikunci hanya aksi verifikasi PIN selama 15 menit.
class ChangePinScreen extends StatefulWidget {
  const ChangePinScreen({super.key});

  @override
  State<ChangePinScreen> createState() => _ChangePinScreenState();
}

class _ChangePinScreenState extends State<ChangePinScreen> {
  final _formKey = GlobalKey<FormState>();
  final _currentPinController = TextEditingController();
  final _newPinController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _currentPinController.dispose();
    _newPinController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await context.read<AuthProvider>().changePin(
        currentPin: _currentPinController.text,
        newPin: _newPinController.text,
      );

      if (!mounted) {
        return;
      }

      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('PIN berhasil diubah'),
          content: const Text(
            'PIN baru berlaku saat transaksi di loket teller.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Mengerti'),
            ),
          ],
        ),
      );

      if (mounted && context.canPop()) {
        context.pop();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: _submitting ? null : context.pop,
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
                    eyebrow: 'KEAMANAN TRANSAKSI',
                    title: 'Ubah PIN Transaksi',
                    subtitle:
                        'PIN dipakai hanya saat transaksi di loket teller, '
                        'bukan untuk masuk ke aplikasi. Password login '
                        'tidak berubah di halaman ini.',
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
                          _PinField(
                            controller: _currentPinController,
                            label: 'PIN saat ini',
                            helper:
                                'Salah 3 kali berturut-turut mengunci '
                                'transaksi selama 15 menit.',
                          ),
                          const SizedBox(height: 16),
                          _PinField(
                            controller: _newPinController,
                            label: 'PIN baru',
                            helper:
                                'Pilih yang mudah Anda ingat tapi bukan '
                                'tanggal lahir atau NIS.',
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'PIN baru wajib diisi.';
                              }
                              if (value.length != 6) {
                                return 'PIN baru harus tepat 6 digit.';
                              }
                              if (value == _currentPinController.text) {
                                return 'PIN baru tidak boleh sama dengan '
                                    'PIN saat ini.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          _PinField(
                            controller: _confirmController,
                            label: 'Konfirmasi PIN baru',
                            validator: (value) {
                              if (value != _newPinController.text) {
                                return 'Konfirmasi PIN baru tidak cocok.';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: _submitting ? null : _submit,
                            child: _submitting
                                ? const _ButtonSpinner()
                                : const Text('Simpan PIN baru'),
                          ),
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

class _PinField extends StatefulWidget {
  const _PinField({
    required this.controller,
    required this.label,
    this.helper,
    this.validator,
  });

  final TextEditingController controller;
  final String label;
  final String? helper;
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
        Text(widget.label, style: AppText.fieldLabel),
        const SizedBox(height: 6),
        TextFormField(
          controller: widget.controller,
          keyboardType: TextInputType.number,
          obscureText: _obscure,
          maxLength: 6,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: InputDecoration(
            hintText: '6 digit angka',
            counterText: '',
            suffixIcon: TextButton(
              onPressed: () => setState(() => _obscure = !_obscure),
              child: Text(_obscure ? 'Tampilkan' : 'Sembunyikan'),
            ),
          ),
          validator:
              widget.validator ??
              (value) {
                if (value == null || value.isEmpty) {
                  return '${widget.label} wajib diisi.';
                }
                if (value.length != 6) {
                  return '${widget.label} harus tepat 6 digit.';
                }
                return null;
              },
        ),
        if (widget.helper != null) ...[
          const SizedBox(height: 8),
          Text(widget.helper!, style: AppText.meta),
        ],
      ],
    );
  }
}
