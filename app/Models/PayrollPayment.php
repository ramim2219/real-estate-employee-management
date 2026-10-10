<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPayment extends Model
{
    protected $fillable = [
        'payroll_id',
        'amount',
        'payment_method',
        'transaction_reference',
        'payment_date',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
