<section class="form-section" aria-label="Private application evidence">
    <div class="field">
        {{-- Public applicants provide a reason; association submissions retain their endorsement. --}}
        @if($application->submission_source === 'public_gis')
            <h3>Selected location</h3><p>{{ $application->public_location_title ?: 'Location unavailable' }}</p>
            <h3>Reason for applying <span class="badge badge-neutral">Public applicant</span></h3>
            <p style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $application->reason_for_applying }}</p>
        @elseif(filled($application->endorsement_letter))
            <label for="endorsement-{{ $application->id }}">Endorsement Letter</label>
            <textarea id="endorsement-{{ $application->id }}" rows="8" readonly>{{ $application->endorsement_letter }}</textarea>
        @else
            <h3>Endorsement Letter</h3>
            <p>Not recorded</p>
        @endif
    </div>
    <div class="field">
        <h3>Applicant Photo</h3>
        @if(filled($application->applicant_photo_path))
            {{-- The photo link also works when the larger viewer is unavailable. --}}
            <div class="member-evidence-photo" data-member-evidence-photo>
                <p class="member-evidence-status" data-evidence-status role="status" aria-live="polite" hidden>Loading photo…</p>
                <a class="member-evidence-link" data-evidence-open href="{{ route($photoRoute, $application) }}" aria-label="Open applicant photo in a larger view">
                    <img src="{{ route($photoRoute, $application) }}" alt="Applicant photo" loading="lazy" decoding="async" width="256" height="256">
                </a>
                <dialog class="member-evidence-viewer" data-evidence-viewer aria-label="Applicant photo">
                    <button type="button" class="member-evidence-close" data-evidence-close aria-label="Close photo"><x-icon name="close" /></button>
                    <p class="member-evidence-status" data-evidence-viewer-status role="status" aria-live="polite">Loading photo…</p>
                    <img data-evidence-image alt="Applicant photo" hidden>
                </dialog>
            </div>
        @else
            <p>Not recorded</p>
        @endif
    </div>
</section>
