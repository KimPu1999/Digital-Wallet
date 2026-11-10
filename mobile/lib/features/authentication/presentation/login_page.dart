import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/auth/auth_controller.dart';
import '../../../core/localization/app_localizations.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});
  @override ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final email = TextEditingController();
  final password = TextEditingController();
  @override void dispose() { email.dispose(); password.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final strings = AppLocalizations.of(context);
    return Scaffold(body: SafeArea(child: Center(child: SingleChildScrollView(padding: const EdgeInsets.all(24), child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 440), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      const Icon(Icons.account_balance_wallet_rounded, size: 52, color: Color(0xFF2563EB)), const SizedBox(height: 24),
      Text(strings.appName, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)), const SizedBox(height: 8), Text(strings.sandboxNotice), const SizedBox(height: 28),
      TextField(controller: email, keyboardType: TextInputType.emailAddress, decoration: InputDecoration(labelText: strings.email)), const SizedBox(height: 14),
      TextField(controller: password, obscureText: true, decoration: InputDecoration(labelText: strings.password)), const SizedBox(height: 20),
      if (auth.error != null) Padding(padding: const EdgeInsets.only(bottom: 12), child: Text(auth.error!, style: TextStyle(color: Theme.of(context).colorScheme.error))),
      FilledButton(onPressed: auth.isLoading ? null : () async { final ok = await ref.read(authControllerProvider).login(email.text.trim(), password.text); if (ok && context.mounted) context.go('/home'); }, child: auth.isLoading ? const CircularProgressIndicator() : Text(strings.signIn)),
      const SizedBox(height: 16), TextButton(onPressed: () {}, child: const Text('Forgot password?')), const SizedBox(height: 20),
      Row(mainAxisAlignment: MainAxisAlignment.center, children: [const Text('EN'), Switch(value: strings.locale.languageCode == 'my', onChanged: (value) => ref.read(appSettingsProvider).setLocale(Locale(value ? 'my' : 'en'))), const Text('မြန်မာ')]),
    ])))));
  }
}
