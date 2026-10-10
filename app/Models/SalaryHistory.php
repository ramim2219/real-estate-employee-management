<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'basic_salary',
        'house_allowance',
        'transport_allowance',
        'other_allowance',
        'overtime_hourly_rate',
        'effective_from',
        'effective_to',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'house_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'overtime_hourly_rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
