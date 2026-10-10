@extends('rems.layouts.app')
@section('title',($editing ? 'Edit ' : 'Add ').$definition['singular'])
@section('content')
<div class="page-heading d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3"><div><div class="page-eyebrow"><a href="{{ route('rems.resources.index',$resource) }}">{{ strtoupper($definition['title']) }}</a> / {{ $editing ? 'EDIT' : 'NEW RECORD' }}</div><h1>{{ $editing ? 'Edit ' : ($resource === 'payrolls' ? 'Generate ' : 'Add ') }}{{ $definition['singular'] }}</h1><p>{{ $editing ? 'Update the information below.' : 'Enter the details to create a new record.' }}</p></div><a href="{{ route('rems.resources.index',$resource) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-2"></i>Back to list</a></div>
<div class="row g-4"><div class="col-xl-9"><div class="content-card form-card"><div class="card-head"><div><div class="card-eyebrow">{{ $editing ? 'UPDATE DETAILS' : 'RECORD DETAILS' }}</div><h3>{{ $definition['singular'] }} information</h3></div><span class="subtle-icon"><i class="bi bi-ui-checks-grid"></i></span></div>
<form action="{{ $editing ? route('rems.resources.update',[$resource,$record->id]) : route('rems.resources.store',$resource) }}" method="post" enctype="multipart/form-data" class="form-main">
    @csrf @if($editing)@method('PUT')@endif
    <div class="row g-4">
        @foreach($definition['fields'] as $name => $field)
            @if(!empty($field['hr_only']) && !\App\Support\Access::isHr(auth()->user())) @continue @endif
            @if($editing && !empty($field['readonly_on_edit'])) @continue @endif
            @php
                $type = $field['type'] ?? 'text';
                $currentValue = old($name, $record ? data_get($record,$name) : null);
                if ($currentValue instanceof \Carbon\CarbonInterface) $currentValue = $currentValue->format($type === 'datetime-local' ? 'Y-m-d\\TH:i' : 'Y-m-d');
                if ($type === 'time' && is_string($currentValue)) $currentValue = substr($currentValue,0,5);
                $col = in_array($type,['textarea','file','weekdays'],true) ? 'col-12' : 'col-md-6';
            @endphp
            <div class="{{ $col }}">
                @if($type === 'checkbox')
                    <div class="form-check form-switch mt-4">
                        <input type="hidden" name="{{ $name }}" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="{{ $name }}" name="{{ $name }}" value="1" @checked((bool) old($name,$record ? data_get($record,$name) : in_array($name,['is_active','is_paid'],true)))>
                        <label for="{{ $name }}" class="form-check-label fw-semibold">{{ $field['label'] }}</label>
                    </div>
                @else
                    <label for="{{ $name }}" class="form-label fw-semibold">{{ $field['label'] }}@if(!empty($field['required'])) <span class="text-danger">*</span>@endif</label>
                    @if($type === 'textarea')
                        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="form-control @error($name) is-invalid @enderror" placeholder="Enter {{ strtolower($field['label']) }}" @if(!empty($field['required'])) required @endif>{{ $currentValue }}</textarea>
                    @elseif($type === 'select' || $type === 'lookup')
                        <select class="form-select @error($name) is-invalid @enderror" name="{{ $name }}" id="{{ $name }}" @if(!empty($field['required'])) required @endif>
                            <option value="">Select {{ strtolower($field['label']) }}</option>
                            @foreach($type === 'lookup' ? ($lookups[$name] ?? []) : ($field['options'] ?? []) as $key => $label)
                                <option value="{{ $key }}" @selected((string)$currentValue === (string)$key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'weekdays')
                        <div class="weekdays-box d-flex flex-wrap gap-3" id="{{ $name }}">
                            @php($chosenDays = old('working_days', $record?->working_days ?? [1,2,3,4,5]))
                            @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $day=>$label)
                                <label class="weekday-item"><input type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, (array)$chosenDays))> {{ $label }}</label>
                            @endforeach
                        </div>
                    @elseif($type === 'file')
                        <input class="form-control @error($name) is-invalid @enderror" type="file" name="{{ $name }}" id="{{ $name }}" accept="{{ $field['accept'] ?? '' }}" @if(!empty($field['required'])) required @endif>
                    @else
                        <input class="form-control @error($name) is-invalid @enderror" type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $currentValue }}" placeholder="{{ $field['placeholder'] ?? 'Enter '.strtolower($field['label']) }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['max'])) max="{{ $field['max'] }}" @endif @if(!empty($field['required'])) required @endif>
                    @endif
                    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif
            </div>
        @endforeach
    </div>
    <div class="form-actions"><a href="{{ route('rems.resources.index',$resource) }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary px-4" type="submit"><i class="bi bi-check2-circle me-2"></i>{{ $editing ? 'Save changes' : ($resource === 'payrolls' ? 'Generate draft' : 'Save record') }}</button></div>
</form></div></div>
<div class="col-xl-3"><div class="info-card"><i class="bi bi-shield-check"></i><h4>Secure records</h4><p>All submissions use Laravel CSRF protection, validated inputs and your existing role-based business rules.</p><div class="mini-divider"></div><span><i class="bi bi-lock me-2"></i>Permission protected</span><span><i class="bi bi-clock-history me-2"></i>Audit-sensitive actions</span></div></div></div>
@endsection
