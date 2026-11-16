@php
    $activeFilterCount = (int) ($filters['search'] !== '') + (int) ($filters['validated'] !== '')
        + (int) ($filters['sort'] !== 'newest') + (int) ($filters['quarter'] !== '')
        + (int) (isset($years) && $selectedYear !== $years->first());
@endphp
<form method="GET" action="{{ $filterAction }}" aria-label="Search and filter Monitoring records">
    @foreach(app(\App\View\WorkflowNavigation::class)->carry(request()) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    <div class="monitoring-search-bar">
        <div class="field monitoring-search"><label for="monitoring-search">{{ isset($years) && $monitoringPrefix === 'association.monitoring' ? 'Search monitoring records' : 'Search '.(isset($years) ? 'Monitoring' : 'visit history') }}</label><div><x-icon name="search" /><input id="monitoring-search" type="search" name="search" maxlength="255" value="{{ $filters['search'] }}" placeholder="{{ $searchPlaceholder }}"></div></div>
        <button class="button button-primary" type="submit">Search</button>
        <button class="button" type="button" data-disclosure="monitoring-filter-panel" aria-controls="monitoring-filter-panel" aria-expanded="false"><x-icon name="filter" />Filters<x-icon name="chevron-down" /></button>
        <a class="button" href="{{ $filterAction }}">Reset</a>
        @if($activeFilterCount)<span class="monitoring-filter-indicator" role="status">{{ $activeFilterCount }} active filter(s)</span>@endif
    </div>
    <div id="monitoring-filter-panel" class="monitoring-filter-panel" hidden>
        <div class="monitoring-filter-grid">
            @isset($years)
                <div class="field"><label for="monitoring-year">Reporting Year</label><select id="monitoring-year" name="year" @disabled($years->isEmpty())>@forelse($years as $year)<option value="{{ $year }}" @selected((int) $year === (int) $selectedYear)>{{ $year }}</option>@empty<option>No reporting years available</option>@endforelse</select></div>
                <div class="field"><label for="monitoring-quarter">Matching quarter</label><select id="monitoring-quarter" name="quarter"><option value="">All quarters</option>@foreach([1, 2, 3, 4] as $quarter)<option value="{{ $quarter }}" @selected((int) $filters['quarter'] === $quarter)>Q{{ $quarter }}</option>@endforeach</select></div>
            @endisset
            <div class="field"><label for="monitoring-validated">Site validation</label><select id="monitoring-validated" name="validated"><option value="">All records</option><option value="1" @selected($filters['validated'] === '1')>Validated</option><option value="0" @selected($filters['validated'] === '0')>Not validated</option></select></div>
            <div class="field"><label for="monitoring-sort">Sort by</label><select id="monitoring-sort" name="sort">@foreach($sortOptions as $value => $label)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>@endforeach</select></div>
        </div>
        @if(isset($years) && $monitoringPrefix === 'association.monitoring')<p class="monitoring-filter-help">Filters find matching projects. Each project shows all its recorded quarters for the selected year.</p>@endif
        <div class="monitoring-actions"><button class="button button-primary" type="submit">Apply Filters</button><a class="button" href="{{ $filterAction }}">Reset All</a></div>
    </div>
    <noscript><style>.monitoring-management #monitoring-filter-panel[hidden] { display: block !important; }</style></noscript>
</form>
