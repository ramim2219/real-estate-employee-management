<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\SalaryHistory;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PayrollCalculator
{
    public function __construct(private LeaveDaysCalculator $days) {}

    public function calculate(Employee $employee, int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();
        if ($employee->joining_date->greaterThan($end)) {
            throw ValidationException::withMessages(['month' => 'Employee had not joined in this month.']);
        }
        $effectiveSalary = SalaryHistory::where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $start->toDateString()))
            ->orderByDesc('effective_from')->first();
        if (! $effectiveSalary) {
            throw ValidationException::withMessages(['employee_id' => 'Salary structure is missing for this period.']);
        }
        // A month spanning a salary revision needs more than one rate period. Reject for manual review.
        $changes = SalaryHistory::where('employee_id', $employee->id)
            ->whereBetween('effective_from', [$start->toDateString(), $end->toDateString()])->count();
        if ($changes > 0 && $effectiveSalary->effective_from->gt($start)
            && ! $effectiveSalary->effective_from->isSameDay($employee->joining_date)) {
            throw ValidationException::withMessages(['month' => 'Mid-month salary revision requires manual handling; no payroll was created.']);
        }
        $employee->loadMissing('workSchedule');
        $scheduledDays = $this->days->count($employee, $start->toDateString(), $end->toDateString());
        if ($scheduledDays === 0) {
            throw ValidationException::withMessages(['month' => 'No scheduled workdays in this month.']);
        }
        $eligibleFrom = $employee->joining_date->gt($start) ? $employee->joining_date : $start;
        $eligibleDays = $this->days->count($employee, $eligibleFrom->toDateString(), $end->toDateString());
        $baseMonthly = Money::toPaisa($effectiveSalary->basic_salary);
        $allowanceMonthly = Money::toPaisa($effectiveSalary->house_allowance)
            + Money::toPaisa($effectiveSalary->transport_allowance)
            + Money::toPaisa($effectiveSalary->other_allowance);
        $basic = (int) round($baseMonthly * $eligibleDays / $scheduledDays);
        $allowances = (int) round($allowanceMonthly * $eligibleDays / $scheduledDays);

        $overtime = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('approval_status', 'approved')->sum('approved_overtime_minutes');
        $overtimePay = (int) round($overtime * Money::toPaisa($effectiveSalary->overtime_hourly_rate) / 60);

        $unpaidDays = 0;
        $unpaid = LeaveRequest::with('leaveType')->where('employee_id', $employee->id)
            ->where('status', 'approved')->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())->get();
        foreach ($unpaid as $leave) {
            if ($leave->leaveType->is_paid) continue;
            $from = $leave->start_date->gt($eligibleFrom) ? $leave->start_date : $eligibleFrom;
            $to = $leave->end_date->lt($end) ? $leave->end_date : $end;
            if ($from->lte($to)) $unpaidDays += $this->days->count($employee, $from->toDateString(), $to->toDateString());
        }
        $unpaidDays = min($unpaidDays, $eligibleDays);
        $deductions = (int) round(($baseMonthly + $allowanceMonthly) * $unpaidDays / $scheduledDays);
        $gross = $basic + $allowances + $overtimePay;
        $net = max(0, $gross - $deductions);
        return [
            'basic_salary' => Money::toDecimal($basic),
            'allowances' => Money::toDecimal($allowances),
            'overtime_pay' => Money::toDecimal($overtimePay),
            'deductions' => Money::toDecimal($deductions),
            'gross_salary' => Money::toDecimal($gross),
            'net_salary' => Money::toDecimal($net),
        ];
    }
}
