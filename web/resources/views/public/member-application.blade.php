@extends('layouts.public')
@section('title', 'Membership application · AssocMap')
@section('content')
<section class="public-section"><div class="public-container public-policy public-application">
    <a href="{{ route('public.gis') }}">← Back to the map</a>
    <header class="public-section-heading"><p class="public-eyebrow">Public membership application</p><h1>Apply to {{ $site->application_association_name }}</h1><p>Selected location: {{ $site->public_title }}. Your application will be screened by the assigned Field Officer.</p></header>
    @if($errors->any())<div class="notice notice-error" role="alert"><strong>Please check your application.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Please select your photo again.</p></div>@endif
    <form method="POST" action="{{ route('public.application.store', $site->id) }}" enctype="multipart/form-data" class="card public-application-form">
        @csrf
        <h2>Applicant information</h2><p>Fields marked * are required.</p>
        <div class="form-grid">
            @foreach([['first_name','First name',true,'text',120], ['middle_name','Middle name',false,'text',120], ['last_name','Last name',true,'text',120], ['suffix','Suffix',false,'text',20], ['birth_date','Birth date',true,'date',null], ['contact_number','Mobile number (Philippines)',true,'tel',11], ['email','Email address',false,'email',255]] as [$name,$label,$required,$type,$max])
                <div class="field"><label for="public-{{ $name }}">{{ $label }} {{ $required ? '*' : '(optional)' }}</label><input id="public-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ is_scalar(old($name)) ? old($name) : '' }}" @required($required) @if($max) maxlength="{{ $max }}" @endif @if($name === 'birth_date') max="{{ now()->toDateString() }}" @endif @if($name === 'contact_number') data-ph-mobile inputmode="numeric" autocomplete="tel-national" pattern="09[0-9]{9}" placeholder="09123456789" aria-describedby="public-mobile-help" @endif>@if($name === 'contact_number')<small id="public-mobile-help">PH · 11 digits, starting with 09</small>@endif @error($name)<span class="field-error">{{ $message }}</span>@enderror</div>
            @endforeach
            <div class="field"><label for="public-sex">Sex *</label><select id="public-sex" name="sex_id" required><option value="">Select…</option>@foreach($sexCategories as $sex)<option value="{{ $sex->id }}" @selected((string) old('sex_id') === (string) $sex->id)>{{ $sex->name }}</option>@endforeach</select></div>
            <div class="field field-wide"><label for="public-reason">Why would you like to join this association, and how do you hope to participate? *</label><p>Tell us about your livelihood, your interest in the association’s activities, and how joining may help you. Please share only information relevant to your application.</p><textarea id="public-reason" name="reason_for_applying" rows="8" required maxlength="5000" aria-describedby="word-count">{{ is_string(old('reason_for_applying')) ? old('reason_for_applying') : '' }}</textarea><small id="word-count" data-word-count>Maximum 500 words and 5,000 characters.</small></div>
            <div class="field field-wide"><label for="public-photo">Applicant photo *</label><input type="file" id="public-photo" name="applicant_photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required><small>JPEG or PNG; maximum 5 MiB and 2048 × 2048 pixels. Reselect your photo after a validation error.</small></div>
        </div>
        {{-- The hidden field is left empty by visitors and checked on submission. --}}
        <div class="public-application-trap" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <label class="public-application-consent"><input type="checkbox" name="acknowledgement" value="1" required @checked(old('acknowledgement'))><span>I have read and agree to the <button type="button" class="public-policy-link" data-dialog="application-terms">Terms of Use</button> and acknowledge the <button type="button" class="public-policy-link" data-dialog="application-privacy">Privacy Notice</button>.</span></label>
        <button class="button button-primary" type="submit">Submit application</button>
    </form>
    {{-- Display the existing policy pages without navigating away from the form. --}}
    <dialog id="application-terms" class="modal public-policy-modal" aria-labelledby="application-terms-title"><h2 id="application-terms-title">Terms of Use</h2><iframe title="Terms of Use" src="{{ route('public.terms') }}"></iframe><button type="button" class="button" data-close-dialog>Close</button></dialog>
    <dialog id="application-privacy" class="modal public-policy-modal" aria-labelledby="application-privacy-title"><h2 id="application-privacy-title">Privacy Notice</h2><iframe title="Privacy Notice" src="{{ route('public.privacy') }}"></iframe><button type="button" class="button" data-close-dialog>Close</button></dialog>
</div></section>
@endsection
