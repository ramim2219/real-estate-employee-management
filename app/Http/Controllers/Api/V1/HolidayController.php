<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'holidays.view');
        return Holiday::query()->orderBy('id', 'desc')->paginate(min(max((int) $request->input('per_page', 20), 1), 100));
    }

    public function show(Request $request, Holiday $holiday)
    {
        Access::require($request->user(), 'holidays.view');
        return $holiday;
    }

    private function rules(?int $id = null): array
    {
        $rules = [
            'name' => ['required','string','max:150'],
            'date' => ['required','date_format:Y-m-d', Rule::unique('holidays','date')->ignore($id)],
            'is_paid' => ['sometimes','boolean'],
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
        Access::require($request->user(), 'holidays.manage');
        $data = $request->validate($this->rules());
        return response()->json(Holiday::create($data), 201);
    }

    public function update(Request $request, Holiday $holiday)
    {
        Access::require($request->user(), 'holidays.manage');
        $data = $request->validate($this->rules($holiday->id));
        $holiday->update($data);
        return $holiday->refresh();
    }

    public function destroy(Request $request, Holiday $holiday)
    {
        Access::require($request->user(), 'holidays.manage');
        // Keep historical rows. Disable a master record instead of deleting referenced data.
        abort(405, 'Historical holidays cannot be deleted without a controlled correction.');
        return response()->noContent();
    }
}
