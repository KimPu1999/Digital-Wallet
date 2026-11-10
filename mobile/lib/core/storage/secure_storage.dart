import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorage {
  const SecureStorage(this._storage);
  final FlutterSecureStorage _storage;
  static const biometricEnabledKey = 'biometric_unlock_enabled';
  Future<void> setBiometricEnabled(bool enabled) => _storage.write(key: biometricEnabledKey, value: enabled.toString());
  Future<bool> biometricEnabled() async => (await _storage.read(key: biometricEnabledKey)) == 'true';
  Future<void> clear() => _storage.deleteAll();
}
