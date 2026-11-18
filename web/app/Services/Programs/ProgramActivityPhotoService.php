<?php

namespace App\Services\Programs;

use App\Models\Association;
use App\Models\MediaPhoto;
use App\Models\ProgramActivityRecord;
use App\Models\Role;
use App\Models\UserProfile;
use App\Services\Media\PhotoRules;
use App\Services\Projects\ProjectLifecycleService;
use App\Services\Reporting\ReportScope;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProgramActivityPhotoService
{
    public function __construct(private ProgramActorService $actors, private ReportScope $scope, private ProjectLifecycleService $projects) {}

    public function parent(string $id): ProgramActivityRecord
    {
        $parent = new ProgramActivityRecord;
        $parent->setRawAttributes(['id' => $id], true);
        return $parent;
    }

    public function authorize(Request $request, string $id, bool $write): Closure
    {
        return function (Model $parent) use ($request, $id, $write): ProgramActivityRecord {
            abort_unless($parent instanceof ProgramActivityRecord && $parent->id === $id, 404);
            $previous = $request->attributes->get('current_user');
            abort_unless($previous instanceof UserProfile, 403);
            if ($previous->role?->code === 'association_account') {
                abort_if($write, 403);
                $role = Role::where('code', 'association_account')->sharedLock()->first();
                $actor = UserProfile::whereKey($previous->id)->sharedLock()->first();
                abort_unless($role && $actor && (string) $actor->role_id === (string) $role->id, 403);
                $actor->setRelation('role', $role);
                abort_unless($actor->canAccessApplication(throwOnDatabaseError: true)
                    && $actor->getRawOriginal('auth_sessions_valid_after') === $previous->getRawOriginal('auth_sessions_valid_after'), 403);
            } else {
                $actor = $this->actors->lock($previous);
            }
            $snapshot = ProgramActivityRecord::whereKey($id)->firstOrFail();
            if ($snapshot->association_id) Association::whereKey($snapshot->association_id)->lockForUpdate()->firstOrFail();
            $activity = ProgramActivityRecord::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($snapshot->association_id === $activity->association_id && $snapshot->related_project_id === $activity->related_project_id, 409);
            if ($actor->role->code !== 'system_admin') {
                abort_unless($activity->association_id && $this->scope->associations($actor)->where('a.id', $activity->association_id)->exists(), 404);
            }
            if ($write) {
                abort_unless(in_array($actor->role->code, ['system_admin', 'field_officer'], true), 403);
                abort_if($activity->archived_at !== null, 409, 'Archived activities are read-only.');
                if ($activity->association_id) {
                    $association = Association::whereKey($activity->association_id)->firstOrFail();
                    abort_unless($association->status === 'active' && $association->archived_at === null, 409);
                }
                $this->projects->lockNotFinalized([$activity->related_project_id]);
            }
            $request->attributes->set('current_user', $actor);
            return $activity;
        };
    }

    public function gallery(ProgramActivityRecord $parent, Closure $authorize, bool $manager): array
    {
        return DB::transaction(function () use ($parent, $authorize, $manager) {
            $activity = $authorize($parent);
            $rows = MediaPhoto::where('program_activity_id', $activity->id)->where('state', '!=', 'deleted')->orderBy('display_order')->orderBy('id')->get();
            $canManage = $manager && $activity->archived_at === null;
            if ($activity->association_id) {
                $association = Association::find($activity->association_id);
                $canManage = $canManage && $association && $association->status === 'active' && $association->archived_at === null;
            }
            if ($activity->related_project_id) {
                $project = $activity->relatedProject;
                $canManage = $canManage && $project && $project->archived_at === null && ! $project->isFinalized();
            }
            return ['photos' => $rows->where('state', 'ready')->values()->map->toArray()->all(),
                'pending' => $manager ? $rows->where('state', '!=', 'ready')->values()->map(fn ($row) => [
                    'id' => $row->id, 'status' => $row->state,
                    'retry_after' => max(0, (int) ceil(now()->diffInSeconds($row->lease_until ?? now(), false))),
                ])->all() : [],
                'used_slots' => $rows->whereIn('state', PhotoRules::LIVE_STATES)->count(), 'limit' => 20, 'can_manage' => (bool) $canManage];
        });
    }

    public function photo(string $activity, string $id): MediaPhoto
    {
        return MediaPhoto::where('program_activity_id', $activity)->whereKey($id)->firstOrFail();
    }
}
