import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_client.dart';
import 'auth_repository.dart';

final apiClientProvider = Provider<ApiClient>((_) => ApiClient());
final authRepositoryProvider = Provider<AuthRepository>((ref) => AuthRepository(ref.watch(apiClientProvider)));
final authControllerProvider = ChangeNotifierProvider<AuthController>((ref) => AuthController(ref.watch(authRepositoryProvider)));
final appSettingsProvider = ChangeNotifierProvider<AppSettings>((_) => AppSettings());

class AuthController extends ChangeNotifier {
  AuthController(this.repository) { restore(); }
  final AuthRepository repository;
  bool isLoading = false;
  bool isAuthenticated = false;
  Map<String, dynamic>? user;
  String? error;

  Future<void> restore() async { isLoading = true; notifyListeners(); try { user = (await repository.me())['data'] as Map<String, dynamic>?; isAuthenticated = user != null; } catch (_) { isAuthenticated = false; } isLoading = false; notifyListeners(); }
  Future<bool> login(String email, String password) async { isLoading = true; error = null; notifyListeners(); try { final response = await repository.login(email, password); if (response['mfa_required'] == true || (response['data'] as Map?)?['mfa_required'] == true) { error = 'Additional verification is required.'; return false; } user = ((response['data'] as Map?)?['user'] as Map?)?.cast<String, dynamic>(); isAuthenticated = true; return true; } catch (exception) { error = exception.toString(); return false; } finally { isLoading = false; notifyListeners(); } }
  Future<void> logout() async { try { await repository.logout(); } finally { isAuthenticated = false; user = null; notifyListeners(); } }
}

class AppSettings extends ChangeNotifier {
  ThemeMode themeMode = ThemeMode.light;
  Locale locale = const Locale('en');
  void toggleTheme() { themeMode = themeMode == ThemeMode.light ? ThemeMode.dark : ThemeMode.light; notifyListeners(); }
  void setLocale(Locale value) { locale = value; notifyListeners(); }
}
