@props([
    'workspace',
    'context',
    'displayName',
    'accountLabel',
    'contextId' => null,
    'avatar' => null,
    'compact' => false,
])

<header @class(['topbar', 'workspace-header', 'workspace-header--compact' => $compact])>
    <div class="topbar-context">
        {{ $navigationToggle }}
        @unless($compact)<div class="context-product">{{ $workspace }}</div><x-icon name="chevron" />@endunless
        <span class="workspace-header-context" @if($contextId) id="{{ $contextId }}" @endif>{{ $context }}</span>
    </div>
    <div class="topbar-account">
        <span class="account-divider" aria-hidden="true"></span>
        @if($avatar)
            <span class="avatar">{{ $avatar }}</span>
        @endif
        <div class="account-copy">
            <strong>{{ $displayName }}</strong>
            @unless($compact)<small>{{ $accountLabel }}</small>@endunless
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-button"><x-icon name="log-out" />Logout</button>
        </form>
    </div>
</header>
