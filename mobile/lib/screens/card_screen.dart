import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api/api_exception.dart';
import '../core/theme/app_colors.dart';
import '../models/card_info.dart';
import '../services/account_service.dart';
import '../widgets/page_header.dart';
import '../widgets/state_views.dart';
import '../widgets/virtual_card.dart';

/// Kartu QR nasabah. Kontennya sama dengan bagian "Kartu nasabah virtual" di
/// dashboard web, hanya di sini kartu jadi tab penuh supaya QR mudah
/// ditunjukkan ke teller.
class CardScreen extends StatefulWidget {
  const CardScreen({super.key});

  @override
  State<CardScreen> createState() => _CardScreenState();
}

class _CardScreenState extends State<CardScreen> {
  CardInfo? _card;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final card = await context.read<AccountService>().card();
      if (!mounted) {
        return;
      }
      setState(() => _card = card);
    } on ApiException catch (error) {
      if (mounted) {
        setState(() => _error = error.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Gagal memuat kartu nasabah.');
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.paper,
      body: SafeArea(child: _buildBody()),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const AppLoading(message: 'Memuat kartu...');
    }

    if (_error != null) {
      return ListView(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.6,
            child: AppErrorView(message: _error!, onRetry: _load),
          ),
        ],
      );
    }

    final card = _card;
    if (card == null) {
      return ListView(
        children: const [
          SizedBox(height: 80),
          AppEmptyView(
            title: 'Kartu belum tersedia',
            subtitle: 'Hubungi loket teller untuk mengaktifkan rekening Anda.',
            icon: Icons.credit_card_off_outlined,
          ),
        ],
      );
    }

    return RefreshIndicator(
      color: AppColors.brand800,
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
        children: [
          PageHeader(
            eyebrow: 'KARTU NASABAH',
            title: 'Kartu nasabah virtual',
            subtitle:
                'Tunjukkan QR ini kepada teller untuk setoran, '
                'penarikan, dan transfer.',
          ),
          const SizedBox(height: 20),
          VirtualCard(
            bankName: card.bankName,
            fullName: card.fullName,
            studentNumber: card.studentNumber,
            className: card.className,
            accountNumber: card.accountNumber,
            qrCodeValue: card.qrCodeValue,
          ),
        ],
      ),
    );
  }
}
