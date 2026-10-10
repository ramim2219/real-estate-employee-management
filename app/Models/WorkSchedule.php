<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'working_days',
        'grace_period_minutes',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'grace_period_minutes' => 'integer',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'work_schedule_id');
    }
}
