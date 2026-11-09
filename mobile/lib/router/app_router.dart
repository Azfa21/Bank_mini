import 'package:go_router/go_router.dart';

import '../providers/auth_provider.dart';
import '../screens/card_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/change_pin_screen.dart';
import '../screens/forgot_password_screen.dart';
import '../screens/home_shell.dart';
import '../screens/login_screen.dart';
import '../screens/mutasi_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/splash_screen.dart';
import '../screens/statement_screen.dart';
import '../screens/withdrawal_booking_screen.dart';

class AppRouter {
  AppRouter(this._auth);

  final AuthProvider _auth;

  static const String splash = '/splash';
  static const String login = '/login';
  static const String forgotPassword = '/forgot-password';
  static const String changePin = '/change-pin';
  static const String home = '/home';
  static const String statement = '/statement';
  static const String withdrawalBooking = '/withdrawal-booking';

  late final GoRouter router = GoRouter(
    initialLocation: splash,
    refreshListenable: _auth,
    redirect: (context, state) {
      final location = state.matchedLocation;
      final atSplash = location == splash;
      final atAuth = location == login || location == forgotPassword;

      switch (_auth.status) {
        case AuthStatus.unknown:
          return atSplash ? null : splash;
        case AuthStatus.unauthenticated:
          return atAuth ? null : login;
        case AuthStatus.authenticated:
          if (atSplash || atAuth) {
            return home;
          }
          return null;
      }
    },
    routes: [
      GoRoute(path: splash, builder: (context, state) => const SplashScreen()),
      GoRoute(path: login, builder: (context, state) => const LoginScreen()),
      GoRoute(
        path: forgotPassword,
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: changePin,
        builder: (context, state) => const ChangePinScreen(),
      ),
      GoRoute(
        path: home,
        builder: (context, state) => const HomeShell(
          tabs: [
            DashboardScreen(),
            MutasiScreen(),
            CardScreen(),
            ProfileScreen(),
          ],
        ),
      ),
      GoRoute(
        path: statement,
        builder: (context, state) => const StatementScreen(),
      ),
      GoRoute(
        path: withdrawalBooking,
        builder: (context, state) => const WithdrawalBookingScreen(),
      ),
    ],
  );
}
