import 'package:mobile/data/models/association_records.dart';
import 'package:mobile/data/repositories/association_records_repository.dart';
import 'member_application_fixtures.dart' show applicationAssociationId;

Map<String, dynamic> trainingRecord() => {
  'id': '77777777-7777-4777-8777-777777777777',
  'title': 'Coastal skills training',
  'status': 'scheduled',
  'program_year': 2027,
  'start_date': '2027-01-02',
  'end_date': '2027-01-03',
  'is_archived': false,
  'venue': 'Coastal community hall',
  'conducted_by': 'Training team',
  'related_project': 'Livelihood project',
  'description': 'Practical coastal livelihood skills.',
};

Map<String, dynamic> recordPage({String key = 'members', int page = 1}) => {
  'association_id': applicationAssociationId,
  'page': page,
  'last_page': 1,
  'total': 1,
  key: key == 'members'
      ? [
          {'name': 'Alpha, Ana'},
        ]
      : [trainingRecord()],
};

class TestAssociationRecordsRepository implements AssociationRecordsRepository {
  Future<AssociationRecordsPage<OfficialMember>> Function(int)? loadMembers;
  Future<AssociationRecordsPage<AssociationTraining>> Function(int)?
  loadTrainings;
  @override
  Future<AssociationRecordsPage<OfficialMember>> members({
    int page = 1,
  }) async => loadMembers != null
      ? loadMembers!(page)
      : AssociationRecordsPage.fromJson(
          recordPage(),
          'members',
          OfficialMember.fromJson,
        );
  @override
  Future<AssociationRecordsPage<AssociationTraining>> trainings({
    int page = 1,
  }) async => loadTrainings != null
      ? loadTrainings!(page)
      : AssociationRecordsPage.fromJson(
          recordPage(key: 'trainings'),
          'trainings',
          AssociationTraining.fromJson,
        );
}
