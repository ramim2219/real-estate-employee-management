@extends('rems.layouts.app')
@section('title','Dashboard')
@section('content')
<div class="page-heading d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div><div class="page-eyebrow">OVERVIEW / WORKSPACE</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ',auth()->user()->name)[0] }} <span class="heading-wave">👋</span></h1><p>Here’s what’s happening across your employee operations.</p></div>
    <span class="overview-date"><i class="bi bi-calendar-week me-2"></i>{{ now()->format('l, F j, Y') }}</span>
</div>
<div class="hero-banner mb-4"><div class="hero-copy"><span class="hero-eyebrow"><i class="bi bi-stars me-2"></i>YOUR OPERATIONS AT A GLANCE</span><h2>A clearer view of your entire workforce.</h2><p>Track your people, property sites and work priorities from a single secure dashboard.</p></div><div class="hero-symbol" aria-hidden="true"><i class="bi bi-buildings-fill"></i></div></div>
<div class="row g-3 mb-4">
    @forelse($metrics as $key => $value)
        @php
            $metricIcons = ['active_employees'=>['people-fill','blue'],'ongoing_projects'=>['buildings-fill','purple'],'pending_leave_requests'=>['calendar-heart-fill','orange'],'today_pending_attendance'=>['clock-history','teal'],'draft_payrolls'=>['wallet2','blue'],'approved_unpaid_payrolls'=>['cash-stack','orange'],'managed_projects'=>['buildings-fill','purple'],'pending_project_tasks'=>['list-task','orange'],'my_pending_tasks'=>['list-check','blue'],'my_pending_leaves'=>['calendar3','orange'],'this_month_attendance'=>['calendar-check-fill','teal']];
            [$icon,$tone] = $metricIcons[$key] ?? ['bar-chart-fill','blue'];
        @endphp
        @include('rems.partials.metric',['icon'=>$icon,'tone'=>$tone,'value'=>$value,'label'=>ucwords(str_replace('_',' ',$key))])
    @empty
        <div class="col"><div class="content-card p-4">No dashboard metrics are available for your role.</div></div>
    @endforelse
</div>
<div class="row g-4">
    <div class="col-xl-8"><div class="content-card h-100"><div class="card-head"><div><div class="card-eyebrow">TRACK WORK</div><h3>Recent tasks</h3></div>@can('tasks.view')<a href="{{ route('rems.resources.index','tasks') }}" class="link-action">All tasks <i class="bi bi-arrow-up-right"></i></a>@endcan</div><div class="table-responsive"><table class="table rems-table mb-0"><thead><tr><th>Task</th><th>Priority</th><th>Status</th><th class="text-end">Details</th></tr></thead><tbody>
    @forelse(array_slice($tasks,0,6) as $task)<tr><td class="fw-semibold">{{ \Illuminate\Support\Str::limit($task->title,42) }}<div class="table-subtitle">{{ $task->project?->name ?? 'General assignment' }}</div></td><td>@include('rems.partials.status',['value'=>$task->priority])</td><td>@include('rems.partials.status',['value'=>$task->status])</td><td class="text-end"><a class="table-icon-link" href="{{ route('rems.resources.show',['tasks',$task->id]) }}" aria-label="View task"><i class="bi bi-arrow-up-right"></i></a></td></tr>
    @empty<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No tasks found</td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-xl-4"><div class="content-card h-100"><div class="card-head"><div><div class="card-eyebrow">SHORTCUTS</div><h3>Quick access</h3></div><span class="subtle-icon"><i class="bi bi-lightning-charge-fill"></i></span></div><div class="quick-actions">
    @foreach([['employees','people-fill','Manage people'],['projects','buildings-fill','Construction sites'],['attendances','calendar-check','Attendance records'],['leave-requests','calendar2-heart','Leave requests'],['payrolls','wallet2','Monthly payroll']] as [$slug,$icon,$title])
        @can(config("rems_ui.resources.$slug.view"))<a class="quick-row" href="{{ route('rems.resources.index',$slug) }}"><span class="quick-icon"><i class="bi bi-{{ $icon }}"></i></span><span class="flex-grow-1">{{ $title }}</span><i class="bi bi-chevron-right"></i></a>@endcan
    @endforeach
    </div>
    @if(auth()->user()->employee)
    <div class="p-3 border-top"><div class="small text-muted mb-2">Your attendance</div><div class="d-flex gap-2">
        <form method="post" action="{{ route('rems.workflow','check-in') }}">@csrf<button class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Check in</button></form>
        <form method="post" action="{{ route('rems.workflow','check-out') }}">@csrf<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-left me-1"></i>Check out</button></form>
    </div></div>
    @endif
    </div></div>
</div>
@if(count($projects))<div class="content-card mt-4"><div class="card-head"><div><div class="card-eyebrow">FIELD OPERATIONS</div><h3>Projects overview</h3></div><a class="link-action" href="{{ route('rems.resources.index','projects') }}">View projects <i class="bi bi-arrow-up-right"></i></a></div><div class="row g-3 p-3 pt-0">@foreach(array_slice($projects,0,3) as $project)<div class="col-md-4"><a class="project-mini-card" href="{{ route('rems.resources.show',['projects',$project->id]) }}"><span class="project-mini-icon"><i class="bi bi-buildings"></i></span><strong>{{ $project->name }}</strong><small><i class="bi bi-geo-alt me-1"></i>{{ $project->location }}</small>@include('rems.partials.status',['value'=>$project->status])</a></div>@endforeach</div></div>@endif
@endsection
