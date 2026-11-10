import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/auth/auth_controller.dart';
import '../../features/authentication/presentation/login_page.dart';
import '../../features/home/presentation/home_page.dart';
import '../../features/profile/presentation/profile_page.dart';
import '../../features/transactions/presentation/activity_page.dart';
import '../../features/wallets/presentation/wallets_page.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);
  return GoRouter(
    initialLocation: '/login',
    redirect: (context, state) {
      final isLogin = state.matchedLocation == '/login';
      if (auth.isLoading) return null;
      if (!auth.isAuthenticated && !isLogin) return '/login';
      if (auth.isAuthenticated && isLogin) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginPage()),
      StatefulShellRoute.indexedStack(
        builder: (_, __, navigationShell) => MobileScaffold(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [GoRoute(path: '/home', builder: (_, __) => const HomePage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/wallets', builder: (_, __) => const WalletsPage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/send', builder: (_, __) => const ComingSoonPage(title: 'Send money'))]),
          StatefulShellBranch(routes: [GoRoute(path: '/activity', builder: (_, __) => const ActivityPage())]),
          StatefulShellBranch(routes: [GoRoute(path: '/profile', builder: (_, __) => const ProfilePage())]),
        ],
      ),
    ],
  );
});

class MobileScaffold extends StatelessWidget {
  const MobileScaffold({required this.navigationShell, super.key});
  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context) {
    const labels = ['Home', 'Wallets', 'Send', 'Activity', 'Profile'];
    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: navigationShell.currentIndex,
        onDestinationSelected: navigationShell.goBranch,
        destinations: List.generate(labels.length, (index) => NavigationDestination(icon: Icon(_icons[index]), label: labels[index])),
      ),
    );
  }

  static const _icons = [Icons.home_outlined, Icons.account_balance_wallet_outlined, Icons.arrow_upward_rounded, Icons.receipt_long_outlined, Icons.person_outline];
}

class ComingSoonPage extends StatelessWidget {
  const ComingSoonPage({required this.title, super.key});
  final String title;

  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: Text(title)), body: const Center(child: Text('Available in a later sandbox phase.')));
}
