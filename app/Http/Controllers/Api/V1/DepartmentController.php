<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'departments.view');
        return Department::query()->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, Department $department)
    {
        Access::require($request->user(), 'departments.view');
        return $department;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'name' => ['required','string','max:100', Rule::unique('departments', 'name')->ignore($id)],
            'code' => ['required','string','max:20', Rule::unique('departments','code')->ignore($id)],
            'description' => ['nullable','string'],
            'is_active' => ['sometimes','boolean'],
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
        Access::require($request->user(), 'departments.manage');
        $data = $request->validate($this->rules());
        return response()->json(Department::create($data), 201);
    }

    public function update(Request $request, Department $department)
    {
        Access::require($request->user(), 'departments.manage');
        $data = $request->validate($this->rules($department->id));
        $department->update($data);
        return $department->refresh();
    }

    public function destroy(Request $request, Department $department)
    {
        Access::require($request->user(), 'departments.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        $department->update(['is_active' => false]);
        return response()->noContent();
    }
}
