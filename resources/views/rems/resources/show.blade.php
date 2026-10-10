@extends('rems.layouts.app')
@section('title',$definition['singular'].' details')
@section('content')
<div class="page-heading d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"><div><div class="page-eyebrow"><a href="{{ route('rems.resources.index',$resource) }}">{{ strtoupper($definition['title']) }}</a> / RECORD #{{ $record->id }}</div><h1>{{ $definition['singular'] }} details</h1><p>Record information and available operations.</p></div><div class="d-flex gap-2"><a href="{{ route('rems.resources.index',$resource) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Back</a>@if(isset($definition['update_method'],$definition['edit']) && auth()->user()->can($definition['edit']))<a href="{{ route('rems.resources.edit',[$resource,$record->id]) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>@endif</div></div>
@if($resource === 'employees') @include('rems.partials.employee-profile') @endif
@if($resource === 'payrolls') @include('rems.partials.payroll-summary') @endif
<div class="row g-4"><div class="col-xl-8"><div class="content-card"><div class="card-head"><div><div class="card-eyebrow">OVERVIEW</div><h3>Record information</h3></div><span class="subtle-icon"><i class="bi bi-file-earmark-text"></i></span></div><div class="detail-grid">
    @foreach($definition['columns'] as $key=>$label)
        @php($value=data_get($record,$key))
        <div class="detail-item"><span>{{ $label }}</span><strong>
            @if(in_array($key,['status','approval_status','priority','is_active','is_paid','is_primary'])) @include('rems.partials.status',['value'=>$value])
            @elseif(in_array($key,['basic_salary','house_allowance','transport_allowance','gross_salary','net_salary','amount'])) ৳{{ number_format((float)$value,2) }}
            @elseif($value instanceof \Carbon\CarbonInterface) {{ $value->format(in_array($key,['check_in','check_out'],true) ? 'd M Y, h:i A' : 'd M Y') }}
            @else {{ $value ?? '—' }} @endif
        </strong></div>
    @endforeach
    @foreach($definition['fields'] as $key=>$field)
        @if(array_key_exists($key, $definition['columns']) || in_array($key,['file','photo'],true)) @continue @endif
        @php($value=data_get($record,$key))
        @if($value !== null && $value !== '')<div class="detail-item"><span>{{ $field['label'] }}</span><strong>{{ $value instanceof \Carbon\CarbonInterface ? $value->format('d M Y') : (is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : $value)) }}</strong></div>@endif
    @endforeach
    </div></div>
    @if($resource === 'tasks' && $record->description)<div class="content-card mt-4 p-4"><div class="card-eyebrow mb-2">TASK DESCRIPTION</div><div class="detail-prose">{{ $record->description }}</div>@if($record->task_report)<hr><div class="card-eyebrow mb-2">SUBMITTED REPORT</div><div class="detail-prose">{{ $record->task_report }}</div>@endif @if($record->review_feedback)<hr><div class="card-eyebrow mb-2">REVIEW FEEDBACK</div><div class="detail-prose">{{ $record->review_feedback }}</div>@endif</div>@endif
</div>
<div class="col-xl-4"><div class="content-card action-card"><div class="card-head"><div><div class="card-eyebrow">WORKFLOW</div><h3>Available actions</h3></div><span class="subtle-icon"><i class="bi bi-lightning"></i></span></div><div class="action-content">
@if($resource === 'tasks')
    @if($record->assignee?->user_id === auth()->id() && in_array($record->status,['pending','in_progress','revision_required']))
        <h6>Submit task progress</h6><form method="post" action="{{ route('rems.workflow',['task-submit',$record->id]) }}">@csrf<label class="form-label" for="task_report">Work report</label><textarea class="form-control mb-2" id="task_report" name="task_report" rows="4" required>{{ old('task_report',$record->task_report) }}</textarea><select name="status" class="form-select mb-2"><option value="in_progress">In progress</option><option value="submitted">Submit for review</option></select><button class="btn btn-primary w-100">Update task</button></form>
    @endif
    @can('tasks.review')@if($record->status === 'submitted')<h6>Review submission</h6><form method="post" action="{{ route('rems.workflow',['task-review',$record->id]) }}">@csrf<select class="form-select mb-2" name="status" required><option value="approved">Approve</option><option value="revision_required">Request revision</option></select><textarea class="form-control mb-2" name="review_feedback" rows="3" placeholder="Reviewer feedback"></textarea><button class="btn btn-primary w-100">Submit review</button></form>@endif @endcan
@elseif($resource === 'leave-requests')
    @can('leaves.review')@if($record->status === 'pending')<h6>Leave decision</h6><form method="post" action="{{ route('rems.workflow',['leave-review',$record->id]) }}">@csrf<select class="form-select mb-2" name="status" required><option value="approved">Approve leave</option><option value="rejected">Reject leave</option></select><button class="btn btn-primary w-100">Confirm decision</button></form>@endif @endcan
    @if($record->status === 'pending' && (\App\Support\Access::isHr(auth()->user()) || $record->employee?->user_id === auth()->id()))<form method="post" action="{{ route('rems.workflow',['leave-cancel',$record->id]) }}" class="mt-2" data-confirm="Cancel this leave request?"><button class="btn btn-outline-danger w-100">Cancel request</button>@csrf</form>@endif
@elseif($resource === 'attendances')
    @can('attendances.review')@if($record->approval_status === 'pending')<h6>Attendance review</h6><form method="post" action="{{ route('rems.workflow',['attendance-review',$record->id]) }}">@csrf<select class="form-select mb-2" name="approval_status" required><option value="approved">Approve</option><option value="rejected">Reject</option></select><label class="form-label" for="approved_overtime_minutes">Approved overtime (min)</label><input class="form-control mb-2" type="number" name="approved_overtime_minutes" id="approved_overtime_minutes" value="0" min="0" max="1440"><button class="btn btn-primary w-100">Submit review</button></form>@endif @endcan
@elseif($resource === 'payrolls')
    @can('payrolls.approve')@if($record->status === 'draft')<p class="small text-muted">Review employee, pay period and amount before approving.</p><form method="post" action="{{ route('rems.workflow',['payroll-approve',$record->id]) }}" data-confirm="Approve this payroll? This action locks its draft figures.">@csrf<button class="btn btn-primary w-100">Approve payroll</button></form>@endif @endcan
@elseif($resource === 'employee-documents')
    <a class="btn btn-primary w-100" href="{{ route('rems.documents.download',$record->id) }}"><i class="bi bi-download me-1"></i>Download secure file</a>
@elseif($resource === 'employees')
    @can('employees.manage')@if(!$record->user_id && $record->status === 'active')<h6>Create employee login</h6><form method="post" action="{{ route('rems.workflow',['employee-account',$record->id]) }}">@csrf<input class="form-control mb-2" name="email" type="email" placeholder="Login email" value="{{ old('email',$record->email) }}" required><input class="form-control mb-2" name="password" type="password" placeholder="New password (12+ characters)" minlength="12" autocomplete="new-password" required><input class="form-control mb-2" name="password_confirmation" type="password" placeholder="Confirm password" minlength="12" autocomplete="new-password" required><button class="btn btn-primary w-100">Create login</button></form>@endif @endcan
@endif
@if(isset($definition['delete_method'],$definition['edit']) && auth()->user()->can($definition['edit']))
    <form method="post" action="{{ route('rems.resources.destroy',[$resource,$record->id]) }}" class="mt-3" data-confirm="Are you sure? This will archive or update the record.">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-archive me-1"></i>Archive / deactivate</button></form>
@endif
@if(!in_array($resource,['tasks','leave-requests','attendances','payrolls','employee-documents','employees'],true) && !isset($definition['delete_method']))<p class="text-muted small mb-0">This record is retained for operational history. Use the edit option where available.</p>@endif
</div></div></div></div>
@endsection
