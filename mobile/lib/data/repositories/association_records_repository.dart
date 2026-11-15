import '../models/association_records.dart';

enum AssociationRecordsFailureKind {
  unavailable,
  offline,
  expired,
  denied,
  rateLimited,
}

class AssociationRecordsFailure implements Exception {
  const AssociationRecordsFailure(this.kind);
  final AssociationRecordsFailureKind kind;
  String get message => switch (kind) {
    AssociationRecordsFailureKind.offline =>
      'Check your connection and retry. Records are not stored offline.',
    AssociationRecordsFailureKind.expired =>
      'Your session expired. Sign in again.',
    AssociationRecordsFailureKind.denied =>
      'Your association access is unavailable. Ask the administrator to review your account link.',
    AssociationRecordsFailureKind.rateLimited =>
      'Too many requests. Wait a minute before retrying.',
    AssociationRecordsFailureKind.unavailable =>
      'Records could not be loaded. Please retry later.',
  };
}

abstract interface class AssociationRecordsRepository {
  Future<AssociationRecordsPage<OfficialMember>> members({int page = 1});
  Future<AssociationRecordsPage<AssociationTraining>> trainings({int page = 1});
}

class UnavailableAssociationRecordsRepository
    implements AssociationRecordsRepository {
  const UnavailableAssociationRecordsRepository();
  @override
  Future<AssociationRecordsPage<OfficialMember>> members({
    int page = 1,
  }) async => throw const AssociationRecordsFailure(
    AssociationRecordsFailureKind.unavailable,
  );
  @override
  Future<AssociationRecordsPage<AssociationTraining>> trainings({
    int page = 1,
  }) async => throw const AssociationRecordsFailure(
    AssociationRecordsFailureKind.unavailable,
  );
}
