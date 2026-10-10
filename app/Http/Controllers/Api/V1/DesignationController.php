<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'designations.view');
        return Designation::query()->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, Designation $designation)
    {
        Access::require($request->user(), 'designations.view');
        return $designation;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'name' => ['required','string','max:100', Rule::unique('designations','name')->ignore($id)],
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
        Access::require($request->user(), 'designations.manage');
        $data = $request->validate($this->rules());
        return response()->json(Designation::create($data), 201);
    }

    public function update(Request $request, Designation $designation)
    {
        Access::require($request->user(), 'designations.manage');
        $data = $request->validate($this->rules($designation->id));
        $designation->update($data);
        return $designation->refresh();
    }

    public function destroy(Request $request, Designation $designation)
    {
        Access::require($request->user(), 'designations.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        $designation->update(['is_active' => false]);
        return response()->noContent();
    }
}
