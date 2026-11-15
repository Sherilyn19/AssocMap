import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/app/theme/app_theme.dart';
import 'package:mobile/core/networking/laravel_api_client.dart';
import 'package:mobile/core/widgets/assocmap_widgets.dart';
import 'package:mobile/data/models/association_records.dart';
import 'package:mobile/data/models/auth_session.dart';
import 'package:mobile/data/remote/api_association_records_repository.dart';
import 'package:mobile/data/repositories/association_records_repository.dart';
import 'package:mobile/features/account/association_record_list.dart';
import 'mobile_m1_auth_test.dart' show config;
import 'support/association_record_fixtures.dart';
import 'support/member_application_fixtures.dart' show applicationAssociationId;

AuthSession session({String associationId = applicationAssociationId}) =>
    AuthSession(
      userId: '11111111-1111-4111-8111-111111111111',
      displayName: 'Synthetic account',
      role: AccountRole.associationAccount,
      accountIsActive: true,
      roleIsActive: true,
      isDemo: false,
      expiresAt: DateTime.now().add(const Duration(hours: 1)),
      association: AssociationAccess(
        id: associationId,
        name: 'Synthetic association',
        linkIsActive: true,
        associationIsActive: true,
        associationIsArchived: false,
      ),
    );

AssociationRecordsPage<OfficialMember> memberPage({
  int page = 1,
  int lastPage = 1,
  int total = 1,
  String name = 'Alpha, Ana',
  String associationId = applicationAssociationId,
}) => AssociationRecordsPage(
  associationId: associationId,
  records: [OfficialMember(name)],
  page: page,
  lastPage: lastPage,
  total: total,
);

void main() {
  test(
    'Authenticated transport permits only the two exact same-origin read endpoints',
    () async {
      final requests = <http.Request>[];
      final client = LaravelApiClient(
        config: config,
        client: MockClient((request) async {
          requests.add(request);
          final key = request.url.path.split('/').last;
          return http.Response(
            jsonEncode({'data': recordPage(key: key)}),
            200,
            headers: {'content-type': 'application/json'},
          );
        }),
      );
      final repo = ApiAssociationRecordsRepository(
        config: config,
        read: (uri) => client.get(uri, 'synthetic-token'),
      );
      expect((await repo.members()).records.single.name, 'Alpha, Ana');
      expect(
        (await repo.trainings()).records.single.startDate,
        DateTime(2027, 1, 2),
      );
      expect(requests.map((request) => request.url.path), [
        '/api/v1/association/members',
        '/api/v1/association/trainings',
      ]);
      for (final request in requests) {
        expect(request.method, 'GET');
        expect(request.headers['Authorization'], 'Bearer synthetic-token');
        expect(request.url.queryParameters, {'page': '1'});
        expect(request.followRedirects, false);
      }
      await expectLater(
        client.get(
          Uri.parse('https://foreign.example.test/api/v1/association/members'),
          'synthetic-token',
        ),
        throwsA(isA<ApiFailure>()),
      );
      await expectLater(
        client.get(
          Uri.parse(
            'https://backend.example.test/api/v1/association/members/foreign',
          ),
          'synthetic-token',
        ),
        throwsA(isA<ApiFailure>()),
      );
      expect(requests.length, 2);
    },
  );

  test(
    'Training parser retains web information and rejects invalid dates and statuses',
    () {
      final training = AssociationTraining.fromJson(trainingRecord());
      expect(training.relatedProject, 'Livelihood project');
      expect(training.isArchived, false);
      expect(
        AssociationTraining.fromJson(trainingRecord()..['venue'] = ' ').venue,
        null,
      );
      for (final changes in [
        {'status': 'invented'},
        {'is_archived': 'false'},
        {'start_date': '2027-02-30'},
        {'program_year': -1},
      ]) {
        expect(
          () => AssociationTraining.fromJson(trainingRecord()..addAll(changes)),
          throwsFormatException,
        );
      }
    },
  );

  test(
    'Malformed and unexpected pages fail safely rather than displaying partial data',
    () async {
      for (final changes in [
        {'page': 2, 'last_page': 2},
        {'total': -1},
        {
          'members': [
            {'name': ''},
          ],
        },
        {'members': 'invalid'},
      ]) {
        final repo = ApiAssociationRecordsRepository(
          config: config,
          read: (_) async => {'data': recordPage()..addAll(changes)},
        );
        await expectLater(
          repo.members(),
          throwsA(isA<AssociationRecordsFailure>()),
        );
      }
    },
  );

  for (final entry in {
    ApiFailureKind.offline: AssociationRecordsFailureKind.offline,
    ApiFailureKind.unauthenticated: AssociationRecordsFailureKind.expired,
    ApiFailureKind.forbidden: AssociationRecordsFailureKind.denied,
    ApiFailureKind.rateLimited: AssociationRecordsFailureKind.rateLimited,
    ApiFailureKind.unavailable: AssociationRecordsFailureKind.unavailable,
  }.entries) {
    test('Both repositories preserve ${entry.key} failures', () async {
      final repo = ApiAssociationRecordsRepository(
        config: config,
        read: (_) async => throw ApiFailure(entry.key),
      );
      for (final future in [repo.members(), repo.trainings()]) {
        await expectLater(
          future,
          throwsA(
            isA<AssociationRecordsFailure>().having(
              (error) => error.kind,
              'kind',
              entry.value,
            ),
          ),
        );
      }
    });
  }

  Future<void> render(
    WidgetTester tester,
    TestAssociationRecordsRepository repo, {
    bool trainings = false,
    AuthSession? current,
    VoidCallback? onExpired,
    double scale = 1,
  }) => tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light,
      home: Scaffold(
        body: MediaQuery(
          data: MediaQueryData(textScaler: TextScaler.linear(scale)),
          child: PageContent(
            children: [
              AssociationRecordList(
                repository: repo,
                session: current ?? session(),
                trainings: trainings,
                onSessionExpired: onExpired ?? () {},
              ),
            ],
          ),
        ),
      ),
    ),
  );

  testWidgets(
    'Roster loading, full count, pagination retry and refresh are usable',
    (tester) async {
      final pending = Completer<AssociationRecordsPage<OfficialMember>>();
      final repo = TestAssociationRecordsRepository()
        ..loadMembers = (_) => pending.future;
      await render(tester, repo);
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      pending.complete(memberPage(total: 2, lastPage: 2));
      await tester.pumpAndSettle();
      expect(find.text('Alpha, Ana'), findsOneWidget);
      expect(find.text('Showing 1 of 2'), findsOneWidget);
      repo.loadMembers = (_) async => throw const AssociationRecordsFailure(
        AssociationRecordsFailureKind.offline,
      );
      await tester.tap(find.text('Load more'));
      await tester.pumpAndSettle();
      expect(find.text('Alpha, Ana'), findsOneWidget);
      expect(find.textContaining('Check your connection'), findsOneWidget);
      repo.loadMembers = (page) async =>
          memberPage(page: page, lastPage: 2, total: 2, name: 'Zulu, Zoe');
      await tester.tap(find.text('Retry'));
      await tester.pumpAndSettle();
      expect(find.text('Zulu, Zoe'), findsOneWidget);
      expect(find.text('Showing 2 of 2'), findsOneWidget);
      expect(find.text('Load more'), findsNothing);
      repo.loadMembers = (_) async => memberPage(name: 'New, Name');
      await tester.tap(find.byTooltip('Refresh Official members'));
      await tester.pumpAndSettle();
      expect(find.text('New, Name'), findsOneWidget);
      expect(find.text('Alpha, Ana'), findsNothing);
      expect(find.text('Showing 1 of 1'), findsOneWidget);
    },
  );

  for (final kind in [
    AssociationRecordsFailureKind.denied,
    AssociationRecordsFailureKind.expired,
  ]) {
    testWidgets(
      '$kind clears previously loaded names and expires only when required',
      (tester) async {
        var expired = 0;
        final repo = TestAssociationRecordsRepository()
          ..loadMembers = (_) async => memberPage(lastPage: 2, total: 2);
        await render(tester, repo, onExpired: () => expired++);
        await tester.pumpAndSettle();
        repo.loadMembers = (_) async => throw AssociationRecordsFailure(kind);
        await tester.tap(find.text('Load more'));
        await tester.pumpAndSettle();
        expect(find.text('Alpha, Ana'), findsNothing);
        expect(expired, kind == AssociationRecordsFailureKind.expired ? 1 : 0);
      },
    );
  }

  testWidgets('Foreign association response is never displayed', (
    tester,
  ) async {
    final repo = TestAssociationRecordsRepository()
      ..loadMembers = (_) async => memberPage(associationId: 'foreign');
    await render(tester, repo);
    await tester.pumpAndSettle();
    expect(find.text('Alpha, Ana'), findsNothing);
    expect(find.text('Official members unavailable'), findsOneWidget);
  });

  testWidgets(
    'Session changes clear old data and ignore late previous responses',
    (tester) async {
      final old = Completer<AssociationRecordsPage<OfficialMember>>();
      final repo = TestAssociationRecordsRepository()
        ..loadMembers = (_) => old.future;
      await render(tester, repo);
      const nextId = '55555555-5555-4555-8555-555555555555';
      repo.loadMembers = (_) async =>
          memberPage(associationId: nextId, name: 'New association name');
      await render(tester, repo, current: session(associationId: nextId));
      await tester.pumpAndSettle();
      old.complete(memberPage());
      await tester.pumpAndSettle();
      expect(find.text('New association name'), findsOneWidget);
      expect(find.text('Alpha, Ana'), findsNothing);
    },
  );

  for (final trainings in [false, true]) {
    testWidgets(
      'Empty ${trainings ? 'trainings' : 'members'} remain distinct from errors',
      (tester) async {
        final repo = TestAssociationRecordsRepository();
        repo.loadMembers = (_) async => const AssociationRecordsPage(
          associationId: applicationAssociationId,
          records: [],
          page: 1,
          lastPage: 1,
          total: 0,
        );
        repo.loadTrainings = (_) async => const AssociationRecordsPage(
          associationId: applicationAssociationId,
          records: [],
          page: 1,
          lastPage: 1,
          total: 0,
        );
        await render(tester, repo, trainings: trainings);
        await tester.pumpAndSettle();
        expect(
          find.textContaining(
            trainings ? 'No training records' : 'No active official members',
          ),
          findsOneWidget,
        );
        expect(find.text('Retry'), findsNothing);
        expect(find.text('Showing 0 of 0'), findsOneWidget);
      },
    );
  }

  testWidgets(
    'Training cards wrap long text, dates, statuses and expandable web details at 200 percent',
    (tester) async {
      tester.view.physicalSize = const Size(320, 640);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = TestAssociationRecordsRepository()
        ..loadTrainings = (_) async => AssociationRecordsPage(
          associationId: applicationAssociationId,
          page: 1,
          lastPage: 1,
          total: 2,
          records: [
            AssociationTraining.fromJson(trainingRecord()),
            AssociationTraining.fromJson(
              trainingRecord()
                ..['title'] =
                    'Completed training with a long descriptive title for a coastal livelihood association'
                ..['status'] = 'completed'
                ..['is_archived'] = true
                ..['start_date'] = null
                ..['end_date'] = null,
            ),
          ],
        );
      await render(tester, repo, trainings: true, scale: 2);
      await tester.pumpAndSettle();
      expect(find.text('scheduled'), findsOneWidget);
      expect(find.text('completed'), findsOneWidget);
      expect(find.text('archived'), findsOneWidget);
      expect(find.text('Start date not recorded'), findsOneWidget);
      await tester.ensureVisible(find.text('Details').first);
      await tester.tap(find.text('Details').first);
      await tester.pumpAndSettle();
      expect(find.text('Training team'), findsOneWidget);
      expect(find.text('Livelihood project'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
}
