<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class CompanySetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'HR'=>'Human Resources','ENG'=>'Engineering','ACC'=>'Accounts','ADM'=>'Administration',
        ] as $code=>$name) Department::firstOrCreate(['code'=>$code],['name'=>$name,'is_active'=>true]);
        foreach (['HR Officer','Civil Engineer','Site Engineer','Site Supervisor','Project Manager','Accountant','Office Assistant'] as $name) {
            Designation::firstOrCreate(['name'=>$name],['is_active'=>true]);
        }
        WorkSchedule::firstOrCreate(['name'=>'Office 9 AM - 5 PM'],[
            'start_time'=>'09:00:00','end_time'=>'17:00:00','working_days'=>[7,1,2,3,4],
            'grace_period_minutes'=>10,
        ]);
        WorkSchedule::firstOrCreate(['name'=>'Construction Site 8 AM - 4 PM'],[
            'start_time'=>'08:00:00','end_time'=>'16:00:00','working_days'=>[6,7,1,2,3,4],
            'grace_period_minutes'=>10,
        ]);
        // Values here are EXAMPLES: HR must replace these with verified company leave policy.
        foreach ([
            ['name'=>'Annual Leave','annual_allowance_days'=>12,'is_paid'=>true],
            ['name'=>'Sick Leave','annual_allowance_days'=>10,'is_paid'=>true],
            ['name'=>'Casual Leave','annual_allowance_days'=>10,'is_paid'=>true],
            ['name'=>'Unpaid Leave','annual_allowance_days'=>0,'is_paid'=>false],
        ] as $item) LeaveType::firstOrCreate(['name'=>$item['name']],$item+['is_active'=>true]);
    }
}
