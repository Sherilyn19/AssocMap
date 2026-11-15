
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    @include('partials.favicon')

    <title>Welcome back · AssocMap</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="public-page staff-login-page">
    <main id="main-content" tabindex="-1" class="staff-login-main" aria-labelledby="staff-login-title">
        <div class="staff-login-wrap">

            <div class="staff-login-brand">
                <a href="{{ route('home') }}" class="public-brand public-brand-official assocmap-brand-link" aria-label="AssocMap home">
                    <span class="staff-login-emblem" aria-hidden="true">
                        <x-assocmap-logo />
                    </span>
                    <x-assocmap-wordmark />
                </a>
            </div>

            <section class="staff-login-card" aria-labelledby="staff-login-title">
                <h1 id="staff-login-title">Welcome back</h1>

                <p class="staff-login-intro">
                    Sign in to access your authorized AssocMap workspace.
                </p>

                <form method="POST" action="{{ route('login.store') }}" aria-describedby="staff-login-note">
                    @csrf

                    <!-- Work Email -->
                    <div class="staff-login-field">
                        <label for="staff-email">WORK EMAIL</label>

                        <div class="staff-input-wrap">
                            <svg class="staff-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="m3 7 9 6 9-6"/>
                            </svg>

                            <input
                                id="staff-email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                placeholder="name@organization.com"
                                @if($errors->has('email')) aria-invalid="true" aria-describedby="staff-email-error" @endif
                                required
                            >
                        </div>

                        @error('email')
                            <div id="staff-email-error" class="notice notice-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="staff-login-field">
                        <label for="staff-password">PASSWORD</label>

                        <div class="staff-input-wrap staff-password-control">
                            <svg class="staff-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="5" y="10" width="14" height="11" rx="2"/>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                            <input
                                id="staff-password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Enter password"
                                @if($errors->has('password')) aria-invalid="true" aria-describedby="staff-password-error" @endif
                                required
                            >

                            <button
                                id="staff-password-toggle"
                                type="button"
                                class="staff-password-toggle"
                                aria-controls="staff-password"
                                aria-label="Show password"
                                hidden
                            >
                                <x-icon name="eye" />
                                <span>Show</span>
                            </button>
                        </div>

                        @error('password')
                            <div id="staff-password-error" class="notice notice-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember and Recovery -->
                    <div class="staff-login-options">
                        <label class="staff-remember" for="staff-remember">
                            <input id="staff-remember" name="remember" type="checkbox">
                            <span>Keep me signed in</span>
                        </label>

                        <a href="{{ route('password.forgot') }}">Forgot password?</a>
                    </div>

                    <!-- Sign In -->
                    <button
                        type="submit"
                        class="public-button public-button-primary staff-sign-in"
                        aria-describedby="staff-login-note"
                    >
                        <span>Sign In to Workspace</span>
                        <x-icon name="arrow" />
                    </button>

                <div class="staff-login-support">
                    <div id="staff-login-note" class="staff-login-status-badge" role="note">
                        <svg class="staff-login-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                        </svg>
                        <span>Authorized AssocMap accounts only</span>
                    </div>
                </div>
                </form>

                <div class="staff-login-activation">
                    <p>Haven't activated your account?</p>

                    <a href="{{ route('invitation.restart') }}" class="staff-login-activation-link">
                        <span>Resume account activation</span>
                        <x-icon name="chevron" />
                    </a>
                </div>

                <a href="{{ route('home') }}" class="staff-login-back">
                    <x-icon name="back" />
                    Back to AssocMap
                </a>
            </section>

            <p class="staff-login-access">
                <x-icon name="shield" />
                Authorized Account access only.
            </p>

        </div>
    </main>
</body>
</html>
