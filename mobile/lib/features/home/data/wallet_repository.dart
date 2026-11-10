import '../../../core/api/api_client.dart';

class WalletRepository {
  const WalletRepository(this.api);
  final ApiClient api;
  Future<Map<String, dynamic>> overview() async => (await api.get('/dashboard/wallet-overview')).data as Map<String, dynamic>;
  Future<List<dynamic>> wallets() async { final response = await api.get('/wallets'); final data = response.data as Map<String, dynamic>; return (data['data'] as List<dynamic>? ?? const []); }
  Future<List<dynamic>> activity() async { final response = await api.get('/payments'); final data = response.data as Map<String, dynamic>; return (data['data'] as List<dynamic>? ?? const []); }
}
