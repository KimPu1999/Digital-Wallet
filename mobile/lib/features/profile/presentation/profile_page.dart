import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/auth/auth_controller.dart';

class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(appSettingsProvider);
    final user = ref.watch(authControllerProvider).user;
    return Scaffold(appBar: AppBar(title: const Text('Profile')), body: ListView(padding: const EdgeInsets.all(20), children: [CircleAvatar(radius: 32, child: Text((user?['name']?.toString() ?? 'U').substring(0, 1).toUpperCase())), const SizedBox(height: 12), Center(child: Text(user?['name']?.toString() ?? 'Customer', style: Theme.of(context).textTheme.titleLarge)), const SizedBox(height: 20), SwitchListTile(title: const Text('Dark mode'), value: settings.themeMode == ThemeMode.dark, onChanged: (_) => ref.read(appSettingsProvider).toggleTheme()), ListTile(title: const Text('Language'), subtitle: Text(settings.locale.languageCode == 'my' ? 'မြန်မာ' : 'English'), trailing: DropdownButton<Locale>(value: settings.locale, items: const [DropdownMenuItem(value: Locale('en'), child: Text('EN')), DropdownMenuItem(value: Locale('my'), child: Text('MM'))], onChanged: (value) { if (value != null) ref.read(appSettingsProvider).setLocale(value); })), ListTile(leading: const Icon(Icons.security_outlined), title: const Text('Security settings'), subtitle: const Text('MFA, devices and session controls')), const SizedBox(height: 12), OutlinedButton(onPressed: () => ref.read(authControllerProvider).logout(), child: const Text('Sign out'))]));
  }
}
