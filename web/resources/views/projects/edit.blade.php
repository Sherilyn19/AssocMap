@extends($prefix === 'admin' ? 'layouts.admin' : 'layouts.field-officer')
@section('content')
<section class="program-management project-edit-page" data-workspace-module="projects" data-workspace-title="Edit Project">
    {{-- Place the return link and path below the page heading. --}}
    <header class="card program-heading"><div><p class="eyebrow">Project Management</p><h1 tabindex="-1">Edit Project</h1><p>{{ $project->title }}</p></div></header>
    <x-workflow-navigation :back="['label' => 'Project Details', 'url' => route($prefix.'.projects.show', $project)]" :parents="[['label' => 'Projects', 'url' => route($prefix.'.projects.index')], ['label' => $project->title, 'url' => route($prefix.'.projects.show', $project)]]" current="Edit Project" />
    @include('projects.partials.cover', ['canManage' => true])
    {{-- Keep the existing project fields together in one form. --}}
    <section class="card program-information program-editor">
        @include('projects.partials.form', ['projectRoutePrefix' => $prefix.'.projects', 'editRow' => $editRow])
    </section>
</section>
@endsection
