@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.field-officer')
@section('content')
<section class="program-management activity-management" data-workspace-module="program-activities" data-workspace-title="Edit Program Activity">
    @if($prefix === 'admin')<a class="back-link" href="{{ route($prefix.'.program-activities.show', $row['id']) }}"><x-icon name="back" />Back to Details</a>@endif
    <header class="card program-heading"><div><p class="eyebrow">Program Operations</p><h1 tabindex="-1">Edit Program Activity</h1><p>{{ $row['title'] }}</p></div></header>
    {{-- Place the Field Officer's return link and path below the heading. --}}
    @if($prefix === 'field-officer')<x-workflow-navigation :back="['label' => 'Activity Details', 'url' => route($prefix.'.program-activities.show', $row['id'])]" :parents="[['label' => 'Program Activities', 'url' => route($prefix.'.program-activities.index')], ['label' => $row['title'], 'url' => route($prefix.'.program-activities.show', $row['id'])]]" current="Edit Program Activity" />@endif
    <div class="activity-overview-grid">
    <section class="card program-information program-editor" aria-label="Program activity editor">
        @include('program-activities.partials.form', ['activityPrefix' => $prefix.'.program-activities', 'editRow' => $row])
    </section>
    <section class="card activity-gallery-card" data-activity-gallery data-activity-id="{{ $row['id'] }}" data-base="{{ url($prefix.'/program-activities') }}" data-manager="true" data-csrf="{{ csrf_token() }}">
        <p class="notice notice-error" data-activity-gallery-error hidden>Photo gallery is temporarily unavailable.</p>
        @include('components.gis-photos', ['gisManager' => true, 'gisPublic' => false, 'gisAdministrator' => false, 'activityGallery' => true])
    </section>
    </div>
</section>
@endsection
