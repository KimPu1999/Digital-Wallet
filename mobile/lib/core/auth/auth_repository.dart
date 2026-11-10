import '../api/api_client.dart';

class AuthRepository {
  const AuthRepository(this.api);
  final ApiClient api;
  Future<Map<String, dynamic>> login(String email, String password) async => (await api.post('/auth/login', data: {'email': email, 'password': password})).data as Map<String, dynamic>;
  Future<Map<String, dynamic>> me() async => (await api.get('/auth/me')).data as Map<String, dynamic>;
  Future<void> logout() async { await api.post('/auth/logout'); }
  Future<Map<String, dynamic>> mfaStatus() async => (await api.get('/auth/mfa')).data as Map<String, dynamic>;
}
