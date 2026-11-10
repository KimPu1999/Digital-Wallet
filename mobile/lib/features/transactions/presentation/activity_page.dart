import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../home/presentation/home_page.dart';

final activityProvider = FutureProvider<List<dynamic>>((ref) => ref.watch(walletRepositoryProvider).activity());

class ActivityPage extends ConsumerWidget {
  const ActivityPage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final query = ref.watch(activityProvider);
    return Scaffold(appBar: AppBar(title: const Text('Activity')), body: query.when(loading: () => const Center(child: CircularProgressIndicator()), error: (_, __) => const Center(child: Text('Activity unavailable.')), data: (items) => items.isEmpty ? const Center(child: Text('No activity returned by the backend.')) : ListView.separated(padding: const EdgeInsets.all(20), itemCount: items.length, separatorBuilder: (_, __) => const Divider(), itemBuilder: (_, index) { final item = items[index] as Map; return ListTile(title: Text(item['reference']?.toString() ?? item['type']?.toString() ?? 'Transaction'), subtitle: Text(item['status']?.toString() ?? 'Status unavailable'), trailing: Text(item['currency_code']?.toString() ?? '')); }));
  }
}
