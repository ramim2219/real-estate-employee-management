<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'projects.view');
        $query = Project::query();
        if (! Access::isHr($request->user())) $query->whereIn('id', Access::managedProjectIds($request->user()));
        return $query->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, Project $project)
    {
        Access::require($request->user(), 'projects.view');
        abort_unless(Access::isHr($request->user()) || Access::managesProject($request->user(), $project->id), 403);
        return $project;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'project_code' => ['required','string','max:30', Rule::unique('projects','project_code')->ignore($id)],
            'name' => ['required','string','max:180'],
            'location' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'start_date' => ['nullable','date_format:Y-m-d'],
            'end_date' => ['nullable','date_format:Y-m-d'],
            'status' => ['sometimes', Rule::in(['planned','ongoing','on_hold','completed','cancelled'])],
        ];
        if ($id !== null) {
            foreach ($rules as $key => &$checks) {
                if (! str_contains($key, '*') && ($checks[0] ?? null) === 'required') $checks[0] = 'sometimes';
            }
            unset($checks);
        }
        return $rules;
    }

    public function store(Request $request)
    {
        Access::require($request->user(), 'projects.manage');
        $data = $request->validate($this->rules());
        abort_if(!empty($data['start_date']) && !empty($data['end_date']) && \Carbon\Carbon::parse($data['end_date'])->lt($data['start_date']),422,'End date precedes start date.');
        return response()->json(Project::create($data), 201);
    }

    public function update(Request $request, Project $project)
    {
        Access::require($request->user(), 'projects.manage');
        $data = $request->validate($this->rules($project->id));
        if (! empty($data['end_date'])) {
            abort_if($project->start_date && \Carbon\Carbon::parse($data['end_date'])->lt($data['start_date'] ?? $project->start_date), 422, 'End date precedes start date.');
        }
        $project->update($data);
        return $project->refresh();
    }

    public function destroy(Request $request, Project $project)
    {
        Access::require($request->user(), 'projects.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        abort(405, 'Archive projects through the status field instead of deleting.');
        return response()->noContent();
    }
}
