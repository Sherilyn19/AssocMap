import '../../app/configuration/app_config.dart';
import '../../core/networking/laravel_api_client.dart';
import '../models/association_records.dart';
import '../repositories/association_records_repository.dart';
import 'api_dashboard_repository.dart' show AuthenticatedDashboardRead;

class ApiAssociationRecordsRepository implements AssociationRecordsRepository {
  const ApiAssociationRecordsRepository({
    required this.config,
    required this.read,
  });
  final AppConfig config;
  final AuthenticatedDashboardRead read;

  @override
  Future<AssociationRecordsPage<OfficialMember>> members({int page = 1}) =>
      _load('members', page, OfficialMember.fromJson);
  @override
  Future<AssociationRecordsPage<AssociationTraining>> trainings({
    int page = 1,
  }) => _load('trainings', page, AssociationTraining.fromJson);

  Future<AssociationRecordsPage<T>> _load<T>(
    String key,
    int page,
    T Function(Map<String, dynamic>) parse,
  ) async {
    final base = config.backendBaseUri;
    if (base == null || page < 1) {
      throw const AssociationRecordsFailure(
        AssociationRecordsFailureKind.unavailable,
      );
    }
    try {
      final prefix = base.path.replaceFirst(RegExp(r'/+$'), '');
      final response = await read(
        base.replace(
          path: '$prefix/api/v1/association/$key',
          queryParameters: {'page': '$page'},
        ),
      );
      final data = response['data'];
      if (data is! Map<String, dynamic>) {
        throw const FormatException('Invalid response.');
      }
      final result = AssociationRecordsPage<T>.fromJson(data, key, parse);
      if (result.page != page) throw const FormatException('Unexpected page.');
      return result;
    } on ApiFailure catch (error) {
      throw AssociationRecordsFailure(switch (error.kind) {
        ApiFailureKind.unauthenticated => AssociationRecordsFailureKind.expired,
        ApiFailureKind.forbidden => AssociationRecordsFailureKind.denied,
        ApiFailureKind.offline => AssociationRecordsFailureKind.offline,
        ApiFailureKind.rateLimited => AssociationRecordsFailureKind.rateLimited,
        _ => AssociationRecordsFailureKind.unavailable,
      });
    } on AssociationRecordsFailure {
      rethrow;
    } catch (_) {
      throw const AssociationRecordsFailure(
        AssociationRecordsFailureKind.unavailable,
      );
    }
  }
}
