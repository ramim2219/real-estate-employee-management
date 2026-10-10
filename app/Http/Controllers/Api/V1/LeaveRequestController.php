<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Services\LeaveDaysCalculator;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'leaves.view');
        $q = LeaveRequest::with('employee:id,first_name,last_name','leaveType:id,name');
        if (! Access::isHr($request->user())) $q->where('employee_id', Access::ownEmployee($request->user())->id);
        return $q->orderByDesc('id')->paginate(20);
    }

    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        Access::require($request->user(), 'leaves.view');
        abort_unless(Access::isHr($request->user()) || $leaveRequest->employee?->user_id === $request->user()->id,403);
        return $leaveRequest->load('leaveType','employee');
    }

    public function store(Request $request, LeaveDaysCalculator $calculator)
    {
        Access::require($request->user(), 'leaves.create');
        $data = $request->validate([
            'employee_id'=>['nullable','exists:employees,id'],
            'leave_type_id'=>['required','exists:leave_types,id'],
            'start_date'=>['required','date_format:Y-m-d'],
            'end_date'=>['required','date_format:Y-m-d','after_or_equal:start_date'],
            'reason'=>['required','string','max:3000'],
        ]);
        $employee = Access::isHr($request->user()) && ! empty($data['employee_id'])
            ? Employee::findOrFail($data['employee_id']) : Access::ownEmployee($request->user());
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        abort_unless($leaveType->is_active,422,'Leave type is disabled.');
        abort_unless(substr($data['start_date'],0,4) === substr($data['end_date'],0,4),422,
            'Split multi-year leave into separate requests.');
        $days = $calculator->count($employee,$data['start_date'],$data['end_date']);
        abort_if($days < 1,422,'No scheduled workdays in selected period.');
        $overlap = LeaveRequest::where('employee_id',$employee->id)
            ->whereIn('status',['pending','approved'])
            ->whereDate('start_date','<=',$data['end_date'])
            ->whereDate('end_date','>=',$data['start_date'])->exists();
        abort_if($overlap,422,'There is an overlapping leave request.');
        if ($leaveType->annual_allowance_days > 0) {
            $used = LeaveRequest::where('employee_id',$employee->id)->where('leave_type_id',$leaveType->id)
                ->whereIn('status',['approved','pending'])
                ->whereYear('start_date',(int)substr($data['start_date'],0,4))->sum('total_days');
            if ($used + $days > $leaveType->annual_allowance_days) {
                throw ValidationException::withMessages(['leave_type_id'=>'Annual leave allowance exceeded.']);
            }
        }
        return response()->json(LeaveRequest::create([
            'employee_id'=>$employee->id,'leave_type_id'=>$leaveType->id,
            'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],
            'total_days'=>$days,'reason'=>$data['reason'],'status'=>'pending',
        ]),201);
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        Access::require($request->user(), 'leaves.review');
        abort_unless($leaveRequest->status === 'pending',422,'Only pending leave can be reviewed.');
        $data = $request->validate(['status'=>['required',Rule::in(['approved','rejected'])]]);
        if ($data['status'] === 'approved') {
            foreach (\Carbon\CarbonPeriod::create($leaveRequest->start_date->copy()->startOfMonth(), '1 month', $leaveRequest->end_date->copy()->startOfMonth()) as $period) {
                abort_if(Payroll::where('employee_id',$leaveRequest->employee_id)->where('year',$period->year)
                    ->where('month',$period->month)->where('status','!=','draft')->exists(),422,
                    'Cannot approve leave affecting an already approved/paid payroll month.');
            }
        }
        $leaveRequest->update(['status'=>$data['status'],'approved_by'=>$request->user()->id,'decided_at'=>now()]);
        return $leaveRequest->refresh();
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $owner = $leaveRequest->employee?->user_id === $request->user()->id;
        abort_unless($owner || Access::isHr($request->user()),403);
        abort_unless($leaveRequest->status === 'pending',422,'Only pending leave can be cancelled.');
        $leaveRequest->update(['status'=>'cancelled']);
        return $leaveRequest->refresh();
    }
}
