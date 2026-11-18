@extends('layouts.public')
@section('title', 'Membership Application Guide · AssocMap')
@section('content')
<section class="public-section"><div class="public-container public-policy">
    <header class="public-section-heading"><p class="public-eyebrow">Membership applications</p><h1>Membership Application Guide</h1><p>Learn how to submit an application for Field Officer screening.</p></header>
    {{-- Explain screening without promising membership or an intervention. --}}
    <div class="public-policy-content">
        <section><h2>1. Explore and select a location</h2><p>Open the public map and select a published location from a marker or the location list. The Apply for Membership button becomes available after a location is selected.</p></section>
        <section><h2>2. Complete your application</h2><p>Check the association shown, enter your details and mobile number, explain why you would like to join, and upload your photo. Email is optional. Please provide accurate information.</p></section>
        <section><h2>3. Review and submit</h2><p>Read the Terms of Use and Privacy Notice and acknowledge them before submitting. Please select your photo again if the form reports a validation error.</p></section>
        <section><h2>4. Wait for screening</h2><p>The assigned Field Officer will review your application and may contact you using the details you provide. Submission does not guarantee approval, membership, or an intervention.</p></section>
        <section><h2>Need assistance?</h2><p>If you may have applied already or need to correct your information, contact the project team at <a href="mailto:assocmap.info@gmail.com">assocmap.info@gmail.com</a>.</p></section>
    </div>
    <p><a class="button button-primary" href="{{ route('public.gis') }}">Back to the map</a></p>
</div></section>
@endsection
