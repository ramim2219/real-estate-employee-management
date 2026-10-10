<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'project_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'approval_status',
        'approved_overtime_minutes',
        'verified_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'approved_overtime_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
