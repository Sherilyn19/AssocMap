<x-workspace-header
    workspace="Admin workspace"
    context="Dashboard"
    context-id="current-context"
    :display-name="request()->attributes->get('current_user_email') ?: 'Email unavailable'"
    :admin-account="$currentUser"
    :account-label="$currentUser->role->name"
    :compact="true"
    :scroll-context="true"
    avatar="SA"
>
    <x-slot:navigationToggle>
        <button class="icon-button mobile-menu" type="button" data-open-sidebar aria-label="Open navigation" aria-controls="admin-sidebar" aria-expanded="false"><x-icon name="menu" /></button>
    </x-slot:navigationToggle>
</x-workspace-header>
