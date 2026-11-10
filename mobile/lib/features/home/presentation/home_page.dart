import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/auth/auth_controller.dart';
import '../../../core/localization/app_localizations.dart';
import '../data/wallet_repository.dart';

final walletRepositoryProvider = Provider<WalletRepository>((ref) => WalletRepository(ref.watch(apiClientProvider)));
final walletOverviewProvider = FutureProvider<Map<String, dynamic>>((ref) => ref.watch(walletRepositoryProvider).overview());

class HomePage extends ConsumerWidget {
  const HomePage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final overview = ref.watch(walletOverviewProvider);
    final strings = AppLocalizations.of(context);
    final user = ref.watch(authControllerProvider).user;
    return Scaffold(appBar: AppBar(title: Text(strings.home), actions: [IconButton(onPressed: () {}, icon: const Icon(Icons.notifications_none))]), body: RefreshIndicator(onRefresh: () => ref.refresh(walletOverviewProvider.future), child: ListView(padding: const EdgeInsets.all(20), children: [Text('Good morning, ${user?['name'] ?? 'there'}', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 8), Text(strings.sandboxNotice), const SizedBox(height: 20), overview.when(loading: () => const _LoadingCard(), error: (_, __) => const _ErrorCard(), data: (response) { final data = (response['data'] as Map?)?.cast<String, dynamic>() ?? {}; return Column(children: [Card(color: Theme.of(context).colorScheme.primary, child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Text('TOTAL ESTIMATED BALANCE', style: TextStyle(color: Colors.white70, fontSize: 11)), const SizedBox(height: 10), Text(data['estimated_balance']?.toString() ?? 'Not available', style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800)), const SizedBox(height: 10), const Text('Backend-authoritative sandbox data', style: TextStyle(color: Colors.white70))])), const SizedBox(height: 18), Row(children: [_Action(icon: Icons.arrow_upward, label: 'Send'), _Action(icon: Icons.arrow_downward, label: 'Receive'), _Action(icon: Icons.currency_exchange, label: 'Exchange')]), const SizedBox(height: 20), const ListTile(contentPadding: EdgeInsets.zero, leading: Icon(Icons.verified_user_outlined), title: Text('KYC status'), subtitle: Text('Open your secure verification status'), trailing: Icon(Icons.chevron_right))]); })])));
  }
}

class _Action extends StatelessWidget { const _Action({required this.icon, required this.label}); final IconData icon; final String label; @override Widget build(BuildContext context) => Expanded(child: Padding(padding: const EdgeInsets.only(right: 8), child: OutlinedButton.icon(onPressed: () {}, icon: Icon(icon, size: 16), label: Text(label)))); }
class _LoadingCard extends StatelessWidget { const _LoadingCard(); @override Widget build(BuildContext context) => const Card(child: SizedBox(height: 150, child: Center(child: CircularProgressIndicator()))); }
class _ErrorCard extends StatelessWidget { const _ErrorCard(); @override Widget build(BuildContext context) => const Card(child: Padding(padding: EdgeInsets.all(20), child: Text('Unable to load wallet data. Check your connection and retry.'))); }
