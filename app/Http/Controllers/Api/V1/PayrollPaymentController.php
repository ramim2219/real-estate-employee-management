<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Support\Access;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PayrollPaymentController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(),'payments.view');
        $query = PayrollPayment::with('payroll.employee:id,employee_code,first_name,last_name');
        if (! $request->user()->hasAnyRole(['super_admin','accounts'])) {
            $id = Access::ownEmployee($request->user())->id;
            $query->whereHas('payroll', fn ($q) => $q->where('employee_id',$id));
        }
        return $query->orderByDesc('payment_date')->paginate(20);
    }

    public function show(Request $request, PayrollPayment $payrollPayment)
    {
        Access::require($request->user(),'payments.view');
        abort_unless($request->user()->hasAnyRole(['super_admin','accounts'])
            || $payrollPayment->payroll?->employee?->user_id === $request->user()->id,403);
        return $payrollPayment->load('payroll');
    }

    public function store(Request $request)
    {
        Access::require($request->user(),'payments.manage');
        $data = $request->validate([
            'payroll_id'=>['required','exists:payrolls,id'],
            'amount'=>['required','numeric','gt:0','max:9999999999'],
            'payment_method'=>['required',Rule::in(['cash','bank_transfer','mobile_banking'])],
            'transaction_reference'=>['nullable','string','max:150'],
            'payment_date'=>['required','date_format:Y-m-d','before_or_equal:today'],
        ]);
        return DB::transaction(function () use ($request,$data) {
            $payroll = Payroll::whereKey($data['payroll_id'])->lockForUpdate()->firstOrFail();
            abort_unless(in_array($payroll->status,['approved','partially_paid'],true),422,
                'Payroll must be approved and not fully paid.');
            $alreadyPaid = $payroll->payments()->get()->sum(fn ($row) => Money::toPaisa($row->amount));
            $payment = Money::toPaisa($data['amount']);
            abort_unless($payment > 0,422,'Minimum payment is 0.01.');
            $total = Money::toPaisa($payroll->net_salary);
            abort_if($alreadyPaid + $payment > $total,422,'Payment exceeds remaining salary.');
            $data['amount']=Money::toDecimal($payment);
            $data['recorded_by']=$request->user()->id;
            $created = PayrollPayment::create($data);
            $payroll->update(['status'=>$alreadyPaid + $payment === $total ? 'paid' : 'partially_paid']);
            return response()->json($created,201);
        });
    }

    public function destroy(Request $request, PayrollPayment $payrollPayment)
    {
        Access::require($request->user(),'payments.manage');
        abort(405,'Payments cannot be erased; use a controlled reversal process in a later version.');
    }
}
