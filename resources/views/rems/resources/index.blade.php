@extends('rems.layouts.app')
@section('title',$definition['title'])
@section('content')
<div class="page-heading d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
    <div><div class="page-eyebrow">WORKSPACE / {{ strtoupper($definition['title']) }}</div><h1>{{ $definition['title'] }}</h1><p>View and manage {{ strtolower($definition['title']) }} records.</p></div>
    @if(isset($definition['store_method'], $definition['create']) && auth()->user()->can($definition['create']))
        <a href="{{ route('rems.resources.create',$resource) }}" class="btn btn-primary btn-add"><i class="bi bi-plus-lg me-2"></i>{{ $resource === 'payrolls' ? 'Generate payroll' : 'Add '.$definition['singular'] }}</a>
    @endif
</div>
@if($resource === 'attendances' && auth()->user()->employee)
<div class="attendance-strip mb-3"><div><i class="bi bi-clock-fill me-2"></i><strong>Today’s attendance</strong><span>Record your check-in or check-out</span></div><div class="d-flex gap-2"><form action="{{ route('rems.workflow','check-in') }}" method="post">@csrf<button class="btn btn-light btn-sm" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Check in</button></form><form action="{{ route('rems.workflow','check-out') }}" method="post">@csrf<button type="submit" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-left me-1"></i>Check out</button></form></div></div>
@endif
<div class="content-card">
    <div class="card-head flex-column flex-sm-row align-items-start align-items-sm-center gap-3"><div><div class="card-eyebrow">RECORDS</div><h3>All {{ strtolower($definition['title']) }} <span class="record-counter">{{ number_format($records->total()) }}</span></h3></div>
    @if($resource === 'employees' || $resource === 'attendances')
    <form method="get" action="{{ route('rems.resources.index',$resource) }}" class="list-filter">
        @if($resource === 'employees')
            <label class="filter-search"><i class="bi bi-search"></i><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search employees..." aria-label="Search employees"></label>
        @else
            <label class="filter-search"><i class="bi bi-calendar-event"></i><input name="date" type="date" value="{{ request('date') }}" class="form-control" aria-label="Filter by date"></label>
        @endif
        <button class="btn btn-outline-primary" type="submit">Filter</button>
        @if(request()->hasAny(['search','date']))<a href="{{ route('rems.resources.index',$resource) }}" class="btn btn-light border">Clear</a>@endif
    </form>
    @endif</div>
    <div class="table-responsive"><table class="table rems-table align-middle mb-0"><thead><tr><th class="ps-4">#</th>@foreach($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach<th class="text-end pe-4">Actions</th></tr></thead><tbody>
    @forelse($records as $record)
        <tr><td class="ps-4 text-muted small">{{ ($records->currentPage()-1)*$records->perPage()+$loop->iteration }}</td>
            @foreach($definition['columns'] as $path => $label)
                @php
                    $cell = data_get($record,$path);
                    if ($cell instanceof \Carbon\CarbonInterface) $cell = $cell->format(in_array($path,['check_in','check_out'],true) ? 'd M Y, h:i A' : 'd M Y');
                    $isStatus = in_array($path,['status','approval_status','priority','is_active','is_paid','is_primary'],true);
                    $isMoney = in_array($path,['basic_salary','house_allowance','transport_allowance','gross_salary','net_salary','amount'],true);
                @endphp
                <td class="{{ $loop->first ? 'fw-semibold' : '' }}">
                    @if($isStatus)@include('rems.partials.status',['value'=>$cell])
                    @elseif($isMoney)৳{{ number_format((float)$cell,2) }}
                    @elseif($cell === null || $cell === '')<span class="text-muted">—</span>
                    @else{{ \Illuminate\Support\Str::limit((string)$cell,48) }}@endif
                </td>
            @endforeach
            <td class="text-end pe-4"><div class="d-flex justify-content-end gap-2"><a class="table-icon-link" href="{{ route('rems.resources.show',[$resource,$record->id]) }}" title="Details" aria-label="View details"><i class="bi bi-eye"></i></a>
            @if(isset($definition['update_method'],$definition['edit']) && auth()->user()->can($definition['edit']))<a class="table-icon-link" href="{{ route('rems.resources.edit',[$resource,$record->id]) }}" title="Edit" aria-label="Edit record"><i class="bi bi-pencil-square"></i></a>@endif</div></td>
        </tr>
    @empty<tr><td class="p-5 text-center" colspan="{{ count($definition['columns'])+2 }}"><div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><h4>Nothing here yet</h4><p>No records found for this section. {{ request()->hasAny(['date','search']) ? 'Try a different filter.' : 'Your records will appear here when available.' }}</p></div></td></tr>@endforelse
    </tbody></table></div>
    @if($records->hasPages())<div class="card-pagination">{{ $records->links('pagination::bootstrap-5') }}</div>@else<div class="card-pagination d-flex justify-content-between align-items-center"><span>Showing {{ $records->count() }} of {{ $records->total() }} records</span><span>EstateHR · Data is role scoped</span></div>@endif
</div>
@endsection
