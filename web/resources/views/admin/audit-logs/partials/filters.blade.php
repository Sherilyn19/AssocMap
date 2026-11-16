<form id="audit-filter-panel" class="audit-filters" method="GET" action="{{ route('admin.audit-logs.index') }}" @if($invalidFilters ?? false) aria-describedby="audit-filter-errors" @endif>
    <fieldset @disabled($unavailable ?? false)>
        <legend class="sr-only">Filter audit events</legend>
        <div class="audit-filter-grid">
            @foreach(['action_type' => ['Action', 'All actions'], 'module' => ['Module', 'All modules'], 'entity_type' => ['Entity type', 'All entity types']] as $field => [$label, $default])
                <div class="field">
                    <label for="audit-{{ $field }}">{{ $label }}</label>
                    <select id="audit-{{ $field }}" name="{{ $field }}">
                        <option value="">{{ $default }}</option>
                        @foreach($choices[$field] ?? [] as $choice)
                            <option value="{{ $choice }}" @selected(($filters[$field] ?? '') === $choice)>{{ $field === 'entity_type' ? \Illuminate\Support\Str::headline($choice) : $choice }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="field">
                <label for="audit-per-page">Events per page</label>
                <select id="audit-per-page" name="per_page">@foreach([25, 50, 100] as $count)<option value="{{ $count }}" @selected((int) ($filters['per_page'] ?? 25) === $count)>{{ $count }}</option>@endforeach</select>
            </div>
            <div class="field audit-actor-filter">
                <div class="audit-actor-label"><label for="audit-actor">Performed by</label><x-area-info label="About actor filters">Names and IDs distinguish actors. Select deleted or unavailable actors for events without an actor reference. Filters match exact values; dates use {{ $timezone }}.</x-area-info></div>
                <select id="audit-actor" name="actor_user_id">
                    <option value="">All actors</option>
                    <option value="unavailable" @selected(($filters['actor_user_id'] ?? '') === 'unavailable')>Deleted or unavailable actor</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" @selected(($filters['actor_user_id'] ?? '') === $actor->id)>{{ $actor->display_name ?? 'Unavailable profile' }} ({{ $actor->id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="audit-date-from">Date from</label><input id="audit-date-from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="field"><label for="audit-date-to">Date to</label><input id="audit-date-to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"></div>
        </div>
        <div class="audit-filter-actions">
            <button class="button button-primary" type="submit">Apply filters</button>
            <a class="button" href="{{ route('admin.audit-logs.index') }}">Reset filters</a>
        </div>
    </fieldset>
</form>
