import 'package:cookie_jar/cookie_jar.dart';
import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';

import '../errors/api_exception.dart';

class ApiClient {
  ApiClient({String? baseUrl}) {
    final jar = CookieJar();
    dio = Dio(BaseOptions(baseUrl: baseUrl ?? const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api/v1'), connectTimeout: const Duration(seconds: 10), receiveTimeout: const Duration(seconds: 15), headers: {'Accept': 'application/json', 'Content-Type': 'application/json'}));
    dio.interceptors.add(CookieManager(jar));
    dio.interceptors.add(InterceptorsWrapper(onRequest: (options, handler) { options.headers['X-Request-ID'] = _requestId(); handler.next(options); }));
  }

  late final Dio dio;
  Future<Response<dynamic>> get(String path, {Map<String, dynamic>? queryParameters}) => _request(() => dio.get(path, queryParameters: queryParameters));
  Future<Response<dynamic>> post(String path, {Object? data, Map<String, dynamic>? queryParameters, String? idempotencyKey}) => _request(() => dio.post(path, data: data, queryParameters: queryParameters, options: Options(headers: idempotencyKey == null ? null : {'Idempotency-Key': idempotencyKey})));
  Future<Response<dynamic>> patch(String path, {Object? data}) => _request(() => dio.patch(path, data: data));

  Future<Response<dynamic>> _request(Future<Response<dynamic>> Function() request) async {
    try { return await request(); } on DioException catch (error) { final data = error.response?.data; final message = data is Map && data['message'] is String ? data['message'] as String : 'Unable to reach the wallet service.'; throw ApiException(message, statusCode: error.response?.statusCode); }
  }

  static String _requestId() => '${DateTime.now().microsecondsSinceEpoch}';
}
