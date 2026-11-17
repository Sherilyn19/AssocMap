@php
    $hasAreaErrors = $errors->any() && old('area_form') === 'editor';
    $areaOld = fn ($key, $default = null) => $hasAreaErrors ? old($key, $default) : $default;
    $areaCreateTypes = $areaCreateTypes ?? ['Municipality', 'Barangay', 'Specific Area'];
    $areaType = $areaOld('area_type', $areaOld('barangay_id') ? 'Specific Area' : ($areaOld('municipality_id') ? 'Barangay' : 'Municipality'));
    $areaType = in_array($areaType, ['Municipality', 'Barangay', 'Specific Area']) ? $areaType : 'Municipality';
    if (!in_array($areaType, $areaCreateTypes)) $areaCreateTypes[] = $areaType;
    $editingArea = collect($catalog['modules']['areas']['rows'] ?? [])->first(fn ($row) =>
        $areaType !== 'Specific Area' && $row['id'] === $areaOld('area_record_id') &&
        ($row['area_type'] === 'Barangay') === ($areaType === 'Barangay'));
    $municipalityChoices = $catalog['options']['municipality_records'] ?? [];
    $barangayChoices = $catalog['options']['barangay_records'] ?? [];
    $specificMunicipality = $areaOld('municipality_id', collect($barangayChoices)->firstWhere('value', $areaOld('barangay_id'))['municipality_id'] ?? '');
    if ($editingArea && $areaType === 'Barangay' && !in_array($editingArea['municipality_id'], array_column($municipalityChoices, 'value'))) {
        $municipalityChoices[] = ['value' => $editingArea['municipality_id'], 'label' => $editingArea['municipality']];
    }
@endphp
<x-modal id="area-editor" :title="$editingArea ? 'Edit Area' : 'Add Area'">
    <form
        method="POST"
        action="{{ $editingArea['update_url'] ?? ($areaType === 'Specific Area' ? route('admin.areas.store') : ($areaType === 'Barangay' ? route('admin.barangays.store') : route('admin.municipalities.store'))) }}"
        data-municipality-action="{{ route('admin.municipalities.store') }}"
        data-barangay-action="{{ route('admin.barangays.store') }}"
        data-specific-area-action="{{ route('admin.areas.store') }}"
        data-barangay-records="{{ json_encode($barangayChoices) }}"
        data-form
        data-area-form
        data-mode="{{ $editingArea ? 'edit' : 'create' }}"
        data-update-action="{{ $editingArea['update_url'] ?? '' }}"
        data-has-errors="{{ $hasAreaErrors ? 'true' : 'false' }}"
        @if($hasAreaErrors) data-feedback-error-bag="default" @endif
    >
        @csrf
        <input type="hidden" name="area_form" value="editor">
        <input type="hidden" name="_method" value="PATCH" data-area-method @disabled(!$editingArea)>
        <input type="hidden" name="area_record_id" value="{{ $editingArea['id'] ?? '' }}" @disabled(!$editingArea)>
        <input type="hidden" name="area_type" value="{{ $areaType }}" data-area-edit-type @disabled(!$editingArea)>

        @if ($hasAreaErrors)
            <div class="notice notice-error" role="alert" data-area-validation>
                <x-icon name="info" />

                <div>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="form-grid">
            <div class="field-wide">
                <x-field
                    :field="['name' => 'area_type', 'label' => 'Area Type', 'type' => 'select', 'choices' => $areaCreateTypes, 'required' => true]"
                    prefix="area-editor"
                    :value="$areaType"
                />
            </div>
            <div class="field-wide" data-area-fields="Municipality" @if($areaType !== 'Municipality') hidden @endif>
                <x-field
                    :field="[
                        'name' => 'name',
                        'label' => 'Municipality / City Name',
                        'required' => true
                    ]"
                    prefix="area-editor-municipality"
                    :value="$areaOld('name')"
                />
            </div>

            <div class="form-grid field-wide" data-area-fields="Barangay" @if($areaType !== 'Barangay') hidden @endif>
                <x-field
                    :field="['name' => 'municipality_id', 'label' => 'Municipality / City', 'type' => 'select', 'choices' => $municipalityChoices, 'required' => true]"
                    prefix="area-editor-barangay"
                    :value="$areaOld('municipality_id')"
                />
                <x-field
                    :field="['name' => 'name', 'label' => 'Barangay Name', 'required' => true]"
                    prefix="area-editor-barangay"
                    :value="$areaOld('name')"
                />
            </div>

            <div class="form-grid field-wide" data-area-fields="Specific Area" @if($areaType !== 'Specific Area') hidden @endif>
                <div class="field">
                    <label for="area-editor-specific-municipality">Municipality / City <span class="field-hint">*</span></label>
                    <select id="area-editor-specific-municipality" name="municipality_id" data-specific-municipality required>
                        <option value="">Select…</option>
                        @foreach($catalog['options']['specific_municipality_records'] ?? $catalog['options']['municipality_records'] ?? [] as $choice)
                            <option value="{{ $choice['value'] }}" @selected($specificMunicipality === $choice['value'])>{{ $choice['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <x-field
                    :field="['name' => 'barangay_id', 'label' => 'Barangay', 'type' => 'select', 'choices' => array_values(array_filter($barangayChoices, fn ($choice) => $choice['municipality_id'] === $specificMunicipality)), 'required' => true]"
                    prefix="area-editor-specific"
                    :value="$areaOld('barangay_id')"
                />
                <x-field
                    :field="['name' => 'address', 'label' => 'Address / Sitio / Location', 'type' => 'textarea', 'required' => true]"
                    prefix="area-editor-specific"
                    :value="$areaOld('address')"
                />
            </div>

            {{-- Keep ZIP codes separate from PSGC codes for every area type. --}}
            <div class="field"><label for="area-editor-zip">ZIP Code <span class="field-hint">*</span></label><input id="area-editor-zip" name="zip_code" type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" value="{{ $areaOld('zip_code', $editingArea['zip_code'] ?? '') }}" required></div>

            <div class="form-grid field-wide area-reference-fields" data-area-reference-fields @if($areaType === 'Specific Area') hidden @endif>
            <div class="area-territory field-wide"><p class="area-territory-label">Parent administrative territory</p><div class="area-territory-grid">
                <div><span>Province</span><strong data-area-editor-detail="province">{{ $editingArea['province'] ?? 'Cebu' }}</strong></div>
                <div><span>Region</span><strong data-area-editor-detail="region">{{ $editingArea['region'] ?? 'Region VII' }}</strong></div>
            </div></div>

            <x-field
                :field="[
                    'name' => 'psgc_code',
                    'label' => 'PSGC Code',
                    'required' => false
                ]"
                prefix="area-editor"
                :value="$areaOld('psgc_code')"
            />

            <div class="field area-source">
                <label>Source</label>
                <div class="form-static-value"><span data-area-editor-detail="source">{{ $editingArea['source'] ?? 'Manual' }}</span></div>
            </div>

            <div class="area-status field-wide">
                <div><strong>Area operational status</strong><p>Current status of this geographic record.</p></div>
                <span class="area-status-badge" data-area-editor-detail="status">{{ $editingArea['status'] ?? 'Active' }}</span>
            </div>
            </div>
        </div>

        <div class="modal-actions">
            <button
                type="button"
                class="button"
                data-close-dialog
            >
                Cancel
            </button>

            <button
                type="submit"
                class="button button-primary"
                data-area-submit
            >
                {{ $editingArea ? 'Save Changes' : ($areaType === 'Specific Area' ? 'Save Area' : ($areaType === 'Barangay' ? 'Save Barangay' : 'Save Municipality')) }}
            </button>
        </div>
    </form>
</x-modal>

<x-modal id="area-modal" title="Area Details">
    <dl class="detail-grid">
        <div>
            <dt>Area Name</dt>
            <dd data-area-detail="name">—</dd>
        </div>

        <div>
            <dt>Type</dt>
            <dd>
                <span
                    class="badge badge-neutral"
                    data-area-detail="area_type"
                >
                    —
                </span>
            </dd>
        </div>

        <div>
            <dt>Municipality / City</dt>
            <dd data-area-detail="municipality">—</dd>
        </div>

        <div>
            <dt>PSGC Code</dt>
            <dd data-area-detail="psgc_code">—</dd>
        </div>
        <div hidden><dt>Barangay</dt><dd data-area-detail="barangay">—</dd></div>

        <div data-area-province>
            <dt>Province</dt>
            <dd data-area-detail="province">—</dd>
        </div>

        <div data-area-region>
            <dt>Region</dt>
            <dd data-area-detail="region">—</dd>
        </div>

        <div>
            <dt>Source</dt>
            <dd>
                <span
                    class="badge badge-neutral"
                    data-area-detail="source"
                >
                    —
                </span>
            </dd>
        </div>

        <div>
            <dt>Status</dt>
            <dd>
                <span
                    class="badge badge-neutral"
                    data-area-detail="status"
                >
                    —
                </span>
            </dd>
        </div>
        <div><dt>Associations</dt><dd data-area-detail="associations_count">—</dd></div>
    </dl>
</x-modal>
