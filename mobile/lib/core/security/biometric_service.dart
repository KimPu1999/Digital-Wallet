import 'package:local_auth/local_auth.dart';

class BiometricService {
  BiometricService([LocalAuthentication? auth]) : _auth = auth ?? LocalAuthentication();
  final LocalAuthentication _auth;
  Future<bool> isAvailable() async { try { return await _auth.canCheckBiometrics || await _auth.isDeviceSupported(); } catch (_) { return false; } }
  Future<bool> authenticate() async { try { return await _auth.authenticate(localizedReason: 'Unlock your secure wallet session'); } catch (_) { return false; } }
}
