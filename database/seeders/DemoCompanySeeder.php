<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\SalaryHistory;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || ! config('rems.seed_demo')) return;
        $password = config('rems.demo_password');
        if (! $password || strlen($password) < 12) {
            throw new RuntimeException('Set REMS_DEMO_PASSWORD (12+ chars) before enabling demo seeding.');
        }
        $office=WorkSchedule::where('name','Office 9 AM - 5 PM')->firstOrFail();
        $site=WorkSchedule::where('name','Construction Site 8 AM - 4 PM')->firstOrFail();
        $records = [
            ['code'=>'DEMO-HR-001','name'=>'Demo HR Officer','email'=>'demo.hr@example.test','role'=>'hr_admin','department'=>'HR','designation'=>'HR Officer','schedule'=>$office,'salary'=>45000],
            ['code'=>'DEMO-PM-001','name'=>'Demo Project Manager','email'=>'demo.pm@example.test','role'=>'project_manager','department'=>'ENG','designation'=>'Project Manager','schedule'=>$site,'salary'=>65000],
            ['code'=>'DEMO-ENG-001','name'=>'Demo Site Engineer','email'=>'demo.engineer@example.test','role'=>'employee','department'=>'ENG','designation'=>'Site Engineer','schedule'=>$site,'salary'=>35000],
            ['code'=>'DEMO-ACC-001','name'=>'Demo Accountant','email'=>'demo.accounts@example.test','role'=>'accounts','department'=>'ACC','designation'=>'Accountant','schedule'=>$office,'salary'=>43000],
        ];
        $employees=[];
        foreach ($records as $row) {
            $user=User::firstOrCreate(['email'=>$row['email']], [
                'name'=>$row['name'],'password'=>Hash::make($password),
            ]);
            $user->assignRole($row['role']);
            $employee=Employee::firstOrCreate(['employee_code'=>$row['code']], [
                'user_id'=>$user->id,
                'department_id'=>Department::where('code',$row['department'])->value('id'),
                'designation_id'=>Designation::where('name',$row['designation'])->value('id'),
                'work_schedule_id'=>$row['schedule']->id,
                'first_name'=>$row['name'],'phone'=>'01700000000','joining_date'=>now()->subMonths(6)->toDateString(),
                'employment_type'=>'full_time','status'=>'active',
            ]);
            SalaryHistory::firstOrCreate(['employee_id'=>$employee->id,'effective_from'=>$employee->joining_date->toDateString()],[
                'basic_salary'=>$row['salary'],'house_allowance'=>5000,'transport_allowance'=>2000,
                'other_allowance'=>0,'overtime_hourly_rate'=>250,'changed_by'=>$user->id,
            ]);
            $employees[$row['role']]=$employee;
        }
        $project=Project::firstOrCreate(['project_code'=>'DEMO-PROJ-001'],[
            'name'=>'Demo Green Tower','location'=>'Chattogram','status'=>'ongoing',
            'start_date'=>today()->subMonths(3)->toDateString(),
        ]);
        foreach ([['project_manager','Project Manager'],['employee','Site Engineer']] as [$key,$job]) {
            ProjectAssignment::firstOrCreate([
                'project_id'=>$project->id,'employee_id'=>$employees[$key]->id,
                'start_date'=>$project->start_date->toDateString(),
            ],['assignment_role'=>$job,'is_primary'=>true]);
        }
        Task::firstOrCreate(['title'=>'Inspect site foundation','project_id'=>$project->id],[
            'assigned_to'=>$employees['employee']->id,
            'assigned_by'=>$employees['project_manager']->user_id,
            'description'=>'Review foundation reinforcement and submit a safety-focused site report.',
            'priority'=>'high','status'=>'pending','due_date'=>today()->addDays(7)->toDateString(),
        ]);
        $this->command?->info('Local demo users, employees, salary records, project assignments and task seeded.');
    }
}
