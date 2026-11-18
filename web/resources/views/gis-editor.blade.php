@extends($gisLayout)
@section('content')
    @php $editing = $gisFeature !== null; $record = $gisFeature['properties'] ?? []; $correcting = $gisCorrection ?? false; @endphp
    <section class="gis-module" data-gis-editor data-workspace-module="gis" data-workspace-title="GIS Mapping" data-gis-base="{{ $gisBaseUrl }}">
        <header class="card gis-heading"><div><div class="eyebrow">{{ $correcting ? 'Administrator · Geometry correction' : 'GIS location editor' }}</div><h1 tabindex="-1">{{ $correcting ? 'Correct historical GIS geometry' : ($editing ? 'Edit GIS location' : 'Add GIS location') }}</h1><p>{{ $correcting ? 'Correct the saved point or boundary. Location ownership, publication, and operational history remain unchanged.' : 'Select the correct parent and provide actual geographic coordinates or a project boundary.' }}</p></div></header>
        {{-- Place the path guide below the heading. --}}
        <x-workflow-navigation :back="['label' => 'GIS Mapping', 'url' => $gisBaseUrl]" :parents="[['label' => 'GIS Mapping', 'url' => $gisBaseUrl]]" :current="$correcting ? 'Correct geometry' : ($editing ? 'Edit GIS location' : 'Add GIS location')" />
        <p class="notice" data-gis-editor-status role="status" aria-live="polite" aria-atomic="true">Loading the geometry editor…</p>
        @if($correcting)<p class="notice">This operation changes geometry only and records an audit event. Archived associations remain archived. Public visibility continues to follow the existing publication and parent-lifecycle restrictions.</p>
        @elseif($record['is_published'] ?? false)<p class="notice">This location is published. Saving updates its geometry, site classification and approved public details in the public viewer.</p>@endif
        @if(!$correcting && ($record['project_finalized'] ?? false))<p class="notice">This project is finalized. GIS details and publication may be maintained. Its project and association ownership cannot change, and this location cannot be deleted.</p>@endif
        <form data-gis-editor-form data-gis-correction="{{ $correcting ? 'true' : 'false' }}" method="POST" action="{{ $correcting ? route('admin.gis.coordinates.update', $gisFeature['id']) : ($editing ? $gisBaseUrl.'/'.$gisFeature['id'] : $gisBaseUrl) }}">
            @csrf
            @if($editing)<input type="hidden" name="_method" value="PATCH"><input type="hidden" name="version" value="{{ $record['version'] }}">
            @else<input type="hidden" name="idempotency_key" value="{{ $gisRequestId }}">@endif
            <div class="gis-editor-grid">
                <div class="card gis-editor-fields">
                    <h2><x-icon name="map-pin" />Location details</h2>
                    @if($correcting)<fieldset disabled><legend>Location details (read only)</legend>@endif
                    <div class="field"><label for="gis-location-type">Geometry type</label><select id="gis-location-type" name="location_type" required aria-describedby="gis-geometry-type-help"><option value="project_point">Project Point</option><option value="project_boundary">Project Boundary</option></select><small id="gis-geometry-type-help">Choose Project Point to mark the site, or Project Boundary to outline only the project site.</small></div>
                    {{-- Use the existing dropdowns for the association and project. --}}
                    <div class="field"><label for="gis-editor-association">Association</label><select id="gis-editor-association" data-gis-editor-association aria-describedby="gis-parent-option-note" disabled required><option value="">Select an association</option></select><small id="gis-parent-option-note" data-gis-option-note role="status">Only associations you can manage are shown.</small><button type="button" class="button" data-gis-association-retry hidden>Retry associations</button></div>
                    <input type="hidden" name="parent_association_id" value="">
                    <div class="field" data-gis-project-field><label for="gis-editor-project">Project</label><select id="gis-editor-project" name="project_id" aria-describedby="gis-project-status" disabled required><option value="">Select an association first</option></select><small id="gis-project-status" data-gis-project-status role="status">Select an association to load its projects.</small><button type="button" class="button" data-gis-project-retry hidden>Retry projects</button></div>
                    <div class="field"><label for="gis-location-name">Internal location name</label><input id="gis-location-name" name="location_name" maxlength="255" value="{{ $record['title'] ?? '' }}" required></div>
                    <div class="field"><label for="gis-editor-site-type">Site classification (optional)</label><select id="gis-editor-site-type" name="site_type_id" aria-describedby="gis-site-type-help"><option value="">Unclassified</option>@foreach($gisSiteTypes as $siteType)<option value="{{ $siteType['id'] }}" @selected(($record['site_type_id'] ?? null) === $siteType['id'])>{{ $siteType['name'] }}</option>@endforeach</select><small id="gis-site-type-help">Classifies the physical site or facility independently of its geometry and project category. @if($gisSiteTypes === [])No site classifications have been approved yet.@elseThe classification is visible with published locations.@endif</small></div>
                    <div class="field">
                        <label for="gis-registered-area">Registered Area</label>
                        <textarea id="gis-registered-area" rows="3" readonly data-gis-registered-area aria-describedby="gis-registered-area-status"></textarea>
                        <small id="gis-registered-area-status" data-gis-registered-area-status role="status">Select an association to load its registered area. This is association-area context, not a verified project site.</small>
                    </div>
                    <div class="field">
                        <label for="gis-internal-address">Project Site Address (optional)</label>
                        <textarea id="gis-internal-address" name="address" maxlength="2000" rows="2" aria-describedby="gis-site-address-note">{{ $record['address'] ?? '' }}</textarea>
                        <small id="gis-site-address-note">Enter the project site's verified address. {{ $editing ? 'Check that the saved address is for the project site.' : 'Leave it blank if you are unsure.' }} Add public details and the map location separately.</small>
                    </div>
                    <fieldset class="gis-public-fields"><legend>Approved public details</legend>
                        <div class="field"><label for="gis-public-title">Public title</label><input id="gis-public-title" name="public_title" maxlength="255" value="{{ $record['public_title'] ?? '' }}" @if($record['is_published'] ?? false) required @endif></div>
                        <div class="field"><label for="gis-public-description">Public description</label><textarea id="gis-public-description" name="public_description" maxlength="4000" rows="3" aria-describedby="gis-public-description-help">{{ $record['public_description'] ?? '' }}</textarea><small id="gis-public-description-help">Include the association name here only if it is approved for public display.</small></div>
                        <div class="field"><label for="gis-public-address">Public address</label><textarea id="gis-public-address" name="public_address" maxlength="2000" rows="2">{{ $record['public_address'] ?? '' }}</textarea></div>
                    </fieldset>
                    @if($correcting)</fieldset>@endif
                </div>
                <div class="card gis-editor-spatial">
                    <h2><x-icon name="layers" />Geometry <span class="gis-draft-state" data-gis-draft-state role="status" aria-live="polite">{{ $editing ? 'Saved geometry' : 'New location' }}</span></h2><p class="gis-help" id="gis-geometry-help" data-gis-geometry-help>Click the map to select a point, or draw and edit boundary polygons. Nothing is saved until you submit the form.</p>
                    <div class="gis-map gis-editor-map" data-gis-editor-map role="region" aria-label="GIS geometry selection and boundary editor" aria-describedby="gis-geometry-help"></div>
                    <p class="notice" data-gis-tile-notice hidden>Basemap tiles are unavailable. Coordinate fields and GeoJSON editing remain available.</p>
                    <section class="gis-address-search" aria-labelledby="gis-address-search-title">
                        <h3 id="gis-address-search-title">Find a location</h3>
                        <p>Search for an address or landmark, then check the location on the map. Results can be approximate.</p>
                        <div class="gis-address-search-controls"><label class="sr-only" for="gis-address-search-input">Address or landmark</label><input id="gis-address-search-input" type="search" maxlength="200" autocomplete="off" placeholder="Address or landmark, municipality" data-gis-address-input><button class="button" type="button" data-gis-address-search>Search</button><button class="button gis-locate-button" type="button" data-gis-locate aria-describedby="gis-locate-help">Use current location</button></div>
                        <small id="gis-locate-help">At the project site? Use your device location, then review its accuracy before choosing it. Your location is not saved until you save the form.</small>
                        <div class="gis-location-result" data-gis-location-result hidden><p data-gis-location-status role="status" aria-live="polite"></p><div class="gis-location-actions"><button type="button" class="button button-primary" data-gis-location-use hidden>Use this location</button><button type="button" class="button" data-gis-location-cancel>Cancel</button></div></div>
                        <p class="gis-address-search-status" data-gis-address-status role="status" aria-live="polite"></p>
                        <ul class="gis-address-results" data-gis-address-results aria-label="Address search results"></ul>
                    </section>
                    <div class="gis-coordinate-grid" data-gis-coordinate-fields>
                        <div class="field"><label for="gis-longitude">Longitude</label><input id="gis-longitude" type="number" min="-180" max="180" step="any" data-gis-longitude></div>
                        <div class="field"><label for="gis-latitude">Latitude</label><input id="gis-latitude" type="number" min="-90" max="90" step="any" data-gis-latitude></div>
                    </div>
                    <div class="gis-editor-tools"><button type="button" class="button" data-gis-fit><x-icon name="map-pinned" />Fit current geometry</button><button type="button" class="button" data-gis-clear>Clear draft geometry</button></div>
                    <div class="field"><label for="gis-geometry-json">GeoJSON geometry</label><textarea id="gis-geometry-json" name="geometry" rows="7" spellcheck="false" maxlength="131072" aria-describedby="gis-json-help" required></textarea><small id="gis-json-help">Coordinates use longitude, latitude. Supply a geometry object, with at most 2,000 positions. Multiple polygon parts and existing holes are preserved.</small></div>
                    <button type="button" class="button" data-gis-preview>Preview GeoJSON</button>
                </div>
            </div>
            <div class="gis-save-actions"><p>Changes are saved only after successful validation.</p><a class="button" href="{{ $gisBaseUrl }}">Cancel</a><button type="submit" class="button button-primary" data-gis-save disabled>{{ $correcting ? 'Save geometry correction' : ($editing ? 'Save changes' : 'Create unpublished location') }}</button></div>
        </form>
        <script type="application/json" data-gis-seed>{!! json_encode($gisFeature, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
        <noscript><p class="notice">Enable JavaScript to select parents, edit geometry, and save this location.</p></noscript>
    </section>
@endsection
