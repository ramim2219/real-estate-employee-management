<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'leave_types.view');
        return LeaveType::query()->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, LeaveType $leaveType)
    {
        Access::require($request->user(), 'leave_types.view');
        return $leaveType;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'name' => ['required','string','max:100', Rule::unique('leave_types','name')->ignore($id)],
            'annual_allowance_days' => ['required','integer','between:0,366'],
            'is_paid' => ['required','boolean'],
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
        Access::require($request->user(), 'leave_types.manage');
        $data = $request->validate($this->rules());
        return response()->json(LeaveType::create($data), 201);
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        Access::require($request->user(), 'leave_types.manage');
        $data = $request->validate($this->rules($leaveType->id));
        $leaveType->update($data);
        return $leaveType->refresh();
    }

    public function destroy(Request $request, LeaveType $leaveType)
    {
        Access::require($request->user(), 'leave_types.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        $leaveType->update(['is_active' => false]);
        return response()->noContent();
    }
}
