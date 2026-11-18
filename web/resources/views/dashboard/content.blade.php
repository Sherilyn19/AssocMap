{{-- Choose the greeting and shortcuts for the signed-in role. --}}
@php
    $introduction = match ($role) {
        'system_admin' => 'Your overview of associations, members, and project implementation.',
        'field_officer' => 'Explore updates and records for your assigned active associations.',
        default => 'Your association records, applications, projects and monitoring overview.',
    };
    $roleLabel = match ($role) { 'system_admin' => 'System Administrator', 'field_officer' => 'Field Officer', default => 'Association Account' };
    $dashboardActions = match ($role) {
        'system_admin' => [
            ['admin.associations.index', 'Associations', 'building'], ['admin.users.index', 'Field Officers & accounts', 'user'],
            ['admin.association-officers.index', 'Association Officers', 'users'], ['admin.projects.index', 'Projects', 'briefcase'],
            ['admin.trainings.index', 'Trainings', 'graduation'], ['admin.monitoring.index', 'Monitoring', 'chart'],
            ['admin.reports.index', 'Reports & Analytics', 'receipt'], ['admin.gis.index', 'GIS Mapping', 'map'],
        ],
        'field_officer' => [
            ['field-officer.association-officers.index', 'Assigned association officers', 'users'], ['field-officer.members.index', 'Members', 'users'],
            ['field-officer.member-applications.index', 'Member Applications', 'receipt'], ['field-officer.projects.index', 'Projects', 'briefcase'],
            ['field-officer.trainings.index', 'Trainings', 'graduation'], ['field-officer.monitoring.index', 'Monitoring', 'chart'],
            ['field-officer.reports.index', 'Reports & Analytics', 'receipt'], ['field-officer.gis.index', 'GIS Mapping', 'map'],
        ],
        default => [
            ['association.member-applications.index', 'Member Applications', 'users'], ['association.projects.index', 'My Projects', 'briefcase'], ['association.monitoring.index', 'Monitoring', 'clipboard-check'],
            ['association.reports.index', 'Reports & Analytics', 'receipt'], ['association.gis.index', 'GIS Mapping', 'map'],
        ],
    };
@endphp
{{-- Welcome the user and show when the dashboard was updated. --}}
<header class="dashboard-intro">
    <div>
        @if($role === 'system_admin')<p class="eyebrow">BFAR SAAD Phase II</p>@endif
        <h1 tabindex="-1">{{ match ($role) { 'field_officer' => 'Welcome, '.($currentUser->display_name ?: 'Field Officer').'.', 'association_account' => 'Welcome, '.($currentUser->display_name ?: 'Association Account').'.', default => 'Welcome, '.($currentUser->display_name ?: 'Administrator').'.' } }}</h1>
        <p class="dashboard-introduction">{{ $introduction }}</p>
    </div>
    @if($state === 'ready')
        <div class="dashboard-context">
            <a class="button" href="{{ route($prefix.'.dashboard') }}">{{ $role === 'system_admin' ? 'Refresh overview' : 'Refresh dashboard' }}</a>
            <p>Updated <time datetime="{{ $dashboard['generated_at'] }}">{{ \Carbon\CarbonImmutable::parse($dashboard['generated_at'])->format('M j, Y, g:i a') }}</time><span>{{ $role === 'field_officer' ? 'PHT' : $dashboard['timezone'] }}</span></p>
        </div>
    @endif
</header>
@if($state !== 'ready')
    <section class="card dashboard-panel" role="alert">
        <h2>{{ $state === 'denied' ? 'Dashboard access unavailable' : 'Dashboard temporarily unavailable' }}</h2>
        <p class="dashboard-help">{{ $state === 'denied' ? 'Your account or association access is unavailable. Association Accounts need one active account link to an active, unarchived association. Ask the administrator to review access.' : 'Current records could not be read. No statistics are displayed. Retry when the database is available.' }}</p>
        <a class="button button-primary" href="{{ route($prefix.'.dashboard') }}">Retry dashboard</a>
    </section>
@else
    @if($role === 'field_officer')
        {{-- Show the officer's own account and published photos from assigned locations. --}}
        <section class="card dashboard-panel dashboard-association" aria-labelledby="dashboard-officer-title">
            <span class="stat-icon"><x-icon name="user" /></span>
            <div class="dashboard-association-content">
                <p class="dashboard-kicker">Your account</p>
                <div class="dashboard-association-title-row"><h2 id="dashboard-officer-title">{{ $currentUser->display_name }}</h2><span class="badge badge-blue">{{ $currentUser->is_active ? 'Active' : 'Inactive' }}</span></div>
                <section class="dashboard-association-group"><h3>Field Officer details</h3><dl class="dashboard-association-details">
                    @foreach([
                        ['Email', request()->attributes->get('current_user_email'), 'user'],
                        ['User ID', $currentUser->id, 'receipt'],
                        ['Assigned associations', $dashboard['scope_count'], 'building'],
                        ['Created', $currentUser->created_at?->copy()->timezone('Asia/Manila')->format('M d, Y'), 'calendar'],
                        ['Activated', $currentUser->activated_at?->copy()->timezone('Asia/Manila')->format('M d, Y'), 'calendar'],
                    ] as [$label, $value, $icon])
                        <div><dt><x-icon :name="$icon" />{{ $label }}</dt><dd>{{ filled($value) ? $value : 'Not recorded' }}</dd></div>
                    @endforeach
                </dl></section>
            </div>
            @include('dashboard.gis-gallery', ['galleryRoute' => 'field-officer.gis.index'])
        </section>
    @endif
    @if($role === 'association_account')
        <section class="card dashboard-panel dashboard-association" aria-labelledby="dashboard-association-title">
            <span class="stat-icon"><x-icon name="building" /></span>
            <div class="dashboard-association-content"><p class="dashboard-kicker">Your association</p><div class="dashboard-association-title-row"><h2 id="dashboard-association-title">{{ $dashboard['association']['name'] }}</h2><span class="badge badge-blue">{{ ucfirst($dashboard['association']['status']) }}</span></div>
                @foreach([
                    'Association details' => [
                        ['Acronym', $dashboard['association']['acronym'], 'building'],
                        ['SAAD Phase II join date', $dashboard['association']['joined_on'], 'calendar'],
                        ['Association ID', $dashboard['association']['id'], 'receipt'],
                        // This is the date of the last change to the association record.
                        ['Last updated', $dashboard['association']['updated_at'] ?? null, 'calendar'],
                    ],
                    'Registered location' => [
                        ['Address', $dashboard['association']['address'], 'map-pinned'],
                        ['Barangay', $dashboard['association']['barangay'], 'map-pin'],
                        ['Municipality / city', $dashboard['association']['municipality'], 'map-pin'],
                        ['Province', $dashboard['association']['province'], 'map'],
                        ['Region', $dashboard['association']['region'], 'map'],
                        ['Barangay ID', $dashboard['association']['barangay_id'], 'receipt'],
                    ],
                ] as $group => $items)
                    <section class="dashboard-association-group"><h3>{{ $group }}</h3><dl class="dashboard-association-details">
                        @foreach($items as [$label, $value, $icon])<div><dt><x-icon :name="$icon" />{{ $label }}</dt><dd>{{ filled($value) ? $value : 'Not recorded' }}</dd></div>@endforeach
                    </dl></section>
                @endforeach
            </div>
                @include('dashboard.gis-gallery', ['galleryRoute' => 'association.gis.index'])
        </section>
    @elseif($role === 'field_officer' && $dashboard['scope_count'] === 0)
        <p class="notice" role="status">No active, unarchived associations are currently assigned to you. Ask the administrator to review assignments.</p>
    @endif
    {{-- Show summary cards and shortcuts for the current role. --}}
    @php
        $primaryCards = match ($role) {
            'system_admin' => [
                ['associations_total', 'Total Associations', 'admin.associations.index', 'Association Management'],
                ['members_total', 'Total Members', 'admin.members.index', 'Member Management'],
                ['projects_total', 'Total Projects', 'admin.projects.index', 'Project Management'],
            ],
            'field_officer' => [
                ['associations_active', 'Assigned Associations', 'field-officer.association-officers.index', 'Assigned associations'],
                ['members_total', 'Total Members', 'field-officer.members.index', 'Member Management'],
                ['applications_pending', 'Pending Applications', 'field-officer.member-applications.index', 'Member Applications'],
            ],
            'association_account' => [
                ['applications_pending', 'Pending Applications', 'association.member-applications.index', 'Member Applications'],
            ],
            default => [],
        };
        $metricsByKey = array_column($dashboard['metrics'], null, 'key');
    @endphp
    @if($role !== 'association_account')<section class="dashboard-metrics" aria-label="Dashboard summary">
        @foreach($primaryCards as [$metricKey, $label, $routeName, $destination])
            @if(isset($metricsByKey[$metricKey]))
                @php
                    $metric = $metricsByKey[$metricKey];
                    $displayValue = $metric['value'] === null ? 'Unavailable' : number_format($metric['value']);
                    $parameters = $metricKey === 'applications_pending' ? ['status' => 'pending'] : [];
                    // Keep the field officer summary cards concise.
                    $cardNote = $role === 'field_officer' || ($role === 'association_account' && $metricKey === 'applications_pending') ? '' : $metric['note'];
                @endphp
                <a class="dashboard-summary-link" href="{{ route($routeName, $parameters) }}" aria-label="{{ $label }}: {{ $displayValue }}. Open {{ $destination }}">
                    <x-stat-card :label="$label" :value="$displayValue" :icon="$metric['icon']" :note="$cardNote" />
                </a>
            @endif
        @endforeach
    </section>@endif
    <section class="card dashboard-panel" aria-labelledby="dashboard-actions-title">
        <div class="dashboard-panel-heading"><div><h2 id="dashboard-actions-title">{{ match ($role) { 'field_officer' => 'Field workspace', 'association_account' => 'Your workspace', default => 'Quick access' } }}</h2><p class="dashboard-help">{{ match ($role) { 'field_officer' => 'People, program records and reporting in your assigned scope.', 'association_account' => 'Applications, monitoring and reports for your association.', default => 'Open your workspace' } }}</p></div><span class="stat-icon" aria-hidden="true"><x-icon name="grid" /></span></div>
        <nav @class(['dashboard-actions', 'dashboard-actions-field' => $role === 'field_officer', 'dashboard-actions-association' => $role === 'association_account']) aria-label="Dashboard quick actions">
            @foreach($dashboardActions as [$routeName, $label, $icon])
                <a class="dashboard-shortcut" href="{{ route($routeName) }}"><span class="stat-icon"><x-icon :name="$icon" /></span><span>{{ $label }}</span><x-icon name="arrow" /></a>
            @endforeach
            @if($role === 'field_officer')<a class="dashboard-shortcut" href="#assigned-associations"><span class="stat-icon"><x-icon name="building" /></span><span>Assigned associations</span><x-icon name="arrow" /></a>@endif
        </nav>
    </section>
    @if($role !== 'system_admin')
    <div @class(['dashboard-overview', 'dashboard-overview-field' => $role === 'field_officer', 'dashboard-overview-association' => $role === 'association_account'])>
    @endif
    @if($role === 'system_admin')
        <section class="card dashboard-panel dashboard-priority" aria-labelledby="dashboard-attention-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Account oversight</p><h2 id="dashboard-attention-title">Administrative attention</h2></div><span class="stat-icon"><x-icon name="shield" /></span></div>
            @if($dashboard['officer_attention'] === null)<p class="dashboard-empty">Officer assignment checks are temporarily unavailable.</p>
            @else<p class="dashboard-help"><strong>{{ number_format($dashboard['officer_attention']) }}</strong> active, unarchived associations have an inactive or invalid assigned Field Officer profile/role.</p>@endif
            <a class="button" href="{{ route('admin.users.index') }}">Review accounts & assignments</a>
            <a class="button" href="{{ route('admin.audit-logs.index') }}">Open Audit Logs</a>
        </section>
    @else
        <section class="card dashboard-panel dashboard-priority" aria-labelledby="dashboard-applications-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Membership</p><h2 id="dashboard-applications-title">{{ $role === 'field_officer' ? 'Applications awaiting your review' : 'Member application status' }}</h2></div>@if($role === 'association_account')<a class="dashboard-associations-link" href="{{ route('association.member-applications.index', ['status' => 'pending']) }}">View applications <x-icon name="arrow" /></a>@else<span class="stat-icon"><x-icon name="receipt" /></span>@endif</div>
            @if($dashboard['applications'] === null)<p class="dashboard-empty">Application counts are temporarily unavailable.</p>
            @else
                <dl class="dashboard-summary">
                    {{-- Each status opens the matching application list. --}}
                    @foreach($dashboard['applications'] as $status => $count)<div><dt><a class="dashboard-application-status-link" href="{{ route($prefix.'.member-applications.index', ['status' => $status]) }}" aria-label="View {{ $status }} applications, {{ number_format($count) }}">{{ ucfirst($status) }}</a></dt><dd><x-summary-counter :value="number_format($count)" /></dd></div>@endforeach
                </dl>
            @endif
            @if($role === 'association_account')<p class="dashboard-help dashboard-info-line">About application totals <x-area-info label="About application totals">Totals cover the association. The application list shows submissions from this account, so its counts may differ. Field Officers review applications; totals are separate from official member counts.</x-area-info></p>@endif
        </section>
    @endif
    @if($role !== 'association_account')
        <section class="card dashboard-panel dashboard-register" id="assigned-associations" aria-labelledby="dashboard-associations-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">{{ $role === 'field_officer' ? 'Your assignments' : 'Association register' }}</p><h2 id="dashboard-associations-title">{{ $role === 'field_officer' ? 'Assigned associations' : 'Association overview' }}</h2></div>
                @if($role === 'system_admin')
                    <a class="dashboard-associations-link" href="{{ route('admin.associations.index') }}">View associations <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a>
                @else<a class="dashboard-associations-link" href="{{ route('field-officer.associations.index') }}">View associations <x-icon name="arrow" /></a>@endif
            </div>
            <p class="dashboard-help">{{ $role === 'system_admin' ? 'Member counts exclude archived records.' : 'Showing up to 10 assigned associations, listed alphabetically. Member counts include active members whose records are not archived. Project counts include all projects that are not archived.' }}</p>
            @if($dashboard['associations'] === null)<p class="dashboard-empty">The association list is temporarily unavailable.</p>
            @elseif(!$dashboard['associations'])<p class="dashboard-empty">No eligible associations in this scope.</p>
            @else
                <div class="dashboard-table-scroll" tabindex="0" role="region" aria-label="Association overview">
                    <table><caption class="sr-only">{{ $role === 'field_officer' ? 'Current eligible assignments' : 'Registered associations' }}</caption><thead><tr><th scope="col">Association</th><th scope="col">Municipality / City</th><th scope="col">Status</th><th scope="col">Active members</th><th scope="col">Unarchived projects</th><th scope="col">Officers</th></tr></thead><tbody>
                        @foreach($dashboard['associations'] as $association)
                            <tr><th scope="row">{{ $association['name'] }}</th><td>{{ $association['municipality'] }}</td><td><span class="badge badge-neutral">{{ ucfirst($association['status']) }}</span></td><td>{{ number_format($association['active_members']) }}</td><td>{{ number_format($association['unarchived_projects']) }}</td><td><a href="{{ route($prefix.'.association-officers.show', $association['id']) }}">View officers<span class="sr-only"> for {{ $association['name'] }}</span></a></td></tr>
                        @endforeach
                    </tbody></table>
                </div>
                @if($role !== 'system_admin' && $dashboard['scope_count'] > 10)<p class="dashboard-help">{{ number_format($dashboard['scope_count']) }} associations are in scope; this list shows the first 10. Use the association/officer workspace for the remaining records.</p>@endif
            @endif
            @if($role === 'system_admin' && $dashboard['associations'] !== null)
                @php
                    $pages = new \Illuminate\Pagination\LengthAwarePaginator($dashboard['associations'], $dashboard['scope_count'], 10, $dashboard['association_page'] ?? 1, ['path' => request()->url(), 'pageName' => 'association_page', 'fragment' => 'assigned-associations']);
                    $pages->appends(request()->except('association_page'));
                @endphp
                <div class="dashboard-pagination">
                    <p>Showing {{ number_format($pages->firstItem() ?? 0) }}–{{ number_format($pages->lastItem() ?? 0) }} of {{ number_format($pages->total()) }} records <span aria-hidden="true">·</span> Page {{ $pages->currentPage() }} of {{ $pages->lastPage() }}</p>
                    <nav aria-label="Association pages">
                        @if($pages->onFirstPage())<span class="dashboard-page-disabled" aria-disabled="true">Previous</span>@else<a href="{{ $pages->previousPageUrl() }}" rel="prev">Previous</a>@endif
                        @if($pages->hasMorePages())<a href="{{ $pages->nextPageUrl() }}" rel="next">Next</a>@else<span class="dashboard-page-disabled" aria-disabled="true">Next</span>@endif
                    </nav>
                </div>
            @endif
        </section>
    @endif
    @if($role === 'system_admin')
    <section class="card dashboard-panel dashboard-register dashboard-projects" id="registered-projects" aria-labelledby="dashboard-projects-title">
        <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Project register</p><h2 id="dashboard-projects-title">Recently registered projects</h2></div><a class="dashboard-associations-link" href="{{ route('admin.projects.index') }}">View projects <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a></div>
        <p class="dashboard-help">Unarchived projects, newest first.</p>
        @if($dashboard['recent_projects'] === null)<p class="dashboard-empty">Project records are temporarily unavailable.</p>
        @elseif(!$dashboard['recent_projects'])<p class="dashboard-empty">No unarchived projects in this scope.</p>
        @else
            <div class="dashboard-table-scroll" tabindex="0" role="region" aria-label="Recently registered projects">
                <table><caption class="sr-only">Recently registered projects</caption><thead><tr><th scope="col">Project</th><th scope="col">Association</th><th scope="col">Status</th><th scope="col">Program year</th><th scope="col">Registered</th></tr></thead><tbody>
                    @foreach($dashboard['recent_projects'] as $project)
                        <tr><th scope="row"><a href="{{ route('admin.projects.show', $project['id']) }}">{{ $project['title'] }}</a></th><td>{{ $project['association'] }}</td><td><span class="badge badge-neutral">{{ ucfirst($project['status']) }}</span></td><td>{{ $project['program_year'] }}</td><td>{{ \Carbon\CarbonImmutable::parse($project['created_at'])->setTimezone('Asia/Manila')->format('M j, Y') }}</td></tr>
                    @endforeach
                </tbody></table>
            </div>
        @endif
        @if($dashboard['recent_projects'] !== null)
            @php
                $projectPages = new \Illuminate\Pagination\LengthAwarePaginator($dashboard['recent_projects'], $dashboard['project_count'] ?? count($dashboard['recent_projects']), 10, $dashboard['project_page'] ?? 1, ['path' => request()->url(), 'pageName' => 'project_page', 'fragment' => 'registered-projects']);
                $projectPages->appends(request()->except('project_page'));
            @endphp
            <div class="dashboard-pagination"><p>Showing {{ number_format($projectPages->firstItem() ?? 0) }}–{{ number_format($projectPages->lastItem() ?? 0) }} of {{ number_format($projectPages->total()) }} records <span aria-hidden="true">·</span> Page {{ $projectPages->currentPage() }} of {{ $projectPages->lastPage() }}</p>
                <nav aria-label="Project pages">@if($projectPages->onFirstPage())<span class="dashboard-page-disabled" aria-disabled="true">Previous</span>@else<a href="{{ $projectPages->previousPageUrl() }}" rel="prev">Previous</a>@endif
                    @if($projectPages->hasMorePages())<a href="{{ $projectPages->nextPageUrl() }}" rel="next">Next</a>@else<span class="dashboard-page-disabled" aria-disabled="true">Next</span>@endif</nav>
            </div>
        @endif
    </section>
    @else
    <section class="card dashboard-panel dashboard-register dashboard-projects" aria-labelledby="dashboard-projects-title">
        <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Project register</p><h2 id="dashboard-projects-title">Recently registered projects</h2></div><a class="dashboard-associations-link" href="{{ route($prefix.'.projects.index') }}">View projects <x-icon name="arrow" /></a></div>
        @if($role === 'association_account')<p class="dashboard-help">Your six most recently registered projects, excluding archived records.</p>@endif
        @if($dashboard['recent_projects'] === null)<p class="dashboard-empty">Project records are temporarily unavailable.</p>
        @elseif(!$dashboard['recent_projects'])<p class="dashboard-empty">No unarchived projects in this scope.</p>
        @else
            <ul class="dashboard-records">
                @foreach($dashboard['recent_projects'] as $project)<li><div><strong><a href="{{ route($prefix.'.projects.show', $project['id']) }}">{{ $project['title'] }}</a></strong><p>{{ $project['association'] }} · Program year {{ $project['program_year'] }}</p><small>Registered {{ \Carbon\CarbonImmutable::parse($project['created_at'])->setTimezone('Asia/Manila')->format('M j, Y') }}</small></div><span class="badge badge-neutral">{{ ucfirst($project['status']) }}</span></li>@endforeach
            </ul>
        @endif
    </section>
    @endif
    @if($role !== 'system_admin')
    </div>
    @endif
    <div class="dashboard-panels">
        <section class="card dashboard-panel" aria-labelledby="dashboard-training-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Participation</p><h2 id="dashboard-training-title">Training history</h2></div>@if($role === 'system_admin')<a class="dashboard-associations-link" href="{{ route('admin.trainings.index') }}">View trainings <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a>@else<a class="dashboard-associations-link" href="{{ route($prefix.'.trainings.index') }}">View trainings <x-icon name="arrow" /></a>@endif</div>
            @if($dashboard['training'] === null)<p class="dashboard-empty">Training summaries are temporarily unavailable.</p>
            @else
                <dl class="dashboard-summary"><div><dt>Recorded activities</dt><dd><x-summary-counter :value="number_format($dashboard['training']['records'])" /></dd></div><div><dt>Completed activities</dt><dd><x-summary-counter :value="number_format($dashboard['training']['completed'])" /></dd></div><div><dt>Distinct members marked present</dt><dd><x-summary-counter :value="number_format($dashboard['training']['distinct_present_members'])" /></dd></div></dl>
            @endif
            @if($role === 'association_account')<p class="dashboard-help">Training history includes all dates and archived records. Each member marked present is counted once.</p>@endif
        </section>
        <section class="card dashboard-panel" aria-labelledby="dashboard-monitoring-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Quarterly records</p><h2 id="dashboard-monitoring-title">Monitoring overview</h2></div>@if($role === 'system_admin')<a class="dashboard-associations-link" href="{{ route('admin.monitoring.index') }}">View monitoring <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a>@else<a class="dashboard-associations-link" href="{{ route($prefix.'.monitoring.index') }}">View monitoring <x-icon name="arrow" /></a>@endif</div>
            @if($dashboard['monitoring'] === null)<p class="dashboard-empty">Monitoring summaries are temporarily unavailable.</p>
            @else
                <dl class="dashboard-summary"><div><dt>Quarterly records</dt><dd><x-summary-counter :value="number_format($dashboard['monitoring']['records'])" /></dd></div><div><dt>Distinct year / quarter periods</dt><dd><x-summary-counter :value="number_format($dashboard['monitoring']['periods'])" /></dd></div><div><dt>Visit reports</dt><dd><x-summary-counter :value="$dashboard['monitoring']['visits'] === null ? 'Unavailable' : number_format($dashboard['monitoring']['visits']['total'])" /></dd></div></dl>
                @if(in_array($role, ['field_officer', 'association_account'], true))<p class="dashboard-help dashboard-info-line">About these totals <x-area-info label="About monitoring totals">{{ $dashboard['monitoring']['visits'] === null ? 'Visit-report data is unavailable.' : number_format($dashboard['monitoring']['visits']['legacy_imports']).' visit reports were imported from earlier records.' }} Quarterly records, periods and visit reports are counted separately.</x-area-info></p>@endif
            @endif
            @if($role === 'association_account')<p class="dashboard-help">All years are included for unarchived projects. Missing records do not necessarily indicate incomplete or unsuccessful work.</p>@endif
        </section>
    </div>
    <div class="dashboard-insights">
        @if($role !== 'system_admin')<div class="dashboard-section-heading"><p class="dashboard-kicker">Portfolio overview</p></div>@endif
        <div class="dashboard-panels">
            @foreach($dashboard['charts'] as $chart)
                @if($chart === null)<section class="card dashboard-panel"><h2>Chart unavailable</h2><p class="dashboard-empty">This summary could not be read. Other available widgets remain visible.</p></section>
                @else @include('dashboard.chart', ['chart' => $chart]) @endif
            @endforeach
        </div>
    </div>
    <div class="dashboard-panels">
        <section class="card dashboard-panel dashboard-recent-panel" aria-labelledby="dashboard-activities-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Program calendar</p><h2 id="dashboard-activities-title">Latest dated program activities</h2></div>@if($role === 'system_admin')<a class="dashboard-associations-link" href="{{ route('admin.program-activities.index') }}">View activities <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a>@else<a class="dashboard-associations-link" href="{{ route($prefix.'.program-activities.index') }}">View activities <x-icon name="arrow" /></a>@endif</div>
            @if($role !== 'association_account')<p class="dashboard-help">{{ $role === 'system_admin' ? 'Latest six unarchived activities, including planned dates.' : 'Shows up to six recent or planned activities for your assigned associations. Archived activities are excluded.' }}</p>@endif
            @if($dashboard['activities'] === null)<p class="dashboard-empty">Program activity records are temporarily unavailable.</p>
            @else
                <ul class="dashboard-records">@forelse($dashboard['activities'] as $activity)<li><div><strong>@if($role !== 'association_account')<a href="{{ route($prefix.'.program-activities.show', $activity['id']) }}">{{ $activity['title'] }}</a>@else{{ $activity['title'] }}@endif</strong><p>{{ $role === 'field_officer' && $activity['activity_date'] ? \Carbon\CarbonImmutable::parse($activity['activity_date'])->format('M j, Y') : $activity['activity_date'] }}</p></div><span class="badge badge-neutral">{{ ucfirst($activity['status']) }}</span></li>@empty<li>No recorded activities in this scope.</li>@endforelse</ul>
            @endif
        </section>
        <section class="card dashboard-panel dashboard-recent-panel" aria-labelledby="dashboard-recent-monitoring-title">
            <div class="dashboard-panel-heading"><div><p class="dashboard-kicker">Monitoring history</p><h2 id="dashboard-recent-monitoring-title">Latest dated monitoring records</h2></div>@if($role === 'system_admin')<a class="dashboard-associations-link" href="{{ route('admin.monitoring.index') }}">View monitoring <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a>@else<a class="dashboard-associations-link" href="{{ route($prefix.'.monitoring.index') }}">View monitoring <x-icon name="arrow" /></a>@endif</div>
            @if($role !== 'association_account')<p class="dashboard-help">{{ $role === 'system_admin' ? 'Latest six quarterly records, with status at the time of monitoring.' : 'Shows up to six recent quarterly records. Each status is the one saved during monitoring.' }}</p>@endif
            @if($dashboard['monitoring'] === null)<p class="dashboard-empty">Monitoring records are temporarily unavailable.</p>
            @else
                @if($role === 'field_officer' && count($dashboard['monitoring']['recent']))<p class="dashboard-help dashboard-monitoring-status-label">Status during monitoring</p>@endif
                <ul class="dashboard-records">@forelse($dashboard['monitoring']['recent'] as $record)<li><div><strong><a href="{{ route($prefix.'.monitoring.workspace', $record['id']) }}">{{ $record['title'] }}</a></strong><p>{{ $record['year'] }} · Q{{ $record['quarter_number'] }} · {{ $role === 'field_officer' && $record['monitoring_date'] ? \Carbon\CarbonImmutable::parse($record['monitoring_date'])->format('M j, Y') : $record['monitoring_date'] }}</p></div><span class="badge badge-neutral">@if($role !== 'field_officer')Status During Monitoring: @endif{{ ucfirst(str_replace('_', ' ', $record['project_status'])) }}</span></li>@empty<li>No quarterly monitoring records in this scope.</li>@endforelse</ul>
            @endif
        </section>
    </div>
@endif
