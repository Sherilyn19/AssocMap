<?php

namespace App\Services\Members;

use App\Models\Association;
use App\Models\AssociationAccount;
use App\Models\AssociationPosition;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\MemberApplicationReviewEvent;
use App\Models\Role;
use App\Models\UserProfile;
use App\Services\Associations\AssociationLifecycleService;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Throwable;

class MemberApplicationService
{
    public function __construct(
        private MemberExecutivePositionService $executivePositions,
        private MemberApplicationPhotoService $photos,
        private MemberPositionIntegrityService $positions,
        private AssociationLifecycleService $lifecycle,
        private AuditLogService $audit,
    ) {}

    // Check the association account before saving an application and its photo.
    public function submit(array $data, UserProfile $currentUser): MemberApplication
    {
        $this->ensureAssociationAccountCanSubmit($currentUser);
        $associationAccount = AssociationAccount::query()
            ->where('user_id', $currentUser->id)
            ->where('is_active', true)
            ->first();

        if (! $associationAccount) {
            throw ValidationException::withMessages([
                'account' => 'No active Association Account is linked to this user.',
            ]);
        }

        $position = $this->validatePositionOtherText($data);
        $this->executivePositions->ensureApplicationPositionAvailable($associationAccount->association_id, $position);
        $this->ensureFishRAvailable($data['fishr_registration_number'] ?? null);

        $applicationId = (string) Str::uuid();
        $photoPath = null;
        $callbackCompleted = false;

        try {
            return DB::transaction(function () use ($data, $currentUser, $associationAccount,
                $position, $applicationId, &$photoPath, &$callbackCompleted) {
                // Match account replacement: role, association, account-holder profile, then link.
                $role = Role::whereKey($currentUser->role_id)->where('code', 'association_account')->sharedLock()->first();
                abort_unless($role !== null, 403);
                $this->lifecycle->lockOperational([$associationAccount->association_id]);
                $actor = UserProfile::whereKey($currentUser->id)->lockForUpdate()->first();
                abort_unless($actor && (string) $actor->role_id === (string) $role->id
                    && $actor->getRawOriginal('auth_sessions_valid_after') === $currentUser->getRawOriginal('auth_sessions_valid_after'), 403);
                $actor->setRelation('role', $role);
                $this->ensureAssociationAccountCanSubmit($actor);
                $link = AssociationAccount::query()
                    ->whereKey($associationAccount->id)
                    ->where('user_id', $actor->id)
                    ->where('association_id', $associationAccount->association_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $link) {
                    throw ValidationException::withMessages([
                        'account' => 'Your active association link changed. Refresh the page before submitting again.',
                    ]);
                }

                $this->executivePositions->ensureApplicationPositionAvailable($link->association_id, $position);

                $photoPath = isset($data['applicant_photo'])
                    ? $this->photos->store($data['applicant_photo'], $applicationId)
                    : null;
                $application = new MemberApplication([
                    'association_id' => $link->association_id,
                    'submitted_by_user_id' => $actor->id,
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'],
                    'suffix' => $data['suffix'] ?? null,
                    'birth_date' => $data['birth_date'],
                    'sex_id' => $data['sex_id'],
                    'contact_number' => $data['contact_number'] ?? null,
                    'fishr_registration_number' => $data['fishr_registration_number'] ?? null,
                    'position_id' => $data['position_id'],
                    'position_other_text' => $this->resolvePositionOtherText($data),
                    'endorsement_letter' => $data['endorsement_letter'],
                    'applicant_photo_path' => $photoPath,
                    'status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]);
                $application->id = $applicationId;
                $application->save();
                $application->classifications()->sync($data['classification_ids'] ?? []);
                $this->recordApplicationAction($actor, $application, 'SUBMIT MEMBER APPLICATION');
                $callbackCompleted = true;

                return $application;
            });
        } catch (Throwable $exception) {
            // Commit failures may have an unknown outcome; retain evidence for reconciliation.
            if (! $callbackCompleted && DB::transactionLevel() === 0) {
                if ($photoPath !== null) {
                    $this->photos->discard($photoPath, $applicationId);
                }
            } else {
                Log::warning('Member application submission requires reconciliation.', [
                    'application_id' => $applicationId,
                    'code' => 'submission_outcome_uncertain',
                ]);
            }

            throw $exception;
        }
    }

    // Only a permitted association account may send an application.
    private function ensureAssociationAccountCanSubmit(?UserProfile $actor): void
    {
        if (! $actor || ! $actor->canAccessApplication(throwOnDatabaseError: true) || $actor->role?->code !== 'association_account') {
            abort(403, 'Only an active authorized Association Account may submit applications.');
        }
    }

    // Create the member and record the approval together.
    public function approve(
        MemberApplication $application,
        UserProfile $fieldOfficer
    ): Member {
        return DB::transaction(function () use (
            $application,
            $fieldOfficer
        ) {
            [$application, $fieldOfficer] = $this->lockForReview($application, $fieldOfficer);

            // Public applicants cannot become members before association endorsement.
            if ($application->status === 'pending' && $application->submission_source === 'public_gis' && $application->endorsement_stage !== 'endorsed') {
                throw ValidationException::withMessages(['application' => 'The association must endorse this application before Field Officer review.'])->status(409);
            }
            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'application' =>
                        'This application has already been reviewed. Refresh the list before making another decision.',
                ])->status(409);
            }

            $position = $this->positions->activePosition($application->position_id, $application->position_other_text);
            $member = new Member([
                'association_id' =>
                    $application->association_id,

                'source_application_id' =>
                    $application->id,

                'first_name' =>
                    $application->first_name,

                'middle_name' =>
                    $application->middle_name,

                'last_name' =>
                    $application->last_name,

                'suffix' =>
                    $application->suffix,

                'birth_date' =>
                    $application->birth_date,

                'sex_id' =>
                    $application->sex_id,

                'contact_number' =>
                    $application->contact_number,

                // Preserve the applicant's optional email for later contact.
                'email' => $application->email,

                'fishr_registration_number' =>
                    $application->fishr_registration_number,

                'position_id' =>
                    $application->position_id,

                'position_other_text' =>
                    $position->code === 'OTHER' ? $application->position_other_text : null,

                'date_registered' => now()->toDateString(),

                'member_status' => 'active',
            ]);

            $this->executivePositions->save($member);
            $member->classifications()->sync(
                $application
                    ->classifications()
                    ->pluck('member_classifications.id')
                    ->all()
            );
            $application->status = 'approved';
            $application->reviewed_by = $fieldOfficer->id;
            $application->reviewed_at = Carbon::now();
            $application->rejection_reason = null;
            $application->save();
            $this->recordApplicationAction($fieldOfficer, $application, 'APPROVE MEMBER APPLICATION');
            $this->positions->recordOfficerChange($fieldOfficer, $member, null, 'APPOINT ASSOCIATION OFFICER');

            return $member;
        });
    }

    // Save the reason and record the rejection together.
    public function reject(
        MemberApplication $application,
        UserProfile $fieldOfficer,
        string $reason
    ): MemberApplication {
        return DB::transaction(function () use (
            $application,
            $fieldOfficer,
            $reason
        ) {
            [$application, $fieldOfficer] = $this->lockForReview($application, $fieldOfficer);

            // Rejecting also requires a completed association endorsement for public applications.
            if ($application->status === 'pending' && $application->submission_source === 'public_gis' && $application->endorsement_stage !== 'endorsed') {
                throw ValidationException::withMessages(['application' => 'The association must endorse this application before Field Officer review.'])->status(409);
            }
            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'application' =>
                        'This application has already been reviewed. Refresh the list before making another decision.',
                ])->status(409);
            }

            $reason = trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages([
                    'rejection_reason' =>
                        'A rejection reason is required.',
                ]);
            }

            $application->status = 'rejected';
            $application->reviewed_by = $fieldOfficer->id;
            $application->reviewed_at = Carbon::now();
            $application->rejection_reason = $reason;

            $application->save();
            $this->recordApplicationAction($fieldOfficer, $application, 'REJECT MEMBER APPLICATION');

            return $application;
        });
    }

    // Association decisions are serialized with account replacement and other reviews.
    public function associationReview(MemberApplication $application, UserProfile $currentUser, string $action, string $note): void
    {
        $note = trim($note);
        if (!in_array($action, ['endorse', 'decline'], true) || $note === '' || mb_strlen($note) > 1000) {
            throw ValidationException::withMessages(['note' => 'Enter a reason of up to 1,000 characters.']);
        }
        DB::transaction(function () use ($application, $currentUser, $action, $note) {
            $role = Role::whereKey($currentUser->role_id)->where('code', 'association_account')->sharedLock()->first();
            abort_unless($role, 403);
            $this->lifecycle->lockOperational([$application->association_id]);
            $actor = UserProfile::whereKey($currentUser->id)->lockForUpdate()->first();
            abort_unless($actor && (string) $actor->role_id === (string) $role->id && $actor->getRawOriginal('auth_sessions_valid_after') === $currentUser->getRawOriginal('auth_sessions_valid_after'), 403);
            $actor->setRelation('role', $role);
            $this->ensureAssociationAccountCanSubmit($actor);
            $link = AssociationAccount::where('user_id', $actor->id)->where('association_id', $application->association_id)->where('is_active', true)->lockForUpdate()->first();
            abort_unless($link, 403);
            $locked = MemberApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->association_id === $link->association_id, 403);
            if ($locked->submission_source !== 'public_gis' || $locked->status !== 'pending' || !in_array($locked->endorsement_stage, [null, 'returned'], true)) {
                throw ValidationException::withMessages(['application' => 'This application is no longer awaiting association review.'])->status(409);
            }
            $locked->endorsement_stage = $action === 'endorse' ? 'endorsed' : 'declined';
            $locked->save();
            $this->recordReviewEvent($locked, $actor, $action, $note);
        });
    }

    public function returnToAssociation(MemberApplication $application, UserProfile $fieldOfficer, string $note): void
    {
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 1000) throw ValidationException::withMessages(['note' => 'Enter a reason of up to 1,000 characters.']);
        DB::transaction(function () use ($application, $fieldOfficer, $note) {
            [$locked, $actor] = $this->lockForReview($application, $fieldOfficer);
            if ($locked->submission_source !== 'public_gis' || $locked->status !== 'pending' || $locked->endorsement_stage !== 'endorsed') {
                throw ValidationException::withMessages(['application' => 'This application is no longer awaiting Field Officer review.'])->status(409);
            }
            $locked->endorsement_stage = 'returned';
            $locked->save();
            $this->recordReviewEvent($locked, $actor, 'return', $note);
        });
    }

    private function recordReviewEvent(MemberApplication $application, UserProfile $actor, string $action, string $note): void
    {
        // Keep each note and reviewer even when an application is returned and endorsed again.
        MemberApplicationReviewEvent::create(['member_application_id' => $application->id, 'actor_id' => $actor->id,
            'actor_name' => $actor->display_name, 'action' => $action, 'note' => $note, 'created_at' => now()]);
        $this->recordApplicationAction($actor, $application, strtoupper($action).' MEMBER APPLICATION');
    }

    // Keep a simple history of each decision without copying private applicant details.
    private function recordApplicationAction(UserProfile $actor, MemberApplication $application, string $action): void
    {
        $request = new Request;
        $request->attributes->set('current_user', $actor);
        $this->audit->record($request, $action, 'Member Applications', 'member_application', $application->id,
            null, ['association_id' => $application->association_id, 'status' => $application->status],
            ['association_id', 'status']);
    }

    // Recheck the officer and association before either decision.
    private function lockForReview(
        MemberApplication $application,
        UserProfile $fieldOfficer
    ): array {
        // Use the same role -> profile -> association -> child order as officer management.
        $fieldOfficer = $this->positions->lockActor($fieldOfficer, 'field_officer');
        $association = Association::whereKey($application->association_id)->lockForUpdate()->firstOrFail();
        $application = MemberApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
        if ($application->association_id !== $association->id) {
            throw ValidationException::withMessages([
                'application' => 'The application association changed. Refresh before making a decision.',
            ])->status(409);
        }
        if ($association->primary_field_officer_id !== $fieldOfficer->id) {
            throw ValidationException::withMessages([
                'reviewer' =>
                    'This application does not belong to an association assigned to this Field Officer.',
            ]);
        }
        $this->lifecycle->assertOperational($association);

        return [$application, $fieldOfficer];
    }

    // Check the requested position and any extra description.
    private function validatePositionOtherText(
        array $data
    ): AssociationPosition {
        $position = AssociationPosition::query()
            ->whereKey($data['position_id'])
            ->where('is_active', true)
            ->first();

        if (! $position) {
            throw ValidationException::withMessages([
                'position_id' =>
                    'The selected position is invalid or inactive.',
            ]);
        }

        if (
            $position->code === 'OTHER' &&
            empty($data['position_other_text'])
        ) {
            throw ValidationException::withMessages([
                'position_other_text' =>
                    'Please specify the member position.',
            ]);
        }

        return $position;
    }

    private function resolvePositionOtherText(
        array $data
    ): ?string {
        $position = AssociationPosition::query()
            ->find($data['position_id']);

        if ($position?->code !== 'OTHER') {
            return null;
        }

        return $data['position_other_text'] ?? null;
    }

    // Prevent the same FishR number from being used twice.
    private function ensureFishRAvailable(
        ?string $fishRNumber
    ): void {
        if ($fishRNumber === null) {
            return;
        }

        $existsInMembers = Member::query()
            ->where(
                'fishr_registration_number',
                $fishRNumber
            )
            ->exists();

        if ($existsInMembers) {
            throw ValidationException::withMessages([
                'fishr_registration_number' =>
                    'This FishR registration number already belongs to an existing member.',
            ]);
        }

        $existsInApplications =
            MemberApplication::query()
                ->where(
                    'fishr_registration_number',
                    $fishRNumber
                )
                ->whereIn(
                    'status',
                    ['pending', 'approved']
                )
                ->exists();

        if ($existsInApplications) {
            throw ValidationException::withMessages([
                'fishr_registration_number' =>
                    'This FishR registration number already has a pending or approved application.',
            ]);
        }
    }
}
