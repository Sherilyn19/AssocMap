@php
    $visitsActive = request()->routeIs($monitoringPrefix.'.visits', $monitoringPrefix.'.visit.*');
    $activeSection = $visitsActive ? 'visits' : ($section ?? 'overview');
    $sections = ['overview' => ['Overview', 'clipboard-check'], 'production' => ['Production', 'chart'], 'income' => ['Income', 'trend'], 'materials' => ['Materials', 'layers'], 'visits' => ['Visit Reports', 'calendar']];
    $workflow = app(\App\View\WorkflowNavigation::class);
    $programPrefix = str_replace('.monitoring', '', $monitoringPrefix);
    $parentAssociation = $record->project?->association;
    $canViewParentProject = $record->project && $parentAssociation && ($programPrefix !== 'association'
        || ($parentAssociation->status === 'active' && $parentAssociation->archived_at === null));
    $parents = $canViewParentProject ? [['name' => $programPrefix.'.projects.show', 'parameters' => ['project' => $record->project_id], 'label' => $record->project->title]] : [];
    $workflowBack = $workflow->back(request(), $monitoringPrefix.'.index', ['year' => $record->year], $parents);
    $breadcrumbs = [];
    if ($parentAssociation) $breadcrumbs[] = ['label' => $parentAssociation->name, 'url' => $programPrefix === 'association' ? null : route($programPrefix.'.associations.show', ['association' => $parentAssociation->id, 'section' => 'projects'])];
    if ($record->project) $breadcrumbs[] = ['label' => $record->project->title, 'url' => $canViewParentProject ? $workflow->parent(request(), $programPrefix.'.projects.show', ['project' => $record->project_id]) : null];
    if ($programPrefix === 'admin') array_unshift($breadcrumbs, ['label' => 'Monitoring Management', 'url' => route($monitoringPrefix.'.index', ['year' => $record->year])]);
    if ($programPrefix === 'association') {
        $barangay = $parentAssociation?->locationBarangay();
        $municipality = $barangay?->municipality;
        $location = collect([$barangay?->name, $municipality?->name, $municipality?->province])->filter(fn ($part) => filled($part))->implode(', ');
    }
@endphp
@if(!in_array($programPrefix, ['admin', 'association'], true))<x-workflow-navigation :back="$workflowBack" :parents="$breadcrumbs" :current="'Q'.$record->quarter?->quarter_number.' '.$record->year.' · '.$sections[$activeSection][0]" />@endif
@if($programPrefix === 'admin')
<header class="card monitoring-record-header">
    <div class="monitoring-record-heading"><span class="monitoring-record-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25H7.5a2.25 2.25 0 0 0-2.25 2.25v11.25A2.25 2.25 0 0 0 7.5 21h9a2.25 2.25 0 0 0 2.25-2.25V7.5a2.25 2.25 0 0 0-2.25-2.25H15M9 5.25A2.25 2.25 0 0 0 11.25 7.5h1.5A2.25 2.25 0 0 0 15 5.25M9 5.25A2.25 2.25 0 0 1 11.25 3h1.5A2.25 2.25 0 0 1 15 5.25M9 14.25l2.25 2.25L15 12" /></svg></span><div><p class="eyebrow">Quarterly monitoring · Q{{ $record->quarter?->quarter_number }} {{ $record->year }}</p><h1 tabindex="-1">{{ $sections[$activeSection][0] }}</h1></div><span class="badge {{ $record->site_validated ? 'badge-success' : 'badge-neutral' }}">{{ $record->site_validated ? 'Site validated' : 'Site not validated' }}</span></div>
    <dl class="monitoring-record-identity"><div><dt>Project</dt><dd>{{ $record->project?->title ?? 'Project unavailable' }}</dd></div><div><dt>Association</dt><dd>{{ $record->project?->association?->name ?? 'Association unavailable' }}</dd></div></dl>
</header>
@elseif($programPrefix === 'association')
<header class="card monitoring-heading"><div><p class="eyebrow">Quarterly Monitoring · Q{{ $record->quarter?->quarter_number }} {{ $record->year }}</p><h1 tabindex="-1">Monitoring / {{ $sections[$activeSection][0] }}</h1><p class="monitoring-header-identity"><x-icon name="users" /><strong>{{ $parentAssociation?->name ?? 'Association unavailable' }}</strong></p><p class="monitoring-header-identity"><x-icon name="briefcase" /><span>{{ $record->project?->title ?? 'Project unavailable' }}</span>@if($location)<span class="monitoring-header-location"><x-icon name="map-pin" />{{ $location }}</span>@endif</p></div></header>
@else
<header class="card monitoring-heading"><div><p class="eyebrow">Quarterly Monitoring · Q{{ $record->quarter?->quarter_number }} {{ $record->year }}</p><h1 tabindex="-1">{{ $sections[$activeSection][0] }}</h1><p>{{ $record->project?->association?->name ?? 'Association unavailable' }}</p><p>{{ $record->project?->title ?? 'Project unavailable' }}</p></div></header>
@endif
@if(in_array($programPrefix, ['admin', 'association'], true))<x-workflow-navigation :back="$workflowBack" :parents="$breadcrumbs" :current="'Q'.$record->quarter?->quarter_number.' '.$record->year.' · '.$sections[$activeSection][0]" />@endif
@if($record->project?->association?->archived_at)<p class="notice">Association Archived — Read Only. Monitoring values and history are preserved.</p>@endif
@if($record->project?->archived_at)<p class="notice">Project Archived — Read Only.</p>@elseif($record->project?->isFinalized())<p class="notice">Project finalized — Read Only.</p>@endif
<nav class="monitoring-nav" aria-label="Monitoring navigation">
    @foreach($sections as $tab => [$label, $icon])
        <a href="{{ $tab === 'visits' ? route($monitoringPrefix.'.visits', [$record] + $workflow->carry(request())) : route($monitoringPrefix.'.workspace', [$record, $tab] + $workflow->carry(request())) }}" @if($activeSection === $tab) aria-current="page" @endif><x-icon :name="$icon" /><span>{{ $label }}</span></a>
    @endforeach
</nav>
<section class="card monitoring-overview" aria-labelledby="quarterly-overview-heading">
    <div class="monitoring-section-heading"><div><h2 id="quarterly-overview-heading">@if($programPrefix === 'admin')<svg class="monitoring-heading-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25H7.5a2.25 2.25 0 0 0-2.25 2.25v11.25A2.25 2.25 0 0 0 7.5 21h9a2.25 2.25 0 0 0 2.25-2.25V7.5a2.25 2.25 0 0 0-2.25-2.25H15M9 5.25A2.25 2.25 0 0 0 11.25 7.5h1.5A2.25 2.25 0 0 0 15 5.25M9 5.25A2.25 2.25 0 0 1 11.25 3h1.5A2.25 2.25 0 0 1 15 5.25" /></svg>@endif Quarterly Overview</h2>@unless($programPrefix === 'association' && $visitsActive)<p>Saved context for this reporting period.</p>@endunless</div>
        @if($canManage && !request()->routeIs($monitoringPrefix.'.overview.edit'))<a class="button" href="{{ route($monitoringPrefix.'.overview.edit', $record) }}"><x-icon name="pencil" />Edit Overview</a>@endif
    </div>
    @include('monitoring.partials.details', ['details' => [
        'Reporting Period' => 'Q'.$record->quarter?->quarter_number.' '.$record->year,
        'Date Created' => $record->created_at?->format('M d, Y'),
        'Monitored By' => $record->monitoredBy?->display_name,
        'Site Validated' => $record->site_validated ? 'Yes' : 'No',
        'Current Project Status' => ucfirst($record->project?->status ?? 'Unavailable'),
        'Status During Monitoring' => ucfirst($record->project_status),
    ], 'badgeLabels' => in_array($programPrefix, ['admin', 'association'], true) ? ['Site Validated', 'Current Project Status', 'Status During Monitoring'] : []])
</section>
