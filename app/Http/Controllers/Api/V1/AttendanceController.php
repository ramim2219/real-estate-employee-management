<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ProjectAssignment;
use App\Models\Payroll;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'attendances.view');
        $query = Attendance::with('employee:id,first_name,last_name,employee_code','project:id,name');
        if (! Access::isHr($request->user())) {
            $projects = Access::managedProjectIds($request->user());
            $employeeId = Employee::where('user_id',$request->user()->id)->value('id');
            $query->where(fn ($q) => $q->where('employee_id',$employeeId ?? 0)
                ->orWhereIn('project_id',$projects));
        }
        return $query->when($request->filled('date'),fn ($q) => $q->whereDate('date',$request->date))
            ->latest('date')->paginate(20);
    }

    public function show(Request $request, Attendance $attendance)
    {
        Access::require($request->user(), 'attendances.view');
        abort_unless(Access::isHr($request->user()) || $attendance->employee?->user_id === $request->user()->id
            || ($attendance->project_id && Access::managesProject($request->user(),$attendance->project_id)),403);
        return $attendance->load('employee','project');
    }

    public function store(Request $request)
    {
        Access::require($request->user(), 'attendances.manage');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'date'=>['required','date_format:Y-m-d','before_or_equal:today'],
            'check_in'=>['nullable','date'],
            'check_out'=>['nullable','date','after:check_in'],
            'status'=>['required',Rule::in(['present','late','absent','half_day','on_leave'])],
            'remarks'=>['nullable','string','max:2000'],
        ]);
        if (isset($data['project_id'])) $this->assertAssigned($data['employee_id'], $data['project_id'], $data['date']);
        abort_if(Attendance::where('employee_id',$data['employee_id'])->whereDate('date',$data['date'])->exists(), 422,
            'Attendance already exists for this employee/date.');
        $this->assertPayrollOpen((int)$data['employee_id'], $data['date']);
        $data['approval_status']='pending';
        return response()->json(Attendance::create($data),201);
    }

    public function update(Request $request, Attendance $attendance)
    {
        Access::require($request->user(), 'attendances.manage');
        abort_unless($attendance->approval_status === 'pending',422,'Reviewed records must not be silently edited.');
        $this->assertPayrollOpen($attendance->employee_id, $attendance->date->toDateString());
        $data = $request->validate([
            'check_in'=>['sometimes','nullable','date'],
            'check_out'=>['sometimes','nullable','date'],
            'status'=>['sometimes',Rule::in(['present','late','absent','half_day','on_leave'])],
            'remarks'=>['sometimes','nullable','string','max:2000'],
        ]);
        $start = $data['check_in'] ?? $attendance->check_in?->toDateTimeString();
        $end = $data['check_out'] ?? $attendance->check_out?->toDateTimeString();
        abort_if($start && $end && Carbon::parse($end)->lte(Carbon::parse($start)),422,'Check-out must follow check-in.');
        $attendance->update($data);
        return $attendance->refresh();
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();
        $employee = Access::ownEmployee($user)->load('workSchedule');
        abort_unless($employee->status === 'active',403,'Employee must be active to check in.');
        $data = $request->validate(['project_id'=>['nullable','exists:projects,id']]);
        $now = now();
        if (! empty($data['project_id'])) $this->assertAssigned($employee->id,$data['project_id'],$now->toDateString());
        abort_if(Attendance::where('employee_id',$employee->id)->whereDate('date',$now->toDateString())->exists(),422,
            'Attendance already recorded today.');
        $schedule = $employee->workSchedule;
        $latest = Carbon::parse($now->toDateString().' '.$schedule->start_time)
            ->addMinutes($schedule->grace_period_minutes);
        $status = $now->greaterThan($latest) ? 'late' : 'present';
        return response()->json(Attendance::create([
            'employee_id'=>$employee->id,
            'project_id'=>$data['project_id'] ?? null,
            'date'=>$now->toDateString(),
            'check_in'=>$now,
            'status'=>$status,
            'approval_status'=>'pending',
        ]),201);
    }

    public function checkOut(Request $request)
    {
        $employee = Access::ownEmployee($request->user());
        $attendance = Attendance::where('employee_id',$employee->id)->whereDate('date',today())
            ->whereNotNull('check_in')->whereNull('check_out')->firstOrFail();
        abort_unless($attendance->approval_status === 'pending',422,'Attendance is already reviewed.');
        $attendance->update(['check_out'=>now()]);
        return $attendance->refresh();
    }

    public function review(Request $request, Attendance $attendance)
    {
        Access::require($request->user(), 'attendances.review');
        abort_unless(Access::isHr($request->user()) || ($attendance->project_id &&
            Access::managesProject($request->user(),$attendance->project_id)),403);
        abort_unless($attendance->approval_status === 'pending',422,'Attendance already reviewed.');
        $this->assertPayrollOpen($attendance->employee_id, $attendance->date->toDateString());
        $data = $request->validate([
            'approval_status'=>['required',Rule::in(['approved','rejected'])],
            'approved_overtime_minutes'=>['sometimes','integer','between:0,1440'],
        ]);
        $minutes = (int) ($data['approved_overtime_minutes'] ?? 0);
        if ($data['approval_status'] === 'approved' && in_array($attendance->status,['present','late','half_day'],true)) {
            abort_unless($attendance->check_in && $attendance->check_out,422,'Missing check-in/check-out.');
            $worked = (int) $attendance->check_in->diffInMinutes($attendance->check_out);
            abort_if($minutes > $worked,422,'Overtime cannot exceed worked minutes.');
        } else $minutes = 0;
        $attendance->update([
            'approval_status'=>$data['approval_status'],
            'approved_overtime_minutes'=>$minutes,
            'verified_by'=>$request->user()->id,
        ]);
        return $attendance->refresh();
    }

    public function destroy(Request $request, Attendance $attendance)
    {
        Access::require($request->user(), 'attendances.manage');
        abort(405,'Historical attendance cannot be deleted.');
    }

    private function assertPayrollOpen(int $employeeId, string $date): void
    {
        $period = Carbon::parse($date);
        abort_if(Payroll::where('employee_id',$employeeId)->where('year',$period->year)->where('month',$period->month)
            ->where('status','!=','draft')->exists(),422,'Payroll for this month is already approved/paid.');
    }

    private function assertAssigned(int $employeeId, int $projectId, string $date): void
    {
        abort_unless(ProjectAssignment::where('employee_id',$employeeId)->where('project_id',$projectId)
            ->whereDate('start_date','<=',$date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date','>=',$date))->exists(),422,
            'Employee is not assigned to this project on this date.');
    }
}
