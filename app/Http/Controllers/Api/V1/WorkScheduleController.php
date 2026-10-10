<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'work_schedules.view');
        return WorkSchedule::query()->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, WorkSchedule $workSchedule)
    {
        Access::require($request->user(), 'work_schedules.view');
        return $workSchedule;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'name' => ['required','string','max:100', Rule::unique('work_schedules','name')->ignore($id)],
            'start_time' => ['required','date_format:H:i'],
            'end_time' => ['required','date_format:H:i','different:start_time'],
            'working_days' => ['required','array','min:1','max:7'],
            'working_days.*' => ['required','integer','between:1,7','distinct'],
            'grace_period_minutes' => ['sometimes','integer','between:0,120'],
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
        Access::require($request->user(), 'work_schedules.manage');
        $data = $request->validate($this->rules());
        return response()->json(WorkSchedule::create($data), 201);
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        Access::require($request->user(), 'work_schedules.manage');
        $data = $request->validate($this->rules($workSchedule->id));
        $workSchedule->update($data);
        return $workSchedule->refresh();
    }

    public function destroy(Request $request, WorkSchedule $workSchedule)
    {
        Access::require($request->user(), 'work_schedules.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        abort(405, 'Work schedules assigned to employees must be retained.');
        return response()->noContent();
    }
}
