@extends('layouts.public')
@section('title', 'Application received · AssocMap')
@section('content')
<section class="public-section"><div class="public-container public-policy public-application-received">
    <div class="card public-application-form"><h1>Application submitted!</h1><p>Thank you for your interest. Your application has been received and is pending review.</p><button class="button button-primary" type="button" data-dialog="application-next">What happens next?</button> <a class="button" href="{{ route('public.gis') }}">Return to the map</a></div>
    {{-- Keep a readable confirmation on the page as well as the two dialogs. --}}
    <dialog id="application-success" class="modal" aria-labelledby="application-success-title" data-application-success><h2 id="application-success-title">Application submitted!</h2><p>Thank you for your interest. Your application has been received and is pending review.</p><div class="modal-actions"><button type="button" class="button button-primary" data-application-next>Continue</button></div></dialog>
    <dialog id="application-next" class="modal" aria-labelledby="application-next-title"><h2 id="application-next-title">What happens next?</h2><p>The assigned Field Officer will review your application and may contact you using the details you provided. Please keep your phone available for updates.</p><p>Thank you—we look forward to connecting with you!</p><div class="modal-actions"><button type="button" class="button button-primary" data-close-dialog>Done</button></div></dialog>
</div></section>
@endsection
