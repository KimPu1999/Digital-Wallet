import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../home/presentation/home_page.dart';

class WalletsPage extends ConsumerWidget {
  const WalletsPage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final query = ref.watch(walletOverviewProvider);
    return Scaffold(appBar: AppBar(title: const Text('My wallets')), body: query.when(loading: () => const Center(child: CircularProgressIndicator()), error: (_, __) => const Center(child: Text('Wallets unavailable.')), data: (response) { final data = (response['data'] as Map?)?.cast<String, dynamic>(); final wallets = (data?['wallets'] as List<dynamic>?) ?? const []; if (wallets.isEmpty) return const Center(child: Text('No wallet accounts returned by the backend.')); return ListView.separated(padding: const EdgeInsets.all(20), itemCount: wallets.length, separatorBuilder: (_, __) => const SizedBox(height: 10), itemBuilder: (_, index) { final wallet = wallets[index] as Map; return Card(child: ListTile(leading: const CircleAvatar(child: Icon(Icons.account_balance_wallet_outlined)), title: Text(wallet['nickname']?.toString() ?? wallet['label']?.toString() ?? 'Wallet'), subtitle: Text('${wallet['status'] ?? 'unknown'} · ${wallet['financial_data']?['source_of_truth'] ?? 'Backend data'}'), trailing: const Icon(Icons.chevron_right))); } }); }));
  }
}
