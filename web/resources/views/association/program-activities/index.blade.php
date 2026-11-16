@extends('layouts.association')
@php $workflow = app(\App\View\WorkflowNavigation::class); @endphp

@section('content')
<section class="program-management" data-workspace-module="association-program-activities" data-workspace-title="Program Activities" data-association-program-activities>
    <header class="card program-heading"><div><h1 tabindex="-1">Program Activities</h1><p>Explore your association's program activities and their details.</p></div></header>
    @if($unavailable ?? false)
        <section class="card program-information" role="alert"><h2>Program Activity information is temporarily unavailable</h2><p>Please try again later.</p><a class="button button-primary" href="{{ route('association.program-activities.index') }}">Retry Program Activities</a></section>
    @else
        @if($errors->any())<div class="notice notice-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="card program-register" aria-labelledby="association-activities-title">
            <div class="program-section-heading"><div><h2 id="association-activities-title">{{ $association->name }}</h2></div></div>
            <div class="member-management"><form method="GET" action="{{ route('association.program-activities.index') }}" class="member-register-filters" aria-label="Filter association Program Activities">
                <div class="member-search-bar"><div class="field member-search"><label for="activity-search">Search activities</label><div><x-icon name="search" /><input id="activity-search" name="search" type="search" value="{{ $filters['search'] }}" maxlength="120" placeholder="Search activity titles"></div></div><button type="submit" class="button button-primary">Search</button><a class="button" href="{{ route('association.program-activities.index') }}">Reset</a></div>
                <details class="member-filter-disclosure" @if(filled($filters['year']) || filled($filters['status'])) open @endif><summary class="button"><x-icon name="filter" />Filters <x-icon name="chevron-down" /></summary><div class="member-filter-panel"><div class="member-filter-grid">
                    <div class="field"><label for="activity-year">Program year</label><select id="activity-year" name="year"><option value="">All years</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) $filters['year'] === (string) $year)>{{ $year }}</option>@endforeach</select></div>
                    <div class="field"><label for="activity-status">Activity status / archive</label><select id="activity-status" name="status">@foreach(['' => 'All activities', 'planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'archived' => 'Archived records'] as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>
                <div class="member-filter-actions"><button type="submit" class="button button-primary">Apply filters</button><a class="button" href="{{ route('association.program-activities.index') }}">Reset filters</a></div></div></details>
            </form></div>
            <div class="table-scroll" role="region" aria-label="Association Program Activities directory" tabindex="0">
                <table class="data-table program-register-table"><caption class="sr-only">Program Activities belonging to your association</caption>
                    <thead><tr><th scope="col">Date</th><th scope="col">Activity</th><th scope="col">Venue</th><th scope="col">Status</th><th scope="col">Related project</th><th scope="col">Details</th></tr></thead>
                    <tbody>
                        @forelse($records as $activity)
                            <tr><td class="training-timeline-date"><span class="training-timeline-marker" aria-hidden="true"></span><span>{{ $activity->activity_date?->format('M j, Y') ?? 'Not recorded' }}</span></td>
                                <th scope="row"><a href="{{ $workflow->to('association.program-activities.show', [$activity->id]) }}">{{ $activity->title }}</a><small>Program year: {{ $activity->program_year }}</small></th>
                                <td>{{ filled($activity->venue) ? $activity->venue : 'Not recorded' }}</td>
                                <td><x-status-badge :value="ucfirst($activity->status)" />@if($activity->archived_at)<span class="badge badge-neutral">Archived</span>@endif</td>
                                <td>@if($activity->relatedProject)<a href="{{ route('association.projects.show', $activity->relatedProject->id) }}">{{ $activity->relatedProject->title }}</a>@else{{ $activity->related_project_id ? 'Not available within your association' : 'Not recorded' }}@endif</td>
                                <td><a class="button" href="{{ $workflow->to('association.program-activities.show', [$activity->id]) }}" aria-label="View details for {{ $activity->title }}"><x-icon name="eye" />View Details</a></td>
                            </tr>
                        @empty<tr><td colspan="6"><x-empty-state title="No Program Activities on this page" message="No activities match this page and its filters. Reset the filters to view your association's saved activities." /></td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            @include('program-management.partials.pagination', ['paginator' => $records, 'label' => 'activities'])
        </section>
    @endif
</section>
@endsection
