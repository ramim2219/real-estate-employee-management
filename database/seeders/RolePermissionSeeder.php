<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $groups = [
            'departments'=>['view','manage'],
            'designations'=>['view','manage'],
            'work_schedules'=>['view','manage'],
            'employees'=>['view','manage'],
            'projects'=>['view','manage'],
            'assignments'=>['view','manage'],
            'holidays'=>['view','manage'],
            'attendances'=>['view','manage','review'],
            'leave_types'=>['view','manage'],
            'leaves'=>['view','create','review'],
            'tasks'=>['view','manage','review'],
            'salaries'=>['view','manage'],
            'payrolls'=>['view','manage','approve'],
            'payments'=>['view','manage'],
            'documents'=>['view','manage'],
        ];
        $allNames = [];
        foreach ($groups as $key => $actions) {
            foreach ($actions as $action) {
                $allNames[] = $key.'.'.$action;
                Permission::firstOrCreate(['name'=>$key.'.'.$action,'guard_name'=>'web']);
            }
        }
        $rolePermissions = [
            'super_admin' => $allNames,
            'hr_admin' => [
                'departments.view','departments.manage','designations.view','designations.manage',
                'work_schedules.view','work_schedules.manage','employees.view','employees.manage',
                'projects.view','projects.manage','assignments.view','assignments.manage',
                'holidays.view','holidays.manage','attendances.view','attendances.manage','attendances.review',
                'leave_types.view','leave_types.manage','leaves.view','leaves.create','leaves.review',
                'tasks.view','tasks.manage','tasks.review','salaries.view','salaries.manage',
                'payrolls.view','documents.view','documents.manage',
            ],
            'project_manager' => [
                'employees.view','projects.view','assignments.view','attendances.view','attendances.review',
                'tasks.view','tasks.manage','tasks.review',
            ],
            'accounts' => ['salaries.view','payrolls.view','payrolls.manage','payrolls.approve','payments.view','payments.manage'],
            'employee' => ['attendances.view','leaves.view','leaves.create','tasks.view','payrolls.view','payments.view','documents.view'],
        ];
        foreach ($rolePermissions as $name => $permissions) {
            $role = Role::firstOrCreate(['name'=>$name,'guard_name'=>'web']);
            $role->syncPermissions($permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
