import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:digital_wallet_mobile/main.dart';
import 'package:digital_wallet_mobile/app/app.dart';

void main() {
  testWidgets('mobile app boots into the session entry point', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: DigitalWalletApp()));
    await tester.pumpAndSettle();
    expect(find.text('Sign in'), findsOneWidget);
  });
}
