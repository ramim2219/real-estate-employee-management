<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectAssignment;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectAssignmentController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'assignments.view');
        $query = ProjectAssignment::with('employee:id,employee_code,first_name,last_name','project:id,name');
        if (! Access::isHr($request->user())) $query->whereIn('project_id', Access::managedProjectIds($request->user()));
        return $query->when($request->project_id, fn ($q) => $q->where('project_id',$request->integer('project_id')))
            ->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        Access::require($request->user(), 'assignments.manage');
        $data = $request->validate([
            'project_id'=>['required','exists:projects,id'],
            'employee_id'=>['required','exists:employees,id'],
            'assignment_role'=>['nullable','string','max:100'],
            'start_date'=>['required','date_format:Y-m-d'],
            'end_date'=>['nullable','date_format:Y-m-d','after_or_equal:start_date'],
            'is_primary'=>['sometimes','boolean'],
        ]);
        if (ProjectAssignment::where('employee_id',$data['employee_id'])
            ->where('project_id',$data['project_id'])
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date','>=',$data['start_date']))
            ->whereDate('start_date','<=',$data['end_date'] ?? '9999-12-31')->exists()) {
            throw ValidationException::withMessages(['employee_id'=>'Project assignment period overlaps another assignment.']);
        }
        $data['assigned_by']=$request->user()->id;
        return response()->json(ProjectAssignment::create($data),201);
    }

    public function show(Request $request, ProjectAssignment $projectAssignment)
    {
        Access::require($request->user(), 'assignments.view');
        abort_unless(Access::isHr($request->user()) || Access::managesProject($request->user(),$projectAssignment->project_id),403);
        return $projectAssignment->load('employee','project');
    }

    public function update(Request $request, ProjectAssignment $projectAssignment)
    {
        Access::require($request->user(), 'assignments.manage');
        // Historical project / employee / start date are immutable.
        $data = $request->validate([
            'assignment_role'=>['sometimes','nullable','string','max:100'],
            'end_date'=>['sometimes','nullable','date_format:Y-m-d','after_or_equal:'.$projectAssignment->start_date->toDateString()],
            'is_primary'=>['sometimes','boolean'],
        ]);
        $projectAssignment->update($data);
        return $projectAssignment->refresh();
    }

    public function destroy(Request $request, ProjectAssignment $projectAssignment)
    {
        Access::require($request->user(), 'assignments.manage');
        abort(405, 'Close the assignment by setting end_date; history must be retained.');
    }
}
