@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.field-officer')
@php
    $workflow = app(\App\View\WorkflowNavigation::class);
    $parameters += $workflow->carry(request());
    $related->appends($workflow->carry(request()));
    $workflowBack = $workflow->back(request(), $prefix.'.'.(($parameters['origin'] ?? '') === 'officers' ? 'association-officers.index' : 'associations.index'), $parameters,
        array_map(fn ($module) => ['name' => $prefix.'.'.$module.'.index', 'parameters' => [], 'label' => match ($module) { 'projects' => 'Projects', 'trainings' => 'Trainings', 'members' => 'Member Management', default => 'Program Activities' }], ['projects', 'trainings', 'program-activities', 'members']));
    $backUrl = $workflowBack['url'];
@endphp
@section('content')
{{-- Keep the officer detail layout separate from the admin view. --}}
<section @class(['association-management', 'officer-association-detail' => $prefix === 'field-officer']) data-association-ui data-workspace-module="associations" data-workspace-title="Association Details">
    @if($prefix === 'admin')<nav class="association-breadcrumbs" aria-label="Breadcrumb"><a href="{{ $backUrl }}">{{ $workflowBack['label'] }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ $association->name }}</span></nav>@endif
    <header class="card association-heading"><div>@if($prefix === 'admin')<p class="eyebrow">Association details</p>@endif<h1 tabindex="-1">{{ $association->name }}</h1><p>{{ $association->acronym ?? 'Association record' }}</p></div><div class="association-heading-actions"><x-status-badge :value="ucfirst($association->status)" />@if($association->archived_at)<span class="badge badge-neutral">Archived — Read Only</span>@endif @if($prefix === 'admin' && !$association->archived_at)<a class="button" href="{{ route('admin.associations.show', ['association' => $association->id] + $parameters + ['section' => $section, 'editor' => $association->id]) }}"><x-icon name="pencil" />Edit Association</a>@endif<a class="button button-primary" href="{{ $workflow->to($prefix.'.association-officers.show', [$association->id], 'related-association-records') }}">{{ $association->archived_at || $association->status !== 'active' ? 'View officers' : 'Manage officers' }}</a></div>
        <x-contextual-report :prefix="$prefix" type="associations" :association="$association"
            :filters="['association_id' => $association->id]"
            :show-scope="$prefix !== 'field-officer'"
            :scope="'Selected association: '.$association->name.'. Current snapshot; no reporting period. Distribution and status only; members, officers, contacts and private documents are not included. Register filters do not apply.'" />
    </header>
    @if($prefix === 'field-officer')<x-workflow-navigation :back="$workflowBack" :parents="[['label' => $workflowBack['label'], 'url' => $backUrl]]" :current="$association->name" />@endif
    @if($association->archived_at || $association->status !== 'active')<p class="notice">This association is inactive or archived. Operational changes remain restricted; existing historical references are preserved.</p>@endif
    <section class="card association-information" aria-labelledby="association-information-title">
        <div class="association-section-heading"><h2 id="association-information-title">@if($prefix === 'field-officer')<x-icon name="building" /> @endif Association information</h2></div>
        <dl class="association-info-grid">
            <div><dt>Association name</dt><dd>{{ $association->name }}</dd></div><div><dt>Acronym</dt><dd>{{ $association->acronym ?? 'Not recorded' }}</dd></div><div><dt>SAAD Phase II joining</dt><dd>{{ $association->saad_phase2_join_date?->format('M d, Y') ?? 'Not recorded' }}</dd></div><div><dt>Created</dt><dd>{{ $association->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'Not recorded' }}</dd></div><div><dt>Primary Field Officer</dt><dd>{{ $association->primaryFieldOfficer?->display_name ?? 'Not available' }}</dd></div><div><dt>President @if($prefix === 'field-officer')<x-area-info label="About the president">The president shown is an active member whose record is not archived. Registration records are listed below.</x-area-info>@endif</dt><dd>@include('associations.partials.person-name', ['person' => $association->currentPresident])</dd></div>
        </dl>
        @if($prefix === 'admin')<p class="association-section-note">The president is the recorded active, nonarchived member holding the President position. Registration records are available below.</p>@endif
    </section>
    {{-- Keep location details and the gallery together for field officers. --}}
    <section @class(['card', 'association-information', 'officer-location-layout' => $prefix === 'field-officer' && $association->status === 'active' && !$association->archived_at]) aria-labelledby="association-location-title">
        @php($locationBarangay = $association->locationBarangay())
        <div class="association-section-heading"><h2 id="association-location-title">@if($prefix === 'field-officer')<x-icon name="map-pinned" /> @endif Geographic location</h2></div>
        <dl class="association-info-grid">
            <div><dt>City / Municipality</dt><dd>@if($prefix === 'admin' && $locationBarangay?->municipality)<a href="{{ route('admin.municipalities.show', $locationBarangay->municipality_id) }}">{{ $locationBarangay->municipality->name }}</a>@else{{ $locationBarangay?->municipality?->name ?? 'Not recorded' }}@endif</dd></div>
            <div><dt>Barangay</dt><dd>@if($prefix === 'admin' && $locationBarangay)<a href="{{ route('admin.barangays.show', $locationBarangay->id) }}">{{ $locationBarangay->name }}</a>@else{{ $locationBarangay?->name ?? 'Not recorded' }}@endif</dd></div>
            <div><dt>{{ app(\App\Services\Associations\AssociationGeographyService::class)->usesDirectGeography() ? 'Specific Area' : 'Recorded address / Sitio / Purok' }}</dt><dd>{{ $association->area?->address ?? 'Not recorded' }}</dd></div>
            @if(app(\App\Services\Associations\AssociationGeographyService::class)->usesDirectGeography())<div><dt>Association Address / Sitio / Purok</dt><dd>{{ $association->specific_address ?? 'Not recorded' }}</dd></div>@endif
        </dl>
        @if(($association->area && !$association->area->is_active) || ($locationBarangay && (!$locationBarangay->is_active || !$locationBarangay->municipality?->is_active)))<p class="association-section-note">This location includes inactive geographic references, retained for historical accuracy.</p>@endif
        {{-- Reuse the dashboard gallery for this association's published locations. --}}
        @if($prefix === 'field-officer' && $association->status === 'active' && !$association->archived_at)
            <div class="role-dashboard association-location-gallery">@include('dashboard.gis-gallery', ['galleryRoute' => 'field-officer.gis.index', 'galleryAssociationId' => $association->id])</div>
        @endif
    </section>
    <section class="card association-register" aria-labelledby="related-association-records">
        <div class="association-section-heading"><div><h2 id="related-association-records">@if($prefix === 'field-officer')<x-icon name="receipt" /> @endif Related records</h2>@if($prefix === 'admin')<p>Select a record group. Each list is paginated and limited to this association.</p>@endif</div></div>
        <nav class="association-related-tabs" aria-label="Related association records">
            @foreach(['members' => 'Members', 'projects' => 'Projects', 'trainings' => 'Trainings', 'activities' => 'Program Activities', 'registrations' => 'Registrations'] as $key => $label)
                @if(!$canViewActivities && in_array($key, ['trainings', 'activities']))<span aria-disabled="true" title="Existing access rules require an active, nonarchived association.">{{ $label }} (unavailable)</span>
                @else<a href="{{ route($prefix.'.associations.show', ['association' => $association->id] + $parameters + ['section' => $key]) }}" @if($section === $key) aria-current="page" @endif>{{ $label }}</a>@endif
            @endforeach
        </nav>
        @include('associations.partials.related')
        @include('associations.partials.pagination', ['paginator' => $related, 'label' => 'Related record pages'])
    </section>
    @if($prefix === 'admin')<a class="back-link" href="{{ $backUrl }}" data-workflow-back><x-icon name="back" />Back to {{ $workflowBack['label'] }}</a>@endif
</section>
@if($prefix === 'admin' && isset($catalog['options']['area_records'])) @include('admin.associations.editor') @endif
@endsection
