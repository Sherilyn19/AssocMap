<?php

namespace App\Http\Controllers\FieldOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRegisterRequest;
use App\Services\Members\MemberApplicationService;
use App\Models\MemberApplication;
use App\Services\Members\MemberDirectoryQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\MemberApplicationReturn;
use Illuminate\Validation\ValidationException;

class MemberApplicationController extends Controller
{
    // Show applications assigned to this field officer.
    public function index(MemberRegisterRequest $request, MemberDirectoryQueryService $directory): View
    {
        $currentUser = $request->attributes->get('current_user');
        $filters = $request->filters();
        $statusFilter = $filters['status'];
        $associations = $directory->associations($currentUser, true);
        if ($filters['association'] !== '') abort_unless($associations->contains('id', $filters['association']), 404);
        $scope = MemberApplication::query()->useWritePdo()->where(fn ($q) => $q->where('submission_source', '!=', 'public_gis')
            ->orWhere('status', '!=', 'pending')->orWhere('endorsement_stage', 'endorsed'))->whereHas('association',
            fn ($query) => $query->where('primary_field_officer_id', $currentUser->id));
        $summary = $directory->applicationSummary($scope);
        $applications = $directory->applications($scope, $filters, true, $summary);

        return view('field-officer.member-applications.index', compact('applications', 'statusFilter', 'filters', 'associations', 'summary'));
    }

    public function returnToAssociation(Request $request, MemberApplication $memberApplication, MemberApplicationService $service): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $service->returnToAssociation($memberApplication, $request->attributes->get('current_user'), $data['note']);
        return redirect()->route('field-officer.member-applications.index')->with('success', 'Application returned to the association.');
    }

    // Approve the application and return to the same list.
    public function approve(
        Request $request,
        MemberApplication $memberApplication,
        MemberApplicationService $memberApplicationService
    ): RedirectResponse {
        $currentUser = $request->attributes->get('current_user');

        try {
            // Require an explicit confirmation even when a request bypasses the dialog.
            $request->validate(['decision_confirmed' => ['accepted']]);
            $member = $memberApplicationService->approve($memberApplication, $currentUser);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'));
        }

        // Offer contact options only after approval has committed successfully.
        return redirect()->to(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'))
            ->with('approved_contact', ['name' => trim($member->first_name.' '.$member->last_name), 'phone' => $member->contact_number, 'email' => $member->email, 'decision' => 'approved'])
            ->with(
                'success',
                'Member application approved successfully.'
            );
    }

    // Check the reason, reject the application, and return to the list.
    public function reject(
        Request $request,
        MemberApplication $memberApplication,
        MemberApplicationService $memberApplicationService
    ): RedirectResponse {
        try {
            $request->validate([
                'rejection_reason' => ['required', 'string', 'max:1000'],
                'decision_confirmed' => ['accepted'],
            ]);

            $currentUser = $request->attributes->get('current_user');

            $reviewed = $memberApplicationService->reject(
                $memberApplication,
                $currentUser,
                $request->string('rejection_reason')->toString()
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'));
        }

        // Show the completed decision in the Rejected section without stale list filters.
        return redirect()->route('field-officer.member-applications.index', ['status' => 'rejected'])
            ->with('approved_contact', ['name' => trim($reviewed->first_name.' '.$reviewed->last_name), 'phone' => $reviewed->contact_number, 'email' => $reviewed->email, 'decision' => 'rejected'])
            ->with(
                'success',
                'Member application rejected.'
            );
    }
}
