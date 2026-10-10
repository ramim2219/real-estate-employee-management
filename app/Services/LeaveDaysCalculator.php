<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class LeaveDaysCalculator
{
    public function count(Employee $employee, string $from, string $to): int
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();
        abort_if($end->lt($start), 422, 'End date must be after start date.');
        abort_if($start->diffInDays($end) > 366, 422, 'Date range is too long.');

        $days = $employee->workSchedule?->working_days ?? [1, 2, 3, 4, 5];
        $holidays = Holiday::query()->whereBetween('date', [$from, $to])
            ->pluck('date')->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())->all();
        $holidaySet = array_flip($holidays);
        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if (in_array($day->dayOfWeekIso, $days, true)
                && ! isset($holidaySet[$day->toDateString()])) {
                $count++;
            }
        }
        return $count;
    }
}
