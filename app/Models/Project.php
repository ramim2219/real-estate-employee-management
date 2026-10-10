<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'project_code',
        'name',
        'location',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'project_id');
    }
}
