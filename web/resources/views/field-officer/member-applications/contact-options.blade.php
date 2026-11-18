{{-- Contact actions open the officer's configured phone or email app. --}}
<section class="member-review-contact" aria-label="Applicant contact information">
    <h3>Contact the applicant</h3>
    <p>Use the available contact details to discuss the application and its next steps.</p>
    <div class="member-review-contact-actions">
        @if(filled($application->contact_number))
            @php($phoneLink = preg_replace('/[^0-9+]/', '', $application->contact_number))
            <a class="button" href="tel:{{ $phoneLink }}">Call {{ $application->contact_number }}</a>
            <a class="button" href="sms:{{ $phoneLink }}">Send a text</a>
        @endif
        @if(filled($application->email))<a class="button" href="mailto:{{ rawurlencode($application->email) }}">Email {{ $application->email }}</a>@endif
        @if(blank($application->contact_number) && blank($application->email))<p>No contact information was recorded.</p>@endif
    </div>
</section>
