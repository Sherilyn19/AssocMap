{{-- The latest verified renewal details can be updated without storing a renewal history. --}}
<section class="association-documents" aria-label="Existing registrations and accreditations">
    <div class="association-documents-heading"><span class="association-documents-icon" aria-hidden="true"><x-icon name="clipboard-check" /></span><div><h3>Existing registrations and accreditations</h3><p>Record details from the existing document. AssocMap does not issue or renew it.</p></div></div>
    @forelse($editingAssociation['accreditations'] ?? [] as $item)
        <div class="association-document-row">
            <div><strong>{{ $item['agency'] }}</strong><span>{{ $item['registration_number'] }}</span></div>
            @if($item['agency_active'] && ($editingAssociation['status_value'] ?? null) === 'active')
                <a class="button association-document-action" href="{{ $item['update_url'] }}"><x-icon name="pencil" />Update details</a>
            @else
                <span>Read-only while the association or issuing agency is inactive.</span>
            @endif
        </div>
    @empty
        <p class="association-document-empty">No registration or accreditation records are available.</p>
    @endforelse
    @if($selectedAccreditation)
        @php
            $accreditationValue = function ($key) use ($associationEditorErrors, $selectedAccreditation) {
                $value = $associationEditorErrors ? old('registration.'.$key, $selectedAccreditation[$key] ?? null) : ($selectedAccreditation[$key] ?? null);
                return is_scalar($value) ? (string) $value : '';
            };
        @endphp
        {{-- Keep the grid inside the fieldset so it fits the modal. --}}
        <fieldset class="association-document-fields">
            <legend>Update registration details</legend>
            <div class="form-grid association-document-grid">
            <input type="hidden" name="registration[id]" value="{{ $selectedAccreditation['id'] }}">
            <input type="hidden" name="registration[expected_updated_at]" value="{{ $accreditationValue('expected_updated_at') }}">
            <div class="association-document-meta field-wide"><span>Issuing agency <strong>{{ $selectedAccreditation['agency'] }}</strong></span><span>Primary <strong>{{ $selectedAccreditation['is_primary'] ? 'Yes' : 'No' }}</strong></span></div>
            <div class="field"><label for="accreditation-number">Registration number *</label><input id="accreditation-number" name="registration[registration_number]" maxlength="150" value="{{ $accreditationValue('registration_number') }}" required></div>
            <div class="field"><label for="accreditation-date">Registration date *</label><input id="accreditation-date" name="registration[registration_date]" type="date" value="{{ $accreditationValue('registration_date') }}" required></div>
            <div class="field"><label for="accreditation-expiration">Expiration date (optional)</label><input id="accreditation-expiration" name="registration[expiration_date]" type="date" value="{{ $accreditationValue('expiration_date') }}"></div>
            <div class="field"><label for="registration-latest-renewal">Latest renewal date (optional)</label><input id="registration-latest-renewal" name="registration[latest_renewal_date]" type="date" max="{{ now('Asia/Manila')->toDateString() }}" value="{{ $accreditationValue('latest_renewal_date') }}"></div>
            <div class="field"><label for="registration-renewal-count">Number of renewals (optional)</label><input id="registration-renewal-count" name="registration[renewal_count]" type="number" min="0" max="1000" value="{{ $accreditationValue('renewal_count') }}"></div>
            <div class="field"><label for="accreditation-remarks">Remarks (optional)</label><textarea id="accreditation-remarks" name="registration[remarks]" maxlength="4000" rows="2">{{ $accreditationValue('remarks') }}</textarea></div>
            </div>
        </fieldset>
        <p><a class="text-button" href="{{ route('admin.associations.index', ['editor' => $editingAssociation['id']]) }}">Cancel registration update</a></p>
    @endif
</section>
