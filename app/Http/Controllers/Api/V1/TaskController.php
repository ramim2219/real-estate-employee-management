<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Task;
use App\Models\ProjectAssignment;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(),'tasks.view');
        $query = Task::with('project:id,name','assignee:id,first_name,last_name');
        if (! Access::isHr($request->user())) {
            $projects = Access::managedProjectIds($request->user());
            $id = Employee::where('user_id',$request->user()->id)->value('id');
            $query->where(fn ($q) => $q->where('assigned_to',$id ?? 0)->orWhereIn('project_id',$projects));
        }
        return $query->orderByDesc('id')->paginate(20);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeView($request,$task);
        return $task->load('project','assignee');
    }

    private function authorizeView(Request $request, Task $task): void
    {
        Access::require($request->user(),'tasks.view');
        abort_unless(Access::isHr($request->user()) || $task->assignee?->user_id === $request->user()->id
            || ($task->project_id && Access::managesProject($request->user(),$task->project_id)),403);
    }

    private function assertManagerScope(Request $request, ?int $projectId, int $employeeId): void
    {
        if (Access::isHr($request->user())) return;
        abort_unless($projectId && Access::managesProject($request->user(),$projectId),403);
        abort_unless(ProjectAssignment::where('project_id',$projectId)->where('employee_id',$employeeId)
            ->whereDate('start_date','<=',today())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date','>=',today()))->exists(),422,
            'Assignee is not currently allocated to this site.');
    }

    public function store(Request $request)
    {
        Access::require($request->user(),'tasks.manage');
        $data = $request->validate([
            'project_id'=>['nullable','exists:projects,id'],
            'assigned_to'=>['required','exists:employees,id'],
            'title'=>['required','string','max:255'],
            'description'=>['required','string','max:10000'],
            'priority'=>['sometimes',Rule::in(['low','medium','high','urgent'])],
            'due_date'=>['nullable','date_format:Y-m-d'],
        ]);
        $this->assertManagerScope($request,$data['project_id'] ?? null,$data['assigned_to']);
        $data['assigned_by']=$request->user()->id;
        return response()->json(Task::create($data),201);
    }

    public function update(Request $request, Task $task)
    {
        Access::require($request->user(),'tasks.manage');
        $this->assertManagerScope($request,$task->project_id,$task->assigned_to);
        abort_if($task->status === 'approved',422,'Approved task cannot be changed.');
        $data = $request->validate([
            'title'=>['sometimes','string','max:255'],
            'description'=>['sometimes','string','max:10000'],
            'priority'=>['sometimes',Rule::in(['low','medium','high','urgent'])],
            'due_date'=>['sometimes','nullable','date_format:Y-m-d'],
        ]);
        $task->update($data);
        return $task->refresh();
    }

    public function submit(Request $request, Task $task)
    {
        abort_unless($task->assignee?->user_id === $request->user()->id,403);
        abort_unless(in_array($task->status,['pending','in_progress','revision_required'],true),422);
        $data = $request->validate([
            'task_report'=>['required','string','max:10000'],
            'status'=>['sometimes',Rule::in(['in_progress','submitted'])],
        ]);
        $task->update(['task_report'=>$data['task_report'],'status'=>$data['status'] ?? 'submitted']);
        return $task->refresh();
    }

    public function review(Request $request, Task $task)
    {
        Access::require($request->user(),'tasks.review');
        $this->assertManagerScope($request,$task->project_id,$task->assigned_to);
        abort_unless($task->status === 'submitted',422,'Only submitted tasks can be reviewed.');
        $data = $request->validate([
            'status'=>['required',Rule::in(['approved','revision_required'])],
            'review_feedback'=>['nullable','string','max:10000'],
        ]);
        $task->update([
            'status'=>$data['status'],
            'review_feedback'=>$data['review_feedback'] ?? null,
            'reviewed_by'=>$request->user()->id,
            'completed_at'=>$data['status']==='approved' ? now() : null,
        ]);
        return $task->refresh();
    }

    public function destroy(Request $request, Task $task)
    {
        Access::require($request->user(),'tasks.manage');
        abort(405,'Tasks are retained for accountability; do not delete.');
    }
}
