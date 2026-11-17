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
        $scope = MemberApplication::query()->useWritePdo()->whereHas('association',
            fn ($query) => $query->where('primary_field_officer_id', $currentUser->id));
        $summary = $directory->applicationSummary($scope);
        $applications = $directory->applications($scope, $filters, true, $summary);

        return view('field-officer.member-applications.index', compact('applications', 'statusFilter', 'filters', 'associations', 'summary'));
    }

    // Approve the application and return to the same list.
    public function approve(
        Request $request,
        MemberApplication $memberApplication,
        MemberApplicationService $memberApplicationService
    ): RedirectResponse {
        $currentUser = $request->attributes->get('current_user');

        try {
            $memberApplicationService->approve($memberApplication, $currentUser);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'));
        }

        return redirect()->to(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'))
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
            ]);

            $currentUser = $request->attributes->get('current_user');

            $memberApplicationService->reject(
                $memberApplication,
                $currentUser,
                $request->string('rejection_reason')->toString()
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'));
        }

        return redirect()->to(MemberApplicationReturn::url($request, 'field-officer.member-applications.index'))
            ->with(
                'success',
                'Member application rejected.'
            );
    }
}
