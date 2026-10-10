<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'annual_allowance_days',
        'is_paid',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_allowance_days' => 'integer',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
