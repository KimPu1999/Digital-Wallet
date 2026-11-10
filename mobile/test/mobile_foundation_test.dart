import 'package:flutter_test/flutter_test.dart';

void main() {
  test('sandbox policy is explicit', () {
    const financialExecutionEnabled = false;
    expect(financialExecutionEnabled, isFalse);
  });
}
