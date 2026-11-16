<section class="gis-module" data-gis-workspace data-workspace-module="gis" data-workspace-title="GIS Mapping"
    data-gis-base="{{ $gisBaseUrl }}" data-gis-photos="true" data-gis-public="{{ $gisPublic ? 'true' : 'false' }}" data-gis-manager="{{ $gisManager ? 'true' : 'false' }}" data-gis-administrator="{{ ($gisAdministrator ?? false) ? 'true' : 'false' }}">
    @component('partials.page-header', ['headingCard' => true, 'headingClass' => 'gis-heading', 'title' => ($gisAdministrator ?? false) ? 'GIS Management' : $gisTitle, 'description' => ($gisAdministrator ?? false) ? 'Explore and manage project locations and boundaries.' : $gisDescription, 'eyebrow' => $gisPublic ? 'Explore AssocMap' : 'Geographic information'])
        @if($gisAdministrator ?? false)@slot('headerContext')<div class="gis-header-info"><span>About GIS records</span><x-area-info label="About GIS record history">History includes locations of archived associations and projects. Historical locations allow geometry correction only. The Lifecycle filter applies to the GIS location itself.</x-area-info></div>@endslot @endif
        @slot('actions')
        @if($gisManager)<a href="{{ $gisBaseUrl }}/create" class="button button-primary"><x-icon name="plus" />Add GIS location</a>@endif
        @unless($gisManager)<span class="gis-access-label"><x-icon name="eye" />{{ $gisPublic ? 'Public viewer' : 'Read-only viewer' }}</span>@endunless
        @endslot
    @endcomponent
    <div class="card gis-search-panel">
        <div class="gis-toolbar">
            <div class="gis-view-switch" role="group" aria-label="GIS view">
                <button type="button" class="button" data-gis-view="map" aria-controls="gis-map-panel" aria-pressed="true"><x-icon name="map" />Map</button>
                <button type="button" class="button" data-gis-view="list" aria-controls="gis-list-panel" aria-pressed="false"><x-icon name="scroll-text" />{{ $gisManager ? 'Registered locations' : 'Location list' }}</button>
            </div>
            <div class="gis-filter-actions" role="group" aria-label="GIS record controls">
                <button type="button" class="button" data-gis-reload title="Reload records and fit matching locations in map view"><x-icon name="map-pinned" />Refresh records</button>
                <button type="button" class="button" data-gis-open-details aria-controls="gis-location-panel" disabled><x-icon name="info" />Location details</button>
                <button type="button" class="button" data-gis-retry hidden>Retry GIS records</button>
            </div>
        </div>
        <form data-gis-filters>
            <div class="gis-search-bar">
                <div class="field gis-search"><label class="sr-only" for="gis-search">Search {{ $gisPublic ? 'approved titles and addresses' : 'locations, associations and geography' }}</label><div class="search-box"><x-icon name="search" /><input id="gis-search" name="q" type="search" maxlength="100" placeholder="Search {{ $gisPublic ? 'published locations' : 'locations, projects or addresses' }}…" aria-describedby="gis-search-help"></div><small class="sr-only" id="gis-search-help">Results update as you type. Refresh records reloads and fits matching locations in map view.</small></div>
                <button type="submit" class="button button-primary">Search</button><button type="reset" class="button">Reset</button>
            </div>
            <details class="gis-filters">
                <summary><span>@if($gisAdministrator ?? false)<svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.446.248 8.057.722.73.132.943 1.059.417 1.585L15 10.78v5.72a.75.75 0 0 1-.332.624l-4.5 3A.75.75 0 0 1 9 19.5v-8.72L3.526 5.307c-.526-.526-.313-1.453.417-1.585A45.12 45.12 0 0 1 12 3Z" /></svg>@else<x-icon name="filter" />@endif Map filters</span><span class="gis-filter-indicator" data-gis-filter-summary>No additional filters</span><x-icon name="chevron-down" /></summary>
                <div class="gis-filter-grid">
                    <div class="field"><label for="gis-type">Geometry type</label><select id="gis-type" name="location_type"><option value="">All project geometry types</option><option value="project_point">Project Point</option><option value="project_boundary">Project Boundary</option></select></div>
                    <div class="field"><label for="gis-site-type">Site classification</label><select id="gis-site-type" name="site_type_id"><option value="">All site classifications</option><option value="unclassified">Unclassified</option>@foreach($gisSiteTypes as $siteType)<option value="{{ $siteType['id'] }}">{{ $siteType['name'] }}</option>@endforeach</select><small>Physical site type, independent of geometry and fisheries category.</small></div>
                    @if($gisPublic)
                        <div class="field"><label for="gis-association">Related locations</label><select id="gis-association" name="association_location_id" data-gis-parent="association"><option value="">All published locations</option></select><small>Select a published location to find other published locations linked to the same association.</small></div>
                    @else
                        @foreach(['municipality' => 'Municipality', 'barangay' => 'Barangay', 'area' => 'Specific area', 'association' => 'Association', 'project' => 'Project'] as $kind => $label)
                            <div class="field"><label for="gis-filter-{{ $kind }}">{{ $label }}</label><select id="gis-filter-{{ $kind }}" name="{{ $kind }}_id" data-gis-parent="{{ $kind }}" @if(in_array($kind, ['municipality', 'barangay', 'area'])) aria-describedby="gis-geography-help" @endif><option value="">All {{ strtolower($label) }} records in scope</option></select></div>
                        @endforeach
                        <p id="gis-geography-help" class="gis-help gis-filter-help">Municipality, barangay and specific area use the association's registered geography. They do not verify spatial containment of its project site.</p>
                    @endif
                    <div class="field"><label for="gis-parent-search">Find {{ $gisPublic ? 'approved project-location titles' : 'parent and geographic options' }}</label><input id="gis-parent-search" type="search" maxlength="100" data-gis-option-search aria-describedby="gis-option-note"><small id="gis-option-note" data-gis-option-note role="status">Options are bounded. Search to find additional records.</small></div>
                    @if($gisManager)
                        <div class="field"><label for="gis-publication">Publication</label><select id="gis-publication" name="publication"><option value="">All publication states</option><option value="published">Published</option><option value="unpublished">Unpublished</option></select></div>
                        <div class="field"><label for="gis-lifecycle">Lifecycle</label><select id="gis-lifecycle" name="lifecycle"><option value="active">Active records</option><option value="archived">Archived records</option></select></div>
                    @endif
                </div>
            </details>
        </form>
    </div>
    <p class="notice gis-status" data-gis-status role="status" aria-live="polite" aria-atomic="true">Loading GIS records…</p>
    <p class="notice" data-gis-tile-notice hidden>Basemap tiles are unavailable. Saved locations and the location list remain accessible.</p>
    <div class="gis-spatial-grid">
        <div class="card gis-map-panel" id="gis-map-panel" data-gis-map-panel>
            <div class="gis-panel-heading gis-map-heading">
                <h2><x-icon name="map" />Street map</h2>
                <details class="gis-map-legend">
                    <summary><x-icon name="layers" />Legend<x-icon name="chevron-down" /></summary>
                    <div class="gis-legend-content">
            <ul class="gis-legend" aria-label="Map legend">
                <li class="gis-legend-title">Geometry representation</li>
                <li><i class="gis-key project" aria-hidden="true"></i>Project Point — saved point coordinates</li>
                @if($gisPublic)<li><i class="gis-boundary-badge" aria-hidden="true"></i>Project Boundary — calculated representative marker</li>@endif
                @unless($gisPublic)<li><i class="gis-key boundary" aria-hidden="true"></i>Project Boundary — actual project-site area (all classifications)</li>@endunless
                <li class="gis-legend-title">Physical site classification</li>
                <li><span class="gis-legend-symbol" data-gis-site-symbol="" aria-hidden="true"></span>{{ $gisPublic ? 'Unclassified location' : 'Unclassified point' }}</li>
                @foreach($gisSiteTypes as $siteType)<li><span class="gis-legend-symbol" data-gis-site-symbol="{{ $siteType['code'] }}" aria-hidden="true"></span>{{ $siteType['name'] }}{{ $gisPublic ? '' : ' (point)' }}</li>@endforeach
            </ul>
                    </div>
                </details>
            </div>
            <div class="gis-map" data-gis-map role="region" aria-label="Interactive GIS map" aria-describedby="gis-map-help"></div>
            <details class="gis-map-guide"><summary><x-icon name="info" />About this map</summary><p class="gis-help" id="gis-map-help">@if($gisPublic)Boundary markers show calculated representative positions, not surveyed site coordinates. @endif Zoom or filter to narrow the current view. Use the list to browse all records in scope.</p></details>
        </div>
        <div class="card gis-register-panel" id="gis-list-panel" data-gis-list-panel hidden>
            <div class="gis-panel-heading"><h2><x-icon name="scroll-text" />{{ $gisManager ? 'GIS register' : 'Location list' }}</h2><span class="gis-panel-caption">20 per page</span></div>
            <div data-gis-list></div>
            <nav class="gis-pagination" aria-label="Location list pages"><button type="button" class="button" data-gis-page="previous" disabled>Previous</button><span data-gis-page-label></span><button type="button" class="button" data-gis-page="next" disabled>Next</button></nav>
        </div>
        <aside class="card gis-location-panel" id="gis-location-panel" aria-labelledby="gis-details-title">
            <div class="gis-panel-heading"><h2 id="gis-details-title">Location details</h2><button type="button" class="button gis-dismiss" data-gis-dismiss aria-label="Clear selected location" title="Clear selection" disabled><x-icon name="close" /></button></div>
            <div class="gis-location-content" role="region" aria-labelledby="gis-details-title" tabindex="0">
                <div class="gis-selection-heading">
                    <span class="gis-selection-icon" data-gis-selection-symbol data-gis-site-symbol="" aria-hidden="true"></span>
                    <div class="gis-selection-intro"><span class="gis-selection-eyebrow" data-gis-selection-eyebrow>Explore the map</span><h3 data-gis-selection-title tabindex="-1">A closer look starts here</h3></div>
                    <div class="gis-selection-badges" data-gis-selection-badges hidden><span class="gis-detail-badge" data-gis-classification></span><span class="gis-detail-badge gis-geometry-badge" data-gis-geometry></span></div>
                    <p data-gis-selection-note role="status" aria-live="polite">Choose a marker or a list result to explore its details and photos.</p>
                </div>
                <div data-gis-details></div>
                @if($gisManager)<details class="gis-history" data-gis-history hidden><summary>Record history and public details</summary><dl data-gis-history-details></dl></details>@endif
                <div class="gis-record-actions" data-gis-record-actions></div>
                @include('components.gis-photos')
            </div>
        </aside>
    </div>
    <dialog class="gis-details-drawer" id="gis-details-drawer" data-gis-details-drawer aria-labelledby="gis-details-title">
        <div data-gis-drawer-content></div>
        <div class="gis-drawer-footer"><button type="button" class="button" data-gis-close-details><x-icon name="map" />Back to map</button></div>
    </dialog>
    @if($gisManager)
        <input type="hidden" data-gis-csrf value="{{ csrf_token() }}">
        <dialog class="modal gis-confirm" data-gis-confirm aria-labelledby="gis-confirm-title" aria-describedby="gis-confirm-message">
            <h2 id="gis-confirm-title" data-gis-confirm-title>Review GIS action</h2><p id="gis-confirm-message" data-gis-confirm-message></p><dl data-gis-confirm-preview></dl>
            <div class="modal-actions"><button type="button" class="button" data-gis-confirm-cancel>Cancel</button><button type="button" class="button button-primary" data-gis-confirm-submit>Confirm</button></div>
        </dialog>
    @endif
    <noscript><p class="notice">Enable JavaScript to use the GIS map, record list, and controls.</p></noscript>
</section>
