@extends('layouts.admin')

@section('content')
<section class="user-management user-assignments" data-workspace-module="users" data-workspace-title="Field Officer assignments">
    <div class="card user-page-heading">
        @include('partials.page-header', ['title' => 'Association assignments', 'description' => $officer->display_name, 'eyebrow' => 'Field Officer'])
    </div>
    <x-workflow-navigation :back="['label' => 'Account Details', 'url' => route('admin.users.show', $officer->id)]" :parents="[['label' => 'User Management', 'url' => route('admin.users.index')], ['label' => $officer->display_name, 'url' => route('admin.users.show', $officer->id)]]" current="Association assignments" />
    @if($errors->any())<div class="notice notice-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
    <section class="card user-register" aria-labelledby="current-assignments-title">
        <header class="user-register-heading"><div><h2 id="current-assignments-title"><x-icon name="building" /> Current assignments ({{ $assigned->total() }})</h2><p>Associations currently assigned to this Field Officer, including historical associations.</p></div></header>
        <div class="table-scroll" tabindex="0" role="region" aria-label="Current assignments"><table class="data-table"><caption class="sr-only">Current primary association assignments.</caption><thead><tr><th scope="col">Association</th><th scope="col">Status</th><th scope="col">History</th></tr></thead><tbody>
            @forelse($assigned as $association)<tr><th scope="row">{{ $association->name }}</th><td><x-status-badge :value="ucfirst($association->status)" /></td><td>{{ $association->archived_at ? 'Archived · read-only' : 'Not archived' }}</td></tr>@empty<tr><td colspan="3"><x-empty-state title="No current association assignments" message="This Field Officer has no current primary assignments." /></td></tr>@endforelse
        </tbody></table></div>
        <div class="table-footer user-table-footer"><span>Page {{ $assigned->currentPage() }} of {{ $assigned->lastPage() }}</span><nav aria-label="Current assignment pages">@if($assigned->previousPageUrl())<a href="{{ $assigned->previousPageUrl() }}" rel="prev">Previous</a>@endif @if($assigned->nextPageUrl())<a href="{{ $assigned->nextPageUrl() }}" rel="next">Next</a>@endif</nav></div>
    </section>
    <div class="notice"><p>Each association requires one primary Field Officer. Selecting an association transfers that primary assignment to {{ $officer->display_name }}. Unselected associations and Association Account links stay unchanged. To remove an assignment from this officer, open another eligible officer and transfer it there. Archived or inactive associations are read-only.</p></div>
    <form method="POST" action="{{ route('admin.users.assignments.update', $officer->id, false) }}" data-feedback-error-bag="default">
        @csrf
        @method('PATCH')
        <section class="card user-register" aria-labelledby="transfer-assignments-title">
            <header class="user-register-heading"><div><h2 id="transfer-assignments-title"><x-icon name="building" /> Select associations to transfer</h2><p>Choose active associations to assign to {{ $officer->display_name }}.</p></div></header>
            <div class="user-assignment-help"><p>Save selections on this page before moving to another page. Associations already assigned to this officer are checked and read-only.</p>
            <p>Only active, nonarchived associations can receive assignment changes. Reactivate an association through Association Management before changing its primary officer.</p></div>
            <div class="table-scroll" tabindex="0" role="region" aria-label="Eligible associations"><table class="data-table"><caption class="sr-only">Active, nonarchived associations, page {{ $choices->currentPage() }} of {{ $choices->lastPage() }}.</caption>
                <thead><tr><th scope="col">Select</th><th scope="col">Association</th><th scope="col">Status</th><th scope="col">Current primary officer</th></tr></thead>
                <tbody>@forelse($choices as $association)
                    @php($alreadyAssigned = $association->primary_field_officer_id === $officer->id)
                    <tr>
                        <td><input type="checkbox" id="assign-{{ $association->id }}" name="association_ids[]" value="{{ $association->id }}" @checked($alreadyAssigned) @disabled($alreadyAssigned)>
                            @if(!$alreadyAssigned)<input type="hidden" name="expected_officers[{{ $association->id }}]" value="{{ $association->primary_field_officer_id }}">@endif
                        </td>
                        <td><label for="assign-{{ $association->id }}">{{ $association->name }}</label></td>
                        <td>{{ ucfirst($association->status) }}</td>
                        <td>{{ $association->primaryFieldOfficer?->display_name ?? 'User unavailable' }}{{ $alreadyAssigned ? ' (already assigned)' : '' }}</td>
                    </tr>
                @empty<tr><td colspan="4">No active, nonarchived associations on this page.</td></tr>@endforelse</tbody>
            </table></div>
            <div class="table-footer user-table-footer"><span>Page {{ $choices->currentPage() }} of {{ $choices->lastPage() }}</span><nav aria-label="Eligible association pages">@if($choices->previousPageUrl())<a href="{{ $choices->previousPageUrl() }}" rel="prev">Previous</a>@endif @if($choices->nextPageUrl())<a href="{{ $choices->nextPageUrl() }}" rel="next">Next</a>@endif</nav></div>
            <div class="user-assignment-actions"><label><input type="checkbox" name="confirm_reassignment" value="1" required> <span>I confirm that the selected associations will use this Field Officer as their primary officer.</span></label>
            <div class="form-actions"><a class="button" href="{{ route('admin.users.show', $officer->id) }}">Cancel</a><button class="button button-primary" type="submit">Save assignments</button></div></div>
        </section>
    </form>
</section>
@endsection
