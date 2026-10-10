<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'basic_salary',
        'allowances',
        'overtime_pay',
        'deductions',
        'gross_salary',
        'net_salary',
        'status',
        'processed_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class, 'payroll_id');
    }
}
