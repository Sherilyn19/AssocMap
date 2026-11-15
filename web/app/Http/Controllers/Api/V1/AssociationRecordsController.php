<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Training;
use App\Services\Auth\MobileAssociationAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AssociationRecordsController extends Controller
{
    public function members(Request $request, MobileAssociationAccessService $access): JsonResponse
    {
        $this->validatePage($request);
        $associationId = $request->attributes->get('mobile_association_id');
        // Official records are separate from applications; legacy members need no application link.
        $page = Member::query()->useWritePdo()->where('association_id', $associationId)
            ->where('member_status', 'active')->whereNull('archived_at')
            ->orderByRaw('LOWER(TRIM(last_name))')->orderByRaw('LOWER(TRIM(first_name))')
            ->orderByRaw("LOWER(COALESCE(middle_name, ''))")->orderByRaw("LOWER(COALESCE(suffix, ''))")
            ->orderBy('id')->paginate(25, ['id', 'first_name', 'middle_name', 'last_name', 'suffix']);
        $rows = $page->getCollection()->map(fn (Member $member) => ['name' => trim($member->last_name).', '.
            implode(' ', array_filter(array_map(fn ($part) => trim($part ?? ''),
                [$member->first_name, $member->middle_name, $member->suffix]), fn ($part) => $part !== ''))]);

        return $this->response($request, $access, $page, 'members', $rows->all());
    }

    public function trainings(Request $request, MobileAssociationAccessService $access): JsonResponse
    {
        $this->validatePage($request);
        $associationId = $request->attributes->get('mobile_association_id');
        // Match the Association Account web register, including retained training history.
        $page = Training::query()->useWritePdo()->where('association_id', $associationId)
            ->with(['relatedProject' => fn ($query) => $query->useWritePdo()->where('association_id', $associationId)
                ->select(['id', 'title'])])
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')->orderByDesc('start_date')
            ->orderByRaw('LOWER(title)')->orderBy('id')
            ->paginate(25, ['id', 'related_project_id', 'title', 'description', 'program_year',
                'start_date', 'end_date', 'venue', 'conducted_by', 'status', 'archived_at']);
        $rows = $page->getCollection()->map(fn (Training $training) => [
            'id' => $training->id, 'title' => $training->title, 'description' => $training->description,
            'program_year' => $training->program_year, 'start_date' => $training->start_date?->toDateString(),
            'end_date' => $training->end_date?->toDateString(), 'venue' => $training->venue,
            'conducted_by' => $training->conducted_by, 'status' => $training->status,
            'is_archived' => $training->archived_at !== null, 'related_project' => $training->relatedProject?->title,
        ]);

        return $this->response($request, $access, $page, 'trainings', $rows->all());
    }

    private function validatePage(Request $request): void
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'association_id' => ['prohibited'], 'user_id' => ['prohibited']]);
    }

    private function response(Request $request, MobileAssociationAccessService $access,
        LengthAwarePaginator $page, string $key, array $rows): JsonResponse
    {
        // Recheck the current account/link after reading, before releasing any records.
        $context = $access->requireAssociation($request->attributes->get('supabase_identity'));
        $associationId = $request->attributes->get('mobile_association_id');
        abort_unless($context['mobile']['association']['id'] === $associationId, 403);

        return response()->json(['data' => ['association_id' => $associationId, $key => $rows,
            'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }
}
