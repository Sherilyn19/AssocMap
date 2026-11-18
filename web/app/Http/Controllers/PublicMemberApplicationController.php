<?php

namespace App\Http\Controllers;

use App\Exceptions\MemberApplicationPhotoUnavailableException;
use App\Models\AssociationPosition;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\SexCategory;
use App\Services\GIS\GisQueryService;
use App\Services\Members\PublicApplicationSiteService;
use App\Services\Members\MemberApplicationPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PublicMemberApplicationController extends Controller
{
    // Resolve the location through the same publication rules used by the public map.
    private function location(GisQueryService $gis, string $id): mixed
    {
        return $gis->scoped(null)->where('g.id', $id)
            ->select('g.id', 'g.public_title', 'a.id as application_association_id', 'a.name as application_association_name', 'a.primary_field_officer_id')
            ->firstOrFail();
    }

    public function guide(): mixed
    {
        return view('public.how-to-apply');
    }

    public function create(string $location, GisQueryService $gis): mixed
    {
        return response()->view('public.member-application', [
            'site' => $this->location($gis, $location),
            'sexCategories' => SexCategory::where('is_active', true)->orderBy('name')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, string $location, GisQueryService $gis, PublicApplicationSiteService $sites, MemberApplicationPhotoService $photos): mixed
    {
        // Validate the photo and acknowledgement before storing any applicant information.
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'], 'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'], 'suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sex_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('sex_categories', 'id')->where('is_active', true)],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){7})[0-9+() .-]{7,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'reason_for_applying' => ['required', 'string', 'max:5000', function ($attribute, $value, $fail) {
                if (\App\Support\ApplicationReason::wordCount($value) > 500) $fail('The reason must not exceed 500 words.');
            }],
            'applicant_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png',
                'extensions:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=2048,max_height=2048'],
            // The hidden website field catches simple automated submissions.
            'acknowledgement' => ['accepted'], 'website' => ['prohibited'],
        ]);
        $site = $this->location($gis, $location);
        $applicationId = (string) Str::uuid();
        $photoPath = null;
        $callbackCompleted = false;
        try {
            DB::transaction(function () use ($data, $site, $gis, $sites, $location, $photos, $applicationId, &$photoPath, &$callbackCompleted) {
                // Follow the GIS lock order so publication or parent changes cannot race submission.
                $lockedLocation = $sites->lock($location);
                $association = $lockedLocation->project->association;
                $current = $this->location($gis, $location);
                $officerAvailable = DB::table('user_profiles')->join('roles', 'roles.id', '=', 'user_profiles.role_id')
                    ->where('user_profiles.id', $association->primary_field_officer_id)->where('user_profiles.is_active', true)
                    ->where('roles.code', 'field_officer')->where('roles.is_active', true)->exists();
                if ($current->application_association_id !== $association->id || $site->application_association_id !== $association->id || ! $officerAvailable) {
                    throw ValidationException::withMessages(['application' => 'This location is not currently available for applications. Please return to the map.']);
                }
                // The association lock serializes identity checks with other membership submissions.
                foreach ([Member::query(), MemberApplication::query()] as $query) {
                    $query->where('association_id', $association->id)->whereDate('birth_date', $data['birth_date']);
                    foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
                        $value = mb_strtolower(preg_replace('/\s+/u', ' ', trim($data[$field] ?? '')));
                        $query->whereRaw("COALESCE(NULLIF(LOWER(REGEXP_REPLACE(BTRIM({$field}), '\\s+', ' ', 'g')), ''), '') = ?", [$value]);
                    }
                    if ($query->exists()) throw ValidationException::withMessages(['application' => 'An application with these details may already exist. Please contact the assigned Field Officer for assistance.']);
                }
                // Public applicants request ordinary membership, not an officer position.
                $position = AssociationPosition::where('code', 'MEMBER')->where('is_active', true)->first();
                if (! $position) throw ValidationException::withMessages(['application' => 'Applications are temporarily unavailable. Please try again later.']);
                $photoPath = $photos->store($data['applicant_photo'], $applicationId);
                $application = new MemberApplication(collect($data)->only(['first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'sex_id', 'contact_number', 'email', 'reason_for_applying'])->all() + [
                    'association_id' => $association->id, 'submitted_by_user_id' => null, 'submission_source' => 'public_gis',
                    'public_location_id' => $current->id, 'public_location_title' => $current->public_title,
                    'position_id' => $position->id, 'status' => 'pending', 'applicant_photo_path' => $photoPath,
                ]);
                $application->id = $applicationId;
                $application->save();
                // Record the public submission without inventing a staff actor or logging personal details.
                DB::table('audit_logs')->insert(['id' => (string) Str::uuid(), 'actor_user_id' => null,
                    'action_type' => 'SUBMIT PUBLIC MEMBER APPLICATION', 'module' => 'Member Applications',
                    'entity_type' => 'member_application', 'entity_id' => $applicationId, 'performed_at' => now()]);
                $callbackCompleted = true;
            });
        } catch (Throwable $exception) {
            // Remove a confirmed upload only when the transaction failed before commit.
            if (! $callbackCompleted && DB::transactionLevel() === 0 && $photoPath !== null) $photos->discard($photoPath, $applicationId);
            if ($exception instanceof MemberApplicationPhotoUnavailableException) {
                return back()->withInput($request->except('applicant_photo', 'website'))->withErrors(['applicant_photo' => 'The photo could not be stored. Please try again later.']);
            }
            throw $exception;
        }
        return redirect()->route('public.application.received')->with('public_application_received', true);
    }

    public function received(Request $request): mixed
    {
        // Show the receipt only immediately after a successful submission.
        abort_unless($request->session()->get('public_application_received'), 404);
        return response()->view('public.application-received')->header('Cache-Control', 'private, no-store');
    }
}
