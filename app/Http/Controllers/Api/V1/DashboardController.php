<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Task;
use App\Support\Access;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user=$request->user();
        if (Access::isHr($user)) {
            return response()->json([
                'active_employees'=>Employee::where('status','active')->count(),
                'ongoing_projects'=>Project::where('status','ongoing')->count(),
                'pending_leave_requests'=>LeaveRequest::where('status','pending')->count(),
                'today_pending_attendance'=>Attendance::whereDate('date',today())->where('approval_status','pending')->count(),
            ]);
        }
        if ($user->hasRole('accounts')) {
            return response()->json([
                'draft_payrolls'=>Payroll::where('status','draft')->count(),
                'approved_unpaid_payrolls'=>Payroll::whereIn('status',['approved','partially_paid'])->count(),
            ]);
        }
        $employee=Access::ownEmployee($user);
        if ($user->hasRole('project_manager')) {
            $projectIds=Access::managedProjectIds($user);
            return response()->json([
                'managed_projects'=>count($projectIds),
                'pending_project_tasks'=>Task::whereIn('project_id',$projectIds)->whereNotIn('status',['approved'])->count(),
            ]);
        }
        return response()->json([
            'my_pending_tasks'=>Task::where('assigned_to',$employee->id)->whereNotIn('status',['approved'])->count(),
            'my_pending_leaves'=>LeaveRequest::where('employee_id',$employee->id)->where('status','pending')->count(),
            'this_month_attendance'=>Attendance::where('employee_id',$employee->id)->whereYear('date',today()->year)->whereMonth('date',today()->month)->count(),
        ]);
    }
}
