<section class="association-management" data-association-ui data-workspace-module="associations" data-workspace-title="{{ $officerDirectory ? 'Association Officers' : ($prefix === 'admin' ? 'Association Management' : 'Assigned Associations') }}">
    <header class="card association-heading">
        <div><p class="eyebrow">{{ $prefix === 'admin' ? 'System Administrator' : 'Field Officer' }}</p><h1 tabindex="-1">{{ $officerDirectory ? 'Association Officers' : ($prefix === 'admin' ? 'Association Management' : 'Assigned Associations') }}</h1><p>{{ $officerDirectory ? 'Manage organizational positions held by eligible association members.' : ($prefix === 'admin' ? 'Manage association records, locations, and organizational officers.' : 'Review association records and manage officers within your current assignments.') }}</p></div>
        @if(!$officerDirectory && $prefix !== 'admin')
            <x-contextual-report :prefix="$prefix" type="associations" :related="true"
                :scope="($prefix === 'admin' ? 'All authorized associations, including archived associations.' : 'Your currently assigned active, unarchived associations.').' Current snapshot; no reporting period. Search, status, municipality, barangay, officer, sorting and pagination selections from this register are not applied. Open an association for a single-association report.'" />
        @endif
    </header>
        <div class="association-heading-actions">
            @if(!$officerDirectory)
                <a class="button" href="{{ route($prefix.'.association-officers.index') }}">{{ $prefix === 'admin' ? 'Manage Association Officers' : 'Assign Officers' }}</a>
                @if($prefix === 'admin')<x-contextual-report :prefix="$prefix" type="associations" :related="true" :show-scope="false" scope="All associations, including archived records. Register filters do not apply." />@endif
                @if($prefix === 'admin')<a class="button button-primary" href="{{ route('admin.associations.index', $filters + ['editor' => 'create']) }}"><x-icon name="plus" />Add Association</a>@endif
            @else
                @if($prefix === 'admin')<nav class="association-officer-path" aria-label="Association officer navigation"><a href="{{ route('admin.associations.index') }}">Association Management</a><span aria-hidden="true">/</span><a href="{{ route('admin.association-officers.index') }}" aria-current="page">Manage Association Officers</a></nav>@endif
                <a class="button" href="{{ route($prefix.'.associations.index') }}">Back to {{ $prefix === 'admin' ? 'Association Management' : 'Assigned Associations' }}</a>
            @endif
        </div>
    @if($summary !== null)
        <nav class="association-summary" aria-label="Association status groups">
            @foreach(['total' => ['Total Associations', 'all', 'building'], 'active' => ['Active Associations', 'active', 'check'], 'inactive' => ['Inactive Associations', 'inactive', 'users'], 'archived' => ['Archived Associations', 'archived', 'archive']] as $key => [$label, $status, $icon])
                <a href="{{ route('admin.associations.index', array_replace($filters, ['status' => $status])) }}" data-association-summary="{{ $key }}" @if($filters['status'] === $status) aria-current="page" @endif>
                    <x-stat-card :label="$label" :value="number_format($summary[$key])" :icon="$icon" />
                </a>
            @endforeach
        </nav>
        <div class="association-info-row"><span>About these totals</span><x-area-info label="About association totals">Total includes archived associations. Active and inactive totals exclude archived records. The report includes all authorized associations; register filters do not apply.</x-area-info></div>
    @endif
    @if($officerDirectory)<p class="notice">Officers are association members. Assigning a position does not create an account or change access permissions.</p>@endif
    <section class="card association-register" aria-labelledby="association-records-title">
        <div class="association-section-heading"><div class="association-heading-title"><h2 id="association-records-title">{{ $officerDirectory ? 'Associations and officers' : 'Association records' }}</h2>@if(!$officerDirectory && $prefix === 'admin')<x-area-info label="About member counts">Member counts include active, unarchived members.</x-area-info>@endif</div>@if($prefix !== 'admin')<p>{{ number_format($associations->total()) }} matching association(s)</p>@endif</div>
        @include('associations.partials.filters')
        <div class="table-scroll" role="region" aria-label="Association records" tabindex="0">
            <table class="data-table association-table"><caption class="sr-only">{{ $prefix === 'admin' ? 'Authorized association records' : 'Associations currently assigned to this Field Officer' }}.</caption>
                <thead><tr><th scope="col">Association</th><th scope="col">{{ $prefix === 'admin' && !$officerDirectory ? 'Assigned Officers' : 'President' }}</th>@if(!$officerDirectory)<th scope="col">Location</th>@endif<th scope="col">Status</th><th scope="col">{{ $officerDirectory ? 'Current officers' : 'Current members' }}</th><th scope="col">Actions</th></tr></thead><tbody>
                @forelse($associations as $association)
                    @php($recordParameters = ['association' => $association->id] + $filters + ['page' => $associations->currentPage(), 'origin' => $officerDirectory ? 'officers' : 'register'])
                    <tr>
                        <th scope="row"><a href="{{ route($prefix.'.associations.show', $recordParameters) }}">{{ $association->name }}</a>@if(!$officerDirectory && $association->acronym)<small>{{ $association->acronym }}</small>@endif</th>
                        <td>
                            @if($prefix === 'admin' && !$officerDirectory)
                                <dl class="association-assigned-officers">
                                    <div><dt>Field Officer</dt><dd>{{ $association->primaryFieldOfficer?->display_name ?? 'No Field Officer assigned' }}</dd></div>
                                    <div><dt>President</dt><dd>@include('associations.partials.person-name', ['person' => $association->currentPresident])</dd></div>
                                </dl>
                            @else
                                @include('associations.partials.person-name', ['person' => $association->currentPresident])
                            @endif
                        </td>
                        @if(!$officerDirectory)
                            @php($locationBarangay = $association->locationBarangay())
                            <td>{{ $locationBarangay?->municipality?->name ?? 'Municipality unavailable' }}<small>{{ $locationBarangay?->name ?? 'Barangay unavailable' }}</small></td>
                        @endif
                        <td><x-status-badge :value="ucfirst($association->status)" />@if($association->archived_at)<span class="badge badge-neutral">Archived</span>@endif</td>
                        <td>{{ number_format($officerDirectory ? $association->officers_count : $association->members_count) }}</td>
                        <td>@include('associations.partials.actions', ['allowRecordChanges' => !$officerDirectory])</td>
                    </tr>
                @empty<tr><td colspan="{{ $officerDirectory ? 5 : 6 }}"><x-empty-state title="No matching associations" message="Try another search or reset the filters. Only associations authorized for your account are shown." /></td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @include('associations.partials.pagination', ['paginator' => $associations, 'label' => 'Association pages'])
    </section>
</section>
