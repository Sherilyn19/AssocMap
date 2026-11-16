@extends($monitoringLayout)
@section('content')
<div @class(['monitoring-management', 'monitoring-record-view' => $monitoringPrefix === 'admin.monitoring']) data-workspace-module="monitoring" data-workspace-title="Monitoring">
    @include('monitoring.partials.context')
    <section class="card monitoring-section" aria-labelledby="monitoring-section-title">
        <div class="monitoring-section-heading">
            <div><h2 id="monitoring-section-title">@if($monitoringPrefix === 'admin.monitoring')<svg class="monitoring-heading-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v4.125a2.625 2.625 0 0 1-2.625 2.625h-9.75A2.625 2.625 0 0 1 4.5 18.375V5.625A2.625 2.625 0 0 1 7.125 3h6.75L19.5 8.625v5.625ZM9 13.5h6m-6 3h6M13.5 3v4.5a1.125 1.125 0 0 0 1.125 1.125H19.5" /></svg>@endif{{ $section === 'overview' ? 'Quarterly Notes' : ucfirst($section).' Register' }}</h2>
                <p>{{ ['overview' => 'Original quarterly notes retained with this record.', 'production' => 'Recorded output by project product and unit of measurement.', 'income' => 'Saved monthly or quarterly gross income for each production product.', 'materials' => 'Resource condition and maintenance observations for this quarter.'][$section] }}</p>
            </div>
            @if($canManage && $section !== 'overview')<a class="button button-primary" href="{{ route($monitoringPrefix.'.section.edit', [$record, $section]) }}"><x-icon name="pencil" />Manage {{ ucfirst($section) }}</a>@endif
        </div>
        @include('monitoring.partials.project-shortcuts')
        <div class="monitoring-section-body">
            @include('monitoring.partials.'.$section)
        </div>
    </section>
</div>
@endsection
