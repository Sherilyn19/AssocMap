@php
    $chartDescriptions = [
        'association_status' => 'Associations by status, including archived records.',
        'municipalities' => 'Top 12 municipalities and cities, including archived associations.',
        'project_status' => 'Unarchived projects by current status.',
        'components' => 'Unarchived projects across up to 12 program components.',
    ];
    $styledChart = ($role === 'system_admin' || (in_array($role, ['field_officer', 'association_account'], true) && in_array($chart['key'], ['project_status', 'components'], true))) && array_key_exists($chart['key'], $chartDescriptions);
    $projectChart = in_array($chart['key'], ['project_status', 'components'], true);
    $associationNotes = ['project_status' => 'Current status of unarchived projects.', 'components' => 'Unarchived projects by program component, showing up to 12 components.'];
@endphp
<section class="card dashboard-panel {{ $styledChart ? 'dashboard-association-chart dashboard-chart-'.$chart['key'] : '' }}" aria-labelledby="dashboard-chart-{{ $chart['key'] }}" @if($styledChart) data-dashboard-chart @endif>
    @if($styledChart)
        <div class="dashboard-panel-heading"><div><h2 id="dashboard-chart-{{ $chart['key'] }}">{{ $chart['title'] }}</h2><p class="dashboard-help">{{ $role === 'system_admin' ? $chartDescriptions[$chart['key']] : ($role === 'association_account' ? ($associationNotes[$chart['key']] ?? $chart['note']) : $chart['note']) }}</p></div><a class="dashboard-associations-link" href="{{ route($role === 'association_account' ? 'association.projects.index' : ($role === 'field_officer' ? 'field-officer.projects.index' : ($projectChart ? 'admin.projects.index' : 'admin.associations.index'))) }}">{{ $projectChart ? 'View projects' : 'View associations' }} <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" /></svg></a></div>
    @else
        <h2 id="dashboard-chart-{{ $chart['key'] }}">{{ $chart['title'] }}</h2>
        <p class="dashboard-help">{{ $chart['note'] }}</p>
    @endif
    @if(array_sum(array_column($chart['rows'], 'value')) > 0)
        @php $maximum = max(1, ...array_column($chart['rows'], 'value')); @endphp
        <dl class="dashboard-bars">
            @foreach($chart['rows'] as $row)
                <div>
                    <dt>{{ $row['label'] }}</dt>
                    <dd><span class="dashboard-bar-track" aria-hidden="true"><span style="width: {{ min(100, $row['value'] / $maximum * 100) }}%"></span></span><span>{{ number_format($row['value']) }}</span></dd>
                </div>
            @endforeach
        </dl>
    @else
        <p class="dashboard-empty">No matching records in this scope.</p>
    @endif
</section>
