<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalaryHistory;
use App\Support\Access;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryHistoryController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(),'salaries.view');
        $data = $request->validate(['employee_id'=>['nullable','exists:employees,id']]);
        return SalaryHistory::query()->when($data['employee_id'] ?? null,
            fn ($q,$id) => $q->where('employee_id',$id))->orderByDesc('effective_from')->paginate(20);
    }

    public function show(Request $request, SalaryHistory $salaryHistory)
    {
        Access::require($request->user(),'salaries.view');
        return $salaryHistory;
    }

    public function store(Request $request)
    {
        Access::require($request->user(),'salaries.manage');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],
            'basic_salary'=>['required','numeric','min:0','max:9999999999'],
            'house_allowance'=>['sometimes','numeric','min:0','max:9999999999'],
            'transport_allowance'=>['sometimes','numeric','min:0','max:9999999999'],
            'other_allowance'=>['sometimes','numeric','min:0','max:9999999999'],
            'overtime_hourly_rate'=>['sometimes','numeric','min:0','max:9999999999'],
            'effective_from'=>['required','date_format:Y-m-d'],
        ]);
        return DB::transaction(function () use ($request,$data) {
            $last = SalaryHistory::where('employee_id',$data['employee_id'])->orderByDesc('effective_from')
                ->lockForUpdate()->first();
            if ($last) {
                abort_unless($last->effective_to === null,422,'Latest salary record is closed.');
                abort_unless(CarbonImmutable::parse($data['effective_from'])->gt($last->effective_from),422,
                    'Effective date must be later than the existing salary start date.');
                $last->update(['effective_to'=>CarbonImmutable::parse($data['effective_from'])->subDay()->toDateString()]);
            }
            $data['changed_by']=$request->user()->id;
            return response()->json(SalaryHistory::create($data),201);
        });
    }

    public function update(Request $request, SalaryHistory $salaryHistory)
    {
        Access::require($request->user(),'salaries.manage');
        abort(405,'Salary history is immutable; create a new effective-dated salary row.');
    }

    public function destroy(Request $request, SalaryHistory $salaryHistory)
    {
        Access::require($request->user(),'salaries.manage');
        abort(405,'Salary history is immutable.');
    }
}
