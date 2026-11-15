@extends('auth.invitation-layout')

@section('title', 'Resume account activation · AssocMap')

@section('content')
    <div class="invitation-restart-panel">
        <h1 id="activation-title">Resume account activation</h1>

        <p class="invitation-lead">
            Use this when you verified an invitation but lost the activation page before submitting a password.
        </p>

        @if (!config('services.supabase.invitation_restart_enabled'))
            <div class="invitation-alert" role="alert" aria-live="polite">
                <svg class="invitation-alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm8.624 1.458-7.5-13.5a1.25 1.25 0 0 0-2.248 0l-7.5 13.5A1.25 1.25 0 0 0 4.5 20.25h15a1.25 1.25 0 0 0 1.124-1.792Z" />
                </svg>

                <div class="invitation-alert-copy">
                    <strong>Activation recovery is not currently available.</strong>
                    <span>Contact an Administrator for assistance.</span>
                </div>
            </div>
        @else
            @if ($restartThrottled ?? false)
                <div class="invitation-alert invitation-alert-error" role="alert">
                    <svg class="invitation-alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm8.624 1.458-7.5-13.5a1.25 1.25 0 0 0-2.248 0l-7.5 13.5A1.25 1.25 0 0 0 4.5 20.25h15a1.25 1.25 0 0 0 1.124-1.792Z" />
                    </svg>

                    <div class="invitation-alert-copy">
                        <strong>Too many recovery requests.</strong>
                        <span>Wait before requesting another link.</span>
                    </div>
                </div>
            @endif

            @if (session('invitation_restart_requested'))
                <div class="invitation-alert invitation-alert-success" role="status">
                    <svg class="invitation-alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>

                    <div class="invitation-alert-copy">
                        <strong>Recovery request submitted.</strong>
                        <span>If this invitation is eligible, instructions will be sent to its registered email.</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="invitation-alert invitation-alert-error" role="alert">
                    <svg class="invitation-alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm8.624 1.458-7.5-13.5a1.25 1.25 0 0 0-2.248 0l-7.5 13.5A1.25 1.25 0 0 0 4.5 20.25h15a1.25 1.25 0 0 0 1.124-1.792Z" />
                    </svg>

                    <div class="invitation-alert-copy">
                        @foreach ($errors->all() as $error)
                            <strong>{{ $error }}</strong>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('invitation.restart.store') }}" class="invitation-restart-form">
                @csrf

                <div class="staff-login-field invitation-field">
                    <label for="restart-email">Original invitation email</label>

                    <div class="staff-input-wrap invitation-input-wrap">
                        <svg class="staff-field-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5H4.5a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.918l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615A2.25 2.25 0 0 1 2.25 6.993V6.75" />
                        </svg>

                        <input
                            id="restart-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            autocapitalize="none"
                            spellcheck="false"
                            maxlength="255"
                            placeholder="name@organization.com"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="public-button public-button-primary invitation-submit">
                    <span>Request activation recovery</span>
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </button>
            </form>
        @endif

        <p class="invitation-help">
            If you already submitted a password and its outcome is uncertain, contact an Administrator before trying again. This process does not restore revoked access or change your registered email.
        </p>

        <div class="invitation-divider"></div>

        <a href="{{ route('login') }}" class="invitation-signin-link">
            <span>Go to sign in</span>
        </a>
    </div>
@endsection