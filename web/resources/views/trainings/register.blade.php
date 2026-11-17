@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.field-officer')
@php
    $workflow = app(\App\View\WorkflowNavigation::class);
    $module = 'trainings';
    $moduleLabel = 'Trainings';
    $trainingPrefix = $prefix.'.trainings';
    $statuses = ['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $cards = ['current' => ['Current Trainings', '', 'graduation'], 'scheduled' => ['Scheduled', 'scheduled', 'calendar'], 'completed' => ['Completed', 'completed', 'check'], 'cancelled' => ['Cancelled', 'cancelled', 'close'], 'archived' => ['Archived Records', 'archived', 'archive']];
@endphp
@section('content')
<section class="program-management" data-workspace-module="trainings" data-workspace-title="Training Management" data-project-actions>
    <header class="card program-heading"><div>@if($prefix !== 'admin')<p class="eyebrow">Field Officer</p>@endif<h1 tabindex="-1">Training Management</h1><p>{{ $prefix === 'admin' ? 'Manage training schedules, participants, and attendance records.' : 'Manage training and participation within your active assigned associations.' }}</p></div>@if($prefix !== 'admin')<a class="button button-primary" href="{{ route($trainingPrefix.'.index', $filters + ['editor' => 'create']) }}"><x-icon name="plus" />Add Training</a>@endif</header>
    @if($prefix === 'admin')<div class="program-heading-actions project-heading-actions"><a class="button button-primary" href="{{ route($trainingPrefix.'.index', $filters + ['editor' => 'create']) }}"><x-icon name="plus" />Add Training</a></div>@endif
    @include('program-management.partials.summary')
    <section class="card program-register" aria-labelledby="training-register-title">
        <div class="program-section-heading"><div><div class="program-heading-title"><h2 id="training-register-title">Training register</h2>@if($prefix === 'admin')<x-area-info label="About participant counts">Participant counts include retained participation history.</x-area-info>@endif</div><p>{{ number_format($trainings->total()) }} matching training(s)</p></div>@if($prefix !== 'admin')<p>Participant counts include retained participation history.</p>@endif</div>
        @include('program-management.partials.filters')
        <div class="table-scroll" role="region" aria-label="Training register" tabindex="0"><table class="data-table program-register-table"><caption class="sr-only">Authorized training activities, schedules and participation counts</caption><thead><tr><th scope="col">Training</th><th scope="col">Association</th><th scope="col">Schedule</th><th scope="col">Participants</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>
            @forelse($trainings as $training)
                @php
                    $canManage = $training->association && $training->association->status === 'active' && !$training->association->archived_at
                        && !($training->relatedProject?->isFinalized() ?? false)
                        && (!$training->relatedProject || ($training->relatedProject->association && $training->relatedProject->association->status === 'active' && !$training->relatedProject->association->archived_at));
                @endphp
                <tr><th scope="row"><a href="{{ $workflow->to($trainingPrefix.'.show', [$training]) }}">{{ $training->title }}</a><small>{{ $training->saadActivity?->name ?? 'Activity unavailable' }}</small>@if($training->relatedProject)<small>Project: {{ $training->relatedProject->title }}</small>@endif</th>
                    <td><a href="{{ $workflow->to($prefix.'.associations.show', ['association' => $training->association_id, 'section' => 'trainings']) }}">{{ $training->association?->name ?? 'Association unavailable' }}</a><small>{{ $training->association?->locationBarangay()?->name ?? '—' }} · {{ $training->association?->locationBarangay()?->municipality?->name ?? '—' }}</small></td>
                    {{-- Show the existing schedule with a simple timeline marker for admins. --}}
                    <td @class(['admin-timeline-date' => $prefix === 'admin'])>@if($prefix === 'admin')<span class="admin-timeline-marker" aria-hidden="true"></span>@endif{{ $training->start_date?->format('M j, Y') ?? 'Not recorded' }}@if($training->end_date)<small>Until {{ $training->end_date->format('M j, Y') }}</small>@endif<small>Program year {{ $training->program_year }}@if($training->venue) · {{ $training->venue }}@endif</small></td>
                    <td>{{ number_format($training->participants_count) }}</td><td><x-status-badge :value="ucfirst($training->status)" />@if($training->archived_at)<span class="badge badge-neutral">Archived</span>@endif</td>
                    <td class="project-actions-cell"><details class="project-actions" data-user-actions><summary aria-label="Actions for {{ $training->title }}"><x-icon name="ellipsis" /></summary><div class="project-actions-panel" data-user-actions-panel><a href="{{ $workflow->to($trainingPrefix.'.show', [$training]) }}"><x-icon name="eye" />View Training</a>@if($canManage)@if(!$training->archived_at)<a href="{{ route($trainingPrefix.'.edit', $training) }}"><x-icon name="pencil" />Edit Training</a>@endif<button type="button" class="{{ $training->archived_at ? '' : 'project-action-danger' }}" data-dialog="training-action-{{ $training->id }}"><x-icon :name="$training->archived_at ? 'archive-restore' : 'archive'" />{{ $training->archived_at ? 'Unarchive Training' : 'Archive Training' }}</button>@else<p class="program-action-note">{{ $training->relatedProject?->isFinalized() ? 'Read-only: project finalized' : 'Read-only: association unavailable or inactive' }}</p>@endif</div></details></td></tr>
            @empty<tr><td colspan="6"><x-empty-state title="No matching trainings" message="Try another search or filter, or reset to the current training register." /></td></tr>@endforelse
        </tbody></table></div>
        @include('program-management.partials.pagination', ['paginator' => $trainings, 'label' => 'trainings', 'projectRegister' => $prefix === 'admin'])
    </section>
</section>
@foreach($trainings as $training)
    @if($training->association && $training->association->status === 'active' && !$training->association->archived_at && !($training->relatedProject?->isFinalized() ?? false) && (!$training->relatedProject || ($training->relatedProject->association && $training->relatedProject->association->status === 'active' && !$training->relatedProject->association->archived_at)))
        <x-modal :id="'training-action-'.$training->id" :title="$training->archived_at ? 'Unarchive Training' : 'Archive Training'">
            <p>{{ $training->archived_at ? 'Restore' : 'Archive' }} <strong>{{ $training->title }}</strong>? {{ $training->archived_at ? 'The training will return to the current list.' : 'Its details and attendance will remain available for viewing.' }}</p>
            <form method="POST" action="{{ route($trainingPrefix.($training->archived_at ? '.unarchive' : '.archive'), $training) }}">@csrf @method('PATCH')<div class="modal-actions"><button type="button" class="button" data-close-dialog>Cancel</button><button type="submit" class="button {{ $training->archived_at ? 'button-primary' : 'button-danger' }}">{{ $training->archived_at ? 'Unarchive Training' : 'Archive Training' }}</button></div></form>
        </x-modal>
    @endif
@endforeach
@if($showEditor)
    <x-modal id="training-editor" title="Add Training"><div class="program-editor">@include('trainings.partials.form')</div></x-modal>
@endif
@endsection
