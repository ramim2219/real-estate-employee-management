@php
    $fullName = trim($record->first_name.' '.$record->last_name);
@endphp
<div class="content-card employee-profile-card mb-4">
    <div class="profile-cover"><div class="profile-cover-accent"></div></div>
    <div class="profile-main">
        <div class="profile-photo-wrap">
            @if($record->photo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($record->photo_path) }}" alt="Photo of {{ $fullName }}" class="profile-photo">
            @else
                <span class="profile-photo profile-fallback">{{ strtoupper(mb_substr($record->first_name,0,1)) }}</span>
            @endif
        </div>
        <div class="profile-meta flex-grow-1"><span class="profile-kicker">EMPLOYEE PROFILE</span><h2>{{ $fullName }}</h2><p>{{ $record->designation?->name ?? 'No designation' }} <span class="mx-2">·</span> {{ $record->department?->name ?? 'No department' }}</p><div class="d-flex align-items-center gap-2 flex-wrap">@include('rems.partials.status',['value'=>$record->status])<span class="profile-code"><i class="bi bi-upc-scan me-1"></i>{{ $record->employee_code }}</span></div></div>
    </div>
    <div class="profile-stats row g-3"><div class="col-sm-4"><span>Joined company</span><strong>{{ $record->joining_date?->format('d M Y') ?? '—' }}</strong></div><div class="col-sm-4"><span>Employment type</span><strong>{{ ucwords(str_replace('_',' ',$record->employment_type ?? '—')) }}</strong></div><div class="col-sm-4"><span>Assigned projects</span><strong>{{ $record->projectAssignments->count() }}</strong></div></div>
</div>
@if($record->projectAssignments->count())
<div class="content-card mb-4"><div class="card-head"><div><div class="card-eyebrow">FIELD ALLOCATIONS</div><h3>Assigned project sites</h3></div></div><div class="px-4 pb-3">@foreach($record->projectAssignments->take(8) as $assignment)<div class="project-assignment-row"><div class="assigned-symbol"><i class="bi bi-buildings"></i></div><div><strong>{{ $assignment->project?->name ?? 'Project #'.$assignment->project_id }}</strong><small>{{ $assignment->assignment_role ?: 'Site staff' }} · {{ $assignment->start_date?->format('d M Y') }}</small></div><div class="ms-auto">@if($assignment->end_date)<span class="text-muted small">Until {{ $assignment->end_date->format('d M Y') }}</span>@else<span class="status-pill status-success"><span class="status-dot"></span>Assigned</span>@endif</div></div>@endforeach</div></div>
@endif
