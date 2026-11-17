{{-- Shared header for workspace navigation and signed-in account details. --}}
@props([
    'workspace',
    'context',
    'displayName',
    'accountLabel',
    'contextId' => null,
    'avatar' => null,
    'compact' => false,
    'scrollContext' => false,
    'associationName' => null,
    'associationLabel' => 'Association',
    'adminAccount' => null,
])

<header @class(['topbar', 'workspace-header', 'workspace-header--compact' => $compact, 'workspace-header--scroll-context' => $scrollContext])>
    {{-- Keep the current workspace and page easy to find. --}}
    <div class="topbar-context">
        {{ $navigationToggle }}
        @if($scrollContext)
            <div class="admin-header-trail">@if($workspace)<span class="admin-header-workspace">{{ $workspace }}</span>@endif<span class="admin-header-page" data-admin-header-page>@if($workspace)<x-icon name="chevron" />@endif<span class="workspace-header-context" @if($contextId) id="{{ $contextId }}" @endif>{{ $context }}</span></span></div>
        @else
            @unless($compact)<div class="context-product">{{ $workspace }}</div><x-icon name="chevron" />@endunless
            <span class="workspace-header-context" @if($contextId) id="{{ $contextId }}" @endif>{{ $context }}</span>
        @endif
    </div>
    {{-- Show the appropriate account details for each role. --}}
    <div class="topbar-account">
        <span class="account-divider" aria-hidden="true"></span>
        @if($avatar)
            <span class="avatar">{{ $avatar }}</span>
        @endif
        <div class="account-copy">
            @if($adminAccount)
                <details class="admin-account-details">
                    <summary><strong>{{ $displayName }}</strong><x-icon name="chevron-down" /></summary>
                    <div class="admin-account-popover">
                        <div class="admin-account-title"><x-icon name="shield" /><span><small>Account</small><strong>{{ $adminAccount->display_name }}</strong></span></div>
                        <dl>
                            <div><dt><x-icon name="user" /> User ID</dt><dd>{{ $adminAccount->id }}</dd></div>
                            <div><dt><x-icon name="calendar" /> Created</dt><dd>{{ $adminAccount->created_at?->copy()->timezone('Asia/Manila')->format('M d, Y · g:i A') ?? 'Not recorded' }}</dd></div>
                            <div><dt><x-icon name="calendar" /> Activated</dt><dd>{{ $adminAccount->activated_at?->copy()->timezone('Asia/Manila')->format('M d, Y · g:i A') ?? 'Not recorded' }}</dd></div>
                        </dl>
                        <small>Times shown in Philippine time</small>
                    </div>
                </details>
            @elseif($associationName)
                <details class="account-association"><summary><strong>{{ $displayName }}</strong><x-icon name="chevron-down" /></summary><div class="account-association-popover"><x-icon :name="$associationLabel === 'Field Officer' ? 'user' : 'building'" /><span><small>{{ $associationLabel }}</small><strong>{{ $associationName }}</strong></span></div></details>
            @else
                <strong>{{ $displayName }}</strong>
            @endif
            @if(!$compact && $accountLabel)<small>{{ $accountLabel }}</small>@endif
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-button"><x-icon name="log-out" />Logout</button>
        </form>
    </div>
</header>
