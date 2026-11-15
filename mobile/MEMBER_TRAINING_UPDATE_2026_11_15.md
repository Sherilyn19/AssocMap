# Member and training mobile update - 2026-11-15

This update changes the dashboard greeting to **Maayong Adlaw!**, replaces the
Members placeholder with a names-only official roster, and replaces the training
placeholder with dated, read-only training cards. Existing styling, tabs,
registration submission, authentication, public GIS, projects, reports and uploads
remain intact.

## Behavior and API contracts

- `GET /api/v1/association/members?page=1`: authenticated Association Accounts only.
  Server-derived association; `members.member_status = active` and no archive date.
  Applications are never queried as official members. Legacy official records do
  not require a source application. Each record returns only `{name}` in
  `Surname, First Middle Suffix` form, ordered case-insensitively by surname,
  given name, middle name, suffix and stable internal ID. No member IDs, contacts,
  birth dates, registration numbers, positions or photos are returned.
- `GET /api/v1/association/trainings?page=1`: same association guard. Uses the
  fields already authorized on the Association Account web training page: title,
  description, program year, start/end dates, venue, conductor, status and related
  project title. Includes an internal training identifier and archive flag. No
  training cost, internal remarks, creator identifiers or participant details.
  A related project from another association is omitted. Dates sort descending,
  with undated records last; title/ID break ties. Scheduled, completed and
  cancelled statuses remain the stored statuses, without inferring completion.
- Both responses use `{data: {association_id, members|trainings, page,
  last_page, total}}`, 25 records per page, existing bearer authentication,
  secure no-store headers and throttling. Caller association/user overrides are
  prohibited. Current account/link/lifecycle access is rechecked after the read.
  Only GET routes were added.
- The roster count uses the full filtered total, including unloaded pages. Home
  and association-profile member totals use the existing `members_active` metric.
  The historical `members_total` metric is unchanged on the server and labeled
  **Retained member records** in the mobile summary to distinguish its definition.
- Training summary definitions remain unchanged: all retained records, completed
  records, and distinct members with recorded present attendance. Archived
  training history stays visible and clearly labeled, matching the web register.
  Refresh reloads both the dashboard totals and the selected list. Each list has
  loading, empty, error/retry, refresh and load-more states. Revoked/expired access
  and responses for a different association never display cached names or cards.
- Cards show title, dates, status, venue and program year first; conductor,
  related project and description appear under Details. No invented destinations
  or photo sections were added.

## Exact files changed for this task

Flutter implementation:

- `lib/app/app.dart`: create and inject the authenticated records repository.
- `lib/core/authentication/association_guard.dart`: pass the repository through;
  existing session verification/guard behavior is unchanged.
- `lib/core/networking/laravel_api_client.dart`: allow only the two new exact GET
  paths on the existing backend origin; existing budgets/redirect rules apply.
- `lib/data/models/association_records.dart`: typed member/training/page parsing.
- `lib/data/repositories/association_records_repository.dart`: read-only interface
  and safe failure messages.
- `lib/data/remote/api_association_records_repository.dart`: authenticated reads
  through the existing session coordinator, without client association filters.
- `lib/features/account/association_dashboard_screen.dart`: greeting and accurate
  active-member count; preserve layout and styling.
- `lib/features/account/association_home_screen.dart`: pass the repository to
  existing sections.
- `lib/features/account/association_sections.dart`: retain Members/Registrations
  and Projects/Trainings tabs; replace placeholders with functional lists.
- `lib/features/account/association_record_list.dart`: scoped, paginated lists,
  feedback states and responsive training cards.

Laravel implementation (paths relative to repository root):

- `web/routes/api.php`: two read-only authenticated routes.
- `web/app/Http/Controllers/Api/V1/AssociationRecordsController.php`: narrowly
  selected fields, active roster filtering, scoped training reads and recheck.

Verification/documentation:

- `test/association_records_test.dart`
- `test/support/association_record_fixtures.dart`
- `test/association_dashboard_test.dart`
- `test/mobile_m1_widget_test.dart`
- `test/proposed_ui_test.dart`
- `web/tests/Feature/AssociationProgramReadTest.php`
- `web/tests/Feature/DashboardServiceTest.php`
- `web/tests/Feature/MobileAuthenticationTest.php`
- `README.md`
- `MEMBER_TRAINING_UPDATE_2026_11_15.md`

Mobile paths above are relative to `mobile/`. Pre-existing account-email/auth
changes and unrelated documentation in this dirty worktree were preserved.
No migration, dependency, signing, package, permission or shared record change.

## Verification results

- Baseline: 181 isolated Laravel tests / 2,526 assertions and 10 focused Flutter tests passed.
- Final Laravel group: **191 tests / 2,732 assertions passed** using
  `scripts/storage/verify.ps1 -Filter 'MobileAuthenticationTest|AssociationProgramReadTest|DashboardServiceTest|MemberApplicationSubmissionTest'`.
  This uses guarded SQLite `:memory:`, synthetic identities/fixtures, fake storage,
  uncached testing configuration, temporary storage and blocked external HTTP.
  Application-submission regression tests write only synthetic isolated data.
  No shared database or real upload was used.
- Final full Flutter suite: **269 tests passed** with `--concurrency=1`.
  Covers existing auth, GIS, projects, reports, registration, photo handling and
  keyboard regressions, plus new read transport, pagination/retry, empty states,
  revoked/wrong-association access, late responses and app repository wiring.
- Flutter static analysis: **No issues found**. PHP syntax checks, new-controller
  Pint check and `git diff --check` passed.
- New Laravel checks exercise two associations, wrong roles, inactive accounts,
  revoked/archived links, post-read revocation, client scope injection, disallowed
  write methods, safe database failures, alphabetical page boundaries, active
  archived/inactive/pending/rejected exclusion, authorized training fields,
  foreign project suppression and exact summary/list count agreement.
- Responsive widget checks include 320px width, landscape, long text and 200%
  text. These are automated layout checks, not physical-device acceptance.
- The first full Flutter run found a stale retained-member label expectation;
  after updating the contract fixture and expectation, the final full rerun passed.
  Initial new-test fixture issues were also corrected; no production error was
  suppressed to obtain a pass.
- These tests are not PostgreSQL/PostGIS, Supabase provider, Railway deployment
  or real-world end-to-end registration acceptance.

## Physical Galaxy A12 checks

**Verified on the physical phone:** USB-detected Samsung SM-A125F, Android 12;
installed version 12 with `adb install -r` and no app-data clearing. Cold launch
completed in 4.363 seconds. The public map loaded published locations and tiles;
the saved session opened the Association Account workspace without re-entering
credentials. Actual screenshots were inspected for the capitalized greeting,
unchanged dashboard hierarchy, Members/Registrations tabs, training summary/error
layout and registration preview. Android system insets and visible wrapping were
correct in those portrait views.

Members Retry and Trainings Refresh remained usable against the currently deployed
API, with safe unavailable states. Registrations opened the existing own-account
application list and its pending status. Android Back returned to the dashboard.
The unchanged registration form and catalog choices rendered; no fields were
entered, no registration submitted and no file selected or uploaded.

App-process log inspection after these checks found **0 fatal crashes, 0 Flutter
error/unhandled-exception matches and 0 layout-error matches**. This is limited
smoke-test evidence. The temporary USB keep-awake setting was restored to its
original value, `0`; font, network and account settings were not changed.

**Not verified on the deployed phone:** actual member names, populated training
cards/details, multi-page production datasets and real count reconciliation.
The new routes are local changes awaiting Railway deployment, so both new lists
currently show unavailable/retry. Their populated/empty/error/pagination layouts
and scope/count rules passed synthetic automated tests, including 200% text.
Live registration/photo submission and new live login/logout were not tested.
No migrations, deployments, account edits or shared database-record writes were performed.

Screenshots and private runtime logs are local ignored evidence under
`build/member-training-update/`; they may contain account information and must
not be included in a public report or distributable APK package. The distributable
artifact contains the APK only; the companion checksum contains no account data.

## Evaluation APK

- Artifact: `build/member-training-update/AssocMap-Mobile-1.0.0-uat.20261115-v12.apk`
- Standard output: `build/app/outputs/flutter-apk/app-release.apk`
- Version: `1.0.0-uat.20261115`, version code **12**; **58,206,101 bytes**.
- SHA-256: `3D1B1A7B4E4A17FB6702E4EEAAF60F538F496A3658F525B38C83E9F29EFE05A0`
- Backend: `https://portal-assocmap.up.railway.app`; existing staging config unchanged.
- Package: `com.example.mobile`; label: **AssocMap Mobile**.
- Minimum Android API 24, target 36; ARM64, ARMv7 and x86_64 included.
- APK Signature Scheme v2 verified. Existing Android Debug certificate SHA-256:
  `c4812cac16a0052c729184626003fc25958d6f9c1fe53720b22854cc8ab866ed`.
- Release build passed; the local SDK emitted Java native-access/SDK XML version
  warnings. No SDK, dependency or signing configuration was changed.

## Railway deployment requirement

Before accepting these lists against the deployed API, release the new Laravel
controller and the two `web/routes/api.php` entries through the normal Railway
workflow. Rebuild any route cache using the existing deployment procedure. No
migration, seeder, environment variable, storage change or Supabase change is
required. Deploying the matching API first lets older APKs continue to work.
This task does not deploy anything. Until that backend is released, the updated
APK safely displays the unavailable/retry state for the new routes.

After deployment, sign in as two different active Association Accounts and check
that each sees only its own active official names and training records. Compare
the roster total with the dashboard active-member count, and training totals with
the web register, including retained archived history. Confirm pagination, empty
results, retry and revoked access. Do not submit applications or upload files as
part of this read-only acceptance check.

## Rebuild the evaluation APK

From `mobile/`:

```powershell
flutter analyze --no-pub
flutter test --no-pub --concurrency=1
flutter build apk --release --target=lib/main.dart --dart-define-from-file=config/staging.json --build-name=1.0.0-uat.20261115 --build-number=12
```

Keep dependency generation enabled for the release build. On this laptop use
serial tests and temporary bounded Gradle JVM/workers settings if needed.
Install with `adb -s <detected-device-id> install -r <apk-path>` to preserve app
data. This is an internal evaluation build using the existing debug signing
configuration; it is not a production-signed release.
