@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.field-officer')
@section('content')
<section class="program-management training-edit-page" data-workspace-module="trainings" data-workspace-title="Edit Training">
    {{-- Keep the page title above the return link and path. --}}
    <header class="card program-heading"><div><p class="eyebrow">Training Management</p><h1 tabindex="-1">Edit Training</h1><p>{{ $training->title }}</p></div></header>
    <x-workflow-navigation :back="['label' => 'Training Details', 'url' => route($prefix.'.trainings.show', $row['id'])]" :parents="[['label' => 'Trainings', 'url' => route($prefix.'.trainings.index')], ['label' => $training->title, 'url' => route($prefix.'.trainings.show', $row['id'])]]" current="Edit Training" />
    {{-- The form keeps its existing fields and actions in one clear space. --}}
    <section class="card program-information program-editor">
        @include('trainings.partials.form', ['trainingPrefix' => $prefix.'.trainings', 'editRow' => $row])
    </section>
</section>
@endsection
