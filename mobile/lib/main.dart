import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'core/api/api_client.dart';
import 'core/config/app_config.dart';
import 'core/storage/token_storage.dart';
import 'core/theme/app_theme.dart';
import 'providers/auth_provider.dart';
import 'providers/dashboard_provider.dart';
import 'providers/mutasi_provider.dart';
import 'router/app_router.dart';
import 'services/account_service.dart';
import 'services/auth_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID', null);
  runApp(const BankMiniApp());
}

class BankMiniApp extends StatelessWidget {
  const BankMiniApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<TokenStorage>(create: (_) => TokenStorage()),
        Provider<ApiClient>(
          create: (context) => ApiClient(storage: context.read<TokenStorage>()),
        ),
        Provider<AuthService>(
          create: (context) => AuthService(context.read<ApiClient>()),
        ),
        Provider<AccountService>(
          create: (context) => AccountService(context.read<ApiClient>()),
        ),
        ChangeNotifierProvider<AuthProvider>(
          create: (context) => AuthProvider(
            context.read<AuthService>(),
            context.read<TokenStorage>(),
          )..bootstrap(),
        ),
        ChangeNotifierProvider<DashboardProvider>(
          create: (context) => DashboardProvider(context.read<AccountService>()),
        ),
        ChangeNotifierProvider<MutasiProvider>(
          create: (context) => MutasiProvider(context.read<AccountService>()),
        ),
      ],
      child: const _AppView(),
    );
  }
}

class _AppView extends StatefulWidget {
  const _AppView();

  @override
  State<_AppView> createState() => _AppViewState();
}

class _AppViewState extends State<_AppView> {
  late final AppRouter _router;

  @override
  void initState() {
    super.initState();

    final apiClient = context.read<ApiClient>();
    final auth = context.read<AuthProvider>();
    apiClient.onUnauthorized = auth.handleUnauthorized;

    _router = AppRouter(auth);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(),
      routerConfig: _router.router,
    );
  }
}
