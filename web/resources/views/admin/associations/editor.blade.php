@php
    $geography = app(\App\Services\Associations\AssociationGeographyService::class);
    $directGeography = $geography->enabled();
    $associationAreas = collect($catalog['options']['area_records']);
    $associationEditorErrors = $errors->any() && old('association_form') === 'editor';
    $editingAssociation = $associationEditorRecord ?? ($associationEditorErrors ? collect($module['rows'])->firstWhere('id', old('association_record_id')) : null);
    $associationOld = fn ($key, $default = null) => $associationEditorErrors ? old($key, $default) : ($editingAssociation[match ($key) { 'saad_phase2_join_date' => 'joined_at', 'status' => 'status_value', default => $key }] ?? $default);
    $selectedAccreditation = $editingAssociation['selected_accreditation'] ?? null;
    $selectedAssociationArea = $associationAreas->firstWhere('value', $associationOld('area_id'));
    $associationUnavailable = $editingAssociation && ((!$directGeography && !$selectedAssociationArea)
        || !in_array($associationOld('primary_field_officer_id'), array_column($catalog['options']['field_officer_records'] ?? [], 'value'), true));
    $associationMunicipalities = $associationAreas->filter(fn ($area) => filled($area['municipality_id']))->unique('municipality_id');
    $selectedMunicipality = $selectedAssociationArea['municipality_id'] ?? '';
    $selectedBarangay = $selectedAssociationArea['barangay_id'] ?? '';
    if ($directGeography) {
        $associationMunicipalities = collect($catalog['options']['municipality_records']);
        $associationBarangays = collect($catalog['options']['barangay_records']);
        $selectedMunicipality = $associationOld('municipality_id', '');
        $selectedBarangay = $associationOld('barangay_id', '');
        $associationUnavailable = $editingAssociation && (
            !$associationMunicipalities->contains('value', $selectedMunicipality)
            || !$associationBarangays->where('municipality_id', $selectedMunicipality)->contains('value', $selectedBarangay)
            || (filled($associationOld('area_id')) && !$selectedAssociationArea)
            || !in_array($associationOld('primary_field_officer_id'), array_column($catalog['options']['field_officer_records'] ?? [], 'value'), true));
    }
@endphp
<x-modal id="association-editor" :title="$selectedAccreditation ? 'Update Registration' : ($editingAssociation ? 'Edit Association' : 'Add Association')">
    <form method="POST" action="{{ $editingAssociation['update_url'] ?? route('admin.associations.store') }}" data-form data-association-form
        data-create-action="{{ route('admin.associations.store') }}"
        data-area-records="{{ json_encode($catalog['options']['area_records']) }}"
        data-direct-geography="{{ $directGeography ? 'true' : 'false' }}"
        @if($directGeography) data-barangay-records="{{ json_encode($catalog['options']['barangay_records']) }}" @endif
        data-association-auto-open="{{ ($associationOpenEditor ?? false) ? 'true' : 'false' }}"
        data-has-errors="{{ $associationEditorErrors ? 'true' : 'false' }}" @if($associationEditorErrors) data-feedback-error-bag="default" @endif>
        @csrf
        {{-- Show progress only after the form is actually submitted. --}}
        <div class="association-saving" data-association-saving hidden role="status" aria-live="polite"><span class="association-saving-spinner" aria-hidden="true"></span>Saving association…</div>
        @unless($editingAssociation)<input type="hidden" name="registration_notice_accepted" value="" data-registration-notice-value>@endunless
        <input type="hidden" name="association_form" value="editor">
        <input type="hidden" name="_method" value="PATCH" data-association-method @disabled(!$editingAssociation)>
        <input type="hidden" name="association_record_id" value="{{ $editingAssociation['id'] ?? '' }}" @disabled(!$editingAssociation)>
        @if($editingAssociation)
            <input type="hidden" name="expected_primary_field_officer_id" value="{{ $associationOld('expected_primary_field_officer_id') }}">
        @endif
        @if($geography->usesDirectGeography() && $editingAssociation)
            <input type="hidden" name="expected_updated_at" value="{{ $associationOld('expected_updated_at') }}">
        @endif
        <p>Fields marked * are required.</p>
        @if(($editingAssociation['status_value'] ?? null) === 'inactive')<p class="notice">Use Active status to reactivate this association before making operational changes.</p>@endif
        @if($associationEditorErrors)
            <div class="notice notice-error" role="alert" data-association-validation>
                <x-icon name="info" />
                <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            </div>
        @endif
        <div class="form-grid">
            <x-field :field="['name' => 'name', 'label' => 'Association Name', 'required' => true]" prefix="association-editor" :value="$associationOld('name')" />
            <x-field :field="['name' => 'acronym', 'label' => 'Acronym', 'required' => false]" prefix="association-editor" :value="$associationOld('acronym')" />
            <div class="field">
                <label for="association-municipality">Municipality / City <span class="field-hint">*</span></label>
                <select id="association-municipality" data-association-municipality @if($directGeography) name="municipality_id" @endif required>
                    <option value="">Select municipality / city</option>
                    @foreach($associationMunicipalities as $area)
                        @php($municipalityId = $directGeography ? $area['value'] : $area['municipality_id'])
                        <option value="{{ $municipalityId }}" @selected($selectedMunicipality === $municipalityId)>{{ $directGeography ? $area['label'] : $area['municipality'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="association-barangay">Barangay <span class="field-hint">*</span></label>
                <select id="association-barangay" data-association-barangay @if($directGeography) name="barangay_id" @endif required>
                    <option value="">Select barangay</option>
                    @foreach(($directGeography ? $associationBarangays : $associationAreas)->where('municipality_id', $selectedMunicipality)->unique($directGeography ? 'value' : 'barangay_id') as $area)
                        @php($barangayId = $directGeography ? $area['value'] : $area['barangay_id'])
                        <option value="{{ $barangayId }}" @selected($selectedBarangay === $barangayId)>{{ $directGeography ? $area['label'] : $area['barangay'] }}</option>
                    @endforeach
                </select>
            </div>
            <x-field :field="['name' => 'area_id', 'label' => 'Specific Area', 'type' => 'select', 'choices' => $associationAreas->where('barangay_id', $selectedBarangay)->values()->all(), 'required' => !$directGeography, 'empty_label' => $directGeography ? 'No Specific Area' : 'Select specific area']" prefix="association-editor" :value="is_string($associationOld('area_id')) ? $associationOld('area_id') : ''" />
            <p class="field-help" data-association-no-areas hidden>No active Specific Areas are recorded for this barangay. Add one in Area Management, then reopen this form.</p>
            @if($directGeography)
                <div class="field field-wide">
                    <label for="association-specific-address">Association Address / Sitio / Purok <span class="field-hint">Optional</span></label>
                    <textarea id="association-specific-address" name="specific_address" maxlength="500" rows="3">{{ is_string($associationOld('specific_address')) ? $associationOld('specific_address') : '' }}</textarea>
                </div>
                <p class="field-wide notice" data-association-area-cleared role="status" hidden>The previous Specific Area was cleared because it does not belong to the selected Barangay. Review your selection before saving.</p>
            @endif
            <div class="field"><label for="association-editor-saad_phase2_join_date">SAAD Phase II Join Date <span class="field-hint">*</span></label><input id="association-editor-saad_phase2_join_date" name="saad_phase2_join_date" type="date" max="{{ now('Asia/Manila')->toDateString() }}" value="{{ $associationOld('saad_phase2_join_date') }}" required></div>
            <x-field :field="['name' => 'primary_field_officer_id', 'label' => 'Primary Field Officer', 'type' => 'select', 'choices' => $catalog['options']['field_officer_records'] ?? [], 'required' => true]" prefix="association-editor" :value="$associationOld('primary_field_officer_id')" />
            @if($editingAssociation['retained_officer'] ?? false)
                <p class="field-wide notice">The current officer is retained for historical continuity. You may keep this designation for other permitted edits. A replacement must be an eligible, activated Field Officer.</p>
            @endif
            <x-field :field="['name' => 'status', 'label' => 'Status', 'type' => 'select', 'choices' => [['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']], 'required' => true]" prefix="association-editor" :value="$associationOld('status', 'active')" />
        </div>
        @if($editingAssociation)
            @include('admin.associations.accreditation-editor')
        @endif
        @unless($editingAssociation)
        <div data-association-registration>
            <div class="association-registration-heading"><h3>Existing registration or accreditation</h3><x-area-info label="About existing registration">Enter the details from the association's existing certificate. AssocMap records these details; it does not issue or approve registrations.</x-area-info></div>
            {{-- An existing certificate is required for new associations. --}}
            <input type="checkbox" data-registration-toggle checked hidden tabindex="-1" aria-hidden="true">
            <fieldset class="form-grid" data-registration-fields>
                <legend class="sr-only">Registration details</legend>
                <div class="field"><label for="association-registration-agency">Registration agency *</label><select id="association-registration-agency" name="registration[agency_id]" required><option value="">Select agency</option>@foreach($catalog['options']['registration_agencies'] ?? [] as $agency)<option value="{{ $agency['value'] }}" @selected((string) $associationOld('registration.agency_id') === (string) $agency['value'])>{{ preg_replace('/\s*\(registration\)$/i', '', $agency['label']) }}</option>@endforeach</select></div>
                <div class="field"><label for="association-registration-number">Registration number *</label><input id="association-registration-number" name="registration[registration_number]" maxlength="150" value="{{ $associationOld('registration.registration_number') }}" required></div>
                <div class="field"><label for="association-registration-date">Registration date *</label><input id="association-registration-date" name="registration[registration_date]" type="date" value="{{ $associationOld('registration.registration_date') }}" required></div>
                <div class="field"><label for="association-registration-expiration">Expiration date (optional)</label><input id="association-registration-expiration" name="registration[expiration_date]" type="date" value="{{ $associationOld('registration.expiration_date') }}"></div>
                <div class="field"><div class="association-primary-label"><label for="association-registration-primary">Primary registration</label><x-area-info label="About primary registration">Choose Yes if this is the association's main registration record. Its registration date must not be later than the SAAD Phase II joining date.</x-area-info></div><select id="association-registration-primary" name="registration[is_primary]"><option value="0">No</option><option value="1" @selected($associationOld('registration.is_primary') == '1')>Yes</option></select></div>
                <div class="field"><label for="association-registration-remarks">Remarks (optional)</label><textarea id="association-registration-remarks" name="registration[remarks]" maxlength="4000" rows="2">{{ $associationOld('registration.remarks') }}</textarea></div>
            </fieldset>
            @if(empty($catalog['options']['registration_agencies']))<p class="notice">No active registration agencies are available. Contact an administrator before adding an association.</p>@endif
        </div>
        @endunless
        <p class="notice notice-error" role="alert" data-association-unavailable @if(!$associationUnavailable) hidden @endif>{{ $directGeography ? 'A recorded geographic choice or Field Officer is unavailable. Review the selections before saving.' : 'The current area or Field Officer is no longer an available active choice. Select an active Specific Area and Field Officer before saving.' }}</p>
        @if(!$directGeography && $associationAreas->isEmpty())<p class="notice notice-error" role="alert">No active Specific Areas are available. Add an area before creating an association.</p>@endif
        @if(empty($catalog['options']['field_officer_records']))<p class="notice notice-error" role="alert">No active Field Officers are available.</p>@endif
        <div class="modal-actions">
            @unless($editingAssociation)<label class="field-help" data-registration-confirmed hidden><input type="checkbox" data-registration-confirmed-checkbox checked disabled> Registration notice acknowledged</label>@endunless
            <button type="button" class="button" data-close-dialog>Cancel</button>
            <button type="submit" class="button button-primary" data-association-submit @disabled((!$directGeography && $associationAreas->isEmpty()) || empty($catalog['options']['field_officer_records']))>{{ $selectedAccreditation ? 'Update Accreditation' : ($editingAssociation ? 'Save Changes' : 'Save Association') }}</button>
        </div>
    </form>
</x-modal>
{{-- Confirm that the details describe an existing external record. --}}
@unless($editingAssociation)
<dialog id="association-registration-notice" class="modal" aria-labelledby="association-registration-notice-title">
    <div class="modal-heading"><h2 id="association-registration-notice-title">Confirm registration details</h2></div>
    <p>AssocMap records information from an existing registration or accreditation. It does not issue a certificate, register an association with an agency, or approve participation in SAAD Phase II.</p>
    <p>Before continuing, check that the issuing agency, certificate number and dates match the association's documents. Follow the applicable BFAR SAAD procedures separately.</p>
    <label><input type="checkbox" data-registration-notice-check> I have reviewed this notice and will enter the details from the association's existing document.</label>
    <div class="modal-actions"><button type="button" class="button" data-registration-notice-cancel>Cancel</button><button type="button" class="button button-primary" data-registration-notice-accept disabled>OK</button></div>
</dialog>
@endunless
