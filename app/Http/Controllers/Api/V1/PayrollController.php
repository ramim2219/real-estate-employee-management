<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollCalculator;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(),'payrolls.view');
        $q = Payroll::with('employee:id,employee_code,first_name,last_name');
        if (! $request->user()->hasAnyRole(['super_admin','hr_admin','accounts'])) {
            $q->where('employee_id', Access::ownEmployee($request->user())->id);
        }
        return $q->orderByDesc('year')->orderByDesc('month')->paginate(20);
    }

    public function show(Request $request, Payroll $payroll)
    {
        Access::require($request->user(),'payrolls.view');
        abort_unless($request->user()->hasAnyRole(['super_admin','hr_admin','accounts'])
            || $payroll->employee?->user_id === $request->user()->id,403);
        return $payroll->load('employee:id,employee_code,first_name,last_name','payments');
    }

    public function generate(Request $request, PayrollCalculator $calculator)
    {
        Access::require($request->user(),'payrolls.manage');
        $data = $request->validate([
            'employee_id'=>['required','exists:employees,id'],
            'year'=>['required','integer','between:2020,2100'],
            'month'=>['required','integer','between:1,12'],
        ]);
        $employee = Employee::with('workSchedule')->findOrFail($data['employee_id']);
        // Drafts may be regenerated, but approved or paid salary is never overwritten.
        return DB::transaction(function () use ($request,$data,$employee,$calculator) {
            $existing = Payroll::where('employee_id',$employee->id)->where('year',$data['year'])
                ->where('month',$data['month'])->lockForUpdate()->first();
            abort_if($existing && $existing->status !== 'draft',422,'Approved/paid payroll is locked.');
            $values = $calculator->calculate($employee,(int)$data['year'],(int)$data['month']);
            $payroll = $existing ?? new Payroll([
                'employee_id'=>$employee->id,'year'=>$data['year'],'month'=>$data['month'],
            ]);
            $payroll->fill($values + ['status'=>'draft','processed_by'=>$request->user()->id]);
            $payroll->save();
            return response()->json($payroll,$existing ? 200 : 201);
        });
    }

    public function approve(Request $request, Payroll $payroll)
    {
        Access::require($request->user(),'payrolls.approve');
        return DB::transaction(function () use ($payroll,$request) {
            $record = Payroll::whereKey($payroll->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'draft',422,'Only draft payroll can be approved.');
            $record->update(['status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]);
            return $record;
        });
    }

    public function destroy(Request $request, Payroll $payroll)
    {
        Access::require($request->user(),'payrolls.manage');
        abort(405,'Payroll records are retained for financial audit.');
    }
}
