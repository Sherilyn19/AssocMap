@extends($layout)

@section('content')
    <div @class(['role-dashboard', 'admin-dashboard' => $role === 'system_admin']) data-workspace-module="dashboard" data-workspace-title="Dashboard">
        @include('dashboard.content')
    </div>
@endsection
