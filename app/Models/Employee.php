<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'user_id',
        'department_id',
        'designation_id',
        'work_schedule_id',
        'reports_to_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'joining_date',
        'employment_type',
        'status',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reports_to_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'reports_to_id');
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function salaryHistories(): HasMany
    {
        return $this->hasMany(SalaryHistory::class, 'employee_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }
}
