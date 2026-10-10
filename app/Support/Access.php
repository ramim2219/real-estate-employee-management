<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Access
{
    public static function require(User $user, string $permission): void
    {
        abort_unless($user->can($permission), 403, 'You do not have permission.');
    }

    public static function isHr(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'hr_admin']);
    }

    public static function managedProjectIds(User $user): array
    {
        if (! $user->hasRole('project_manager')) {
            return [];
        }

        $employeeId = Employee::where('user_id', $user->id)->value('id');
        if (! $employeeId) {
            return [];
        }

        return ProjectAssignment::query()
            ->where('employee_id', $employeeId)
            ->where('assignment_role', 'Project Manager')
            ->whereDate('start_date', '<=', today())
            ->where(function (Builder $query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })->pluck('project_id')->all();
    }

    public static function managesProject(User $user, int $projectId): bool
    {
        return in_array($projectId, self::managedProjectIds($user), true);
    }

    public static function employeeInManagedProject(User $user, int $employeeId): bool
    {
        $projectIds = self::managedProjectIds($user);
        if ($projectIds === []) {
            return false;
        }

        return ProjectAssignment::query()
            ->whereIn('project_id', $projectIds)
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', today())
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->exists();
    }

    public static function maySeeEmployee(User $user, Employee $employee): bool
    {
        return self::isHr($user)
            || ($employee->user_id !== null && $employee->user_id === $user->id)
            || self::employeeInManagedProject($user, $employee->id);
    }

    public static function scopeEmployees(Builder $query, User $user): Builder
    {
        if (self::isHr($user)) {
            return $query;
        }
        $ids = self::managedProjectIds($user);
        if ($ids !== []) {
            return $query->whereHas('projectAssignments', function (Builder $q) use ($ids) {
                $q->whereIn('project_id', $ids)
                    ->whereDate('start_date', '<=', today())
                    ->where(fn (Builder $x) => $x->whereNull('end_date')->orWhereDate('end_date', '>=', today()));
            });
        }

        return $query->where('user_id', $user->id);
    }

    public static function ownEmployee(User $user): Employee
    {
        return Employee::query()->where('user_id', $user->id)->firstOrFail();
    }
}
