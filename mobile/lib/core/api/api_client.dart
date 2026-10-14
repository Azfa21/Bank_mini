import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';

class ApiClient {
  ApiClient({TokenStorage? storage, Dio? dio})
    : _storage = storage ?? TokenStorage(),
      _dio = dio ?? Dio() {
    _dio.options
      ..baseUrl = AppConfig.apiBaseUrl
      ..connectTimeout = const Duration(seconds: 15)
      ..receiveTimeout = const Duration(seconds: 20)
      ..sendTimeout = const Duration(seconds: 20)
      ..headers = {'Accept': 'application/json'}
      ..responseType = ResponseType.json;

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          final status = error.response?.statusCode;
          final path = error.requestOptions.path;
          if (status == 401 && !path.contains('/mobile/login')) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  final Dio _dio;
  final TokenStorage _storage;

  void Function()? onUnauthorized;

  Future<Map<String, dynamic>> getJson(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    return _request(() => _dio.get<dynamic>(path, queryParameters: query));
  }

  Future<Map<String, dynamic>> postJson(String path, {Object? data}) async {
    return _request(() => _dio.post<dynamic>(path, data: data));
  }

  Future<Map<String, dynamic>> _request(
    Future<Response<dynamic>> Function() send,
  ) async {
    try {
      final response = await send();
      final body = response.data;

      if (body is Map<String, dynamic>) {
        return body;
      }

      return {'success': true, 'data': body};
    } on DioException catch (error) {
      throw _mapError(error);
    }
  }

  ApiException _mapError(DioException error) {
    final response = error.response;
    final body = response?.data;

    if (body is Map) {
      final message = body['message'];
      if (message is String && message.isNotEmpty) {
        return ApiException(message, statusCode: response?.statusCode);
      }

      final errors = body['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          return ApiException(
            first.first.toString(),
            statusCode: response?.statusCode,
          );
        }
        if (first is String) {
          return ApiException(first, statusCode: response?.statusCode);
        }
      }
    }

    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
      case DioExceptionType.connectionError:
        return ApiException(
          'Tidak dapat terhubung ke server. Periksa koneksi dan alamat server.',
        );
      default:
        return ApiException(
          'Terjadi kesalahan. Silakan coba lagi.',
          statusCode: response?.statusCode,
        );
    }
  }
}
