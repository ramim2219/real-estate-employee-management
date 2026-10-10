<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\SalaryHistory;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\LeaveDaysCalculator;
use App\Services\PayrollCalculator;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Safe, repeatable showcase seed for an imaginary Chattogram real-estate company.
 * Run explicitly: php artisan db:seed --class=ProfessionalDemoSeeder
 * NEVER run against a production database.
 */
class ProfessionalDemoSeeder extends Seeder
{
    private const PREFIX = 'REMS-DEMO-';

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('ProfessionalDemoSeeder is restricted to APP_ENV=local.');
        }

        $password = config('rems.demo_password');
        if (! is_string($password) || strlen($password) < 12 || str_contains($password, 'SET_A_LONG')) {
            throw new RuntimeException('Set REMS_DEMO_PASSWORD to a unique password of 12+ characters in .env, then run php artisan config:clear.');
        }

        foreach (['super_admin', 'hr_admin', 'project_manager', 'accounts', 'employee'] as $role) {
            if (! DB::table('roles')->where('name', $role)->where('guard_name', 'web')->exists()) {
                throw new RuntimeException("Role '{$role}' is missing. Run RolePermissionSeeder first.");
            }
        }

        $admin = User::role('super_admin')->first();
        if (! $admin) {
            throw new RuntimeException('Create a super_admin using AdminUserSeeder first. Existing admin credentials will not be modified.');
        }

        $this->call(CompanySetupSeeder::class);

        DB::transaction(function () use ($admin, $password) {
            $departments = $this->departments();
            $designations = $this->designations();
            $schedules = $this->schedules();
            $holidays = $this->holidays();
            $staff = $this->employees($departments, $designations, $schedules, $admin, $password);
            $projects = $this->projects();
            $primaryProjects = $this->projectAssignments($staff, $projects, $admin);
            $this->leaveRequests($staff, $admin);
            $this->attendances($staff, $primaryProjects, $holidays, $admin);
            $this->tasks($staff, $projects, $admin);
            $this->payrolls($staff, $admin);
            $this->documents($staff, $admin);
        });

        $this->command?->info('Professional REMS showcase records are ready. Existing admin account was preserved.');
        $this->command?->warn('All REMS-DEMO data is fictional and is NOT for production use.');
    }

    private function departments(): array
    {
        $list = [
            'HR' => ['Human Resources', 'Employee onboarding, attendance and leave policies'],
            'ENG' => ['Engineering', 'Engineering, design and site supervision'],
            'ACC' => ['Accounts', 'Payroll preparation, approvals and payment records'],
            'ADM' => ['Administration', 'Administrative and office support'],
            'PROC' => ['Procurement', 'Construction materials procurement and vendor coordination'],
        ];
        foreach ($list as $code => [$name, $description]) {
            Department::firstOrCreate(['code' => $code], [
                'name' => $name, 'description' => $description, 'is_active' => true,
            ]);
        }
        return Department::whereIn('code', array_keys($list))->get()->keyBy('code')->all();
    }

    private function designations(): array
    {
        $titles = [
            'HR Officer', 'HR Assistant', 'Project Manager', 'Civil Engineer',
            'Site Engineer', 'Site Supervisor', 'Architect', 'Safety Officer',
            'Accountant', 'Quantity Surveyor', 'Procurement Officer',
            'Office Assistant', 'Electrical Engineer', 'Site Technician',
        ];
        foreach ($titles as $title) {
            Designation::firstOrCreate(['name' => $title], ['is_active' => true]);
        }
        return Designation::whereIn('name', $titles)->get()->keyBy('name')->all();
    }

    private function schedules(): array
    {
        // Already defined by CompanySetupSeeder; ISO days: Monday=1, Sunday=7.
        return [
            'office' => WorkSchedule::where('name', 'Office 9 AM - 5 PM')->firstOrFail(),
            'site' => WorkSchedule::where('name', 'Construction Site 8 AM - 4 PM')->firstOrFail(),
        ];
    }

    private function holidays(): array
    {
        // Imaginary COMPANY holidays, not claims about Bangladesh national holidays.
        $list = [
            '2026-09-14' => 'DEMO - Company Maintenance Day',
            '2026-10-19' => 'DEMO - Staff Development Day',
            '2026-12-15' => 'DEMO - Annual Planning Day',
        ];
        foreach ($list as $date => $name) {
            // Do not change an already established holiday or replace its policy.
            if (! Holiday::whereDate('date', $date)->exists()) {
                Holiday::create(['date' => $date, 'name' => $name, 'is_paid' => true]);
            }
        }
        return Holiday::whereIn('date', array_keys($list))->pluck('date')
            ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())->all();
    }

    private function employees(array $departments, array $designations, array $schedules, User $admin, string $password): array
    {
        // All names, addresses, salaries, telephone numbers and emails are synthetic.
        // The sample phone strings intentionally cannot be used to call real people.
        $rows = [
            // code, first, last, department, designation, schedule, basic, house, transport, joining, role, reports_to
            ['001','Nadia','Rahman','HR','HR Officer','office',42000,6000,3000,'2025-03-01','hr_admin',null],
            ['002','Imran','Hossain','ENG','Project Manager','site',72000,12000,4000,'2024-11-15','project_manager',null],
            ['003','Tahmid','Islam','ENG','Civil Engineer','site',50000,8000,3000,'2025-06-10',null,'002'],
            ['004','Farhana','Akter','ENG','Site Engineer','site',39000,6000,2000,'2026-02-01','employee','002'],
            ['005','Arif','Chowdhury','ENG','Site Supervisor','site',34000,5000,2000,'2025-02-10','employee','002'],
            ['006','Maliha','Sultana','ENG','Architect','office',54000,8000,3000,'2025-08-01',null,'002'],
            ['007','Rakib','Hasan','ENG','Safety Officer','site',32000,4000,2000,'2026-01-10',null,'002'],
            ['008','Nafisa','Ahmed','ACC','Accountant','office',46000,7000,3000,'2025-01-15','accounts',null],
            ['009','Sajid','Karim','ENG','Quantity Surveyor','site',43000,6000,2000,'2025-12-01',null,'002'],
            ['010','Sadia','Islam','PROC','Procurement Officer','office',37000,5000,2000,'2025-07-20',null,null],
            ['011','Rahat','Mahmud','ADM','Office Assistant','office',22000,2000,1000,'2026-02-12',null,null],
            ['012','Tania','Kabir','HR','HR Assistant','office',29000,4000,2000,'2026-04-15',null,'001'],
            ['013','Shuvo','Das','ENG','Site Engineer','site',36000,5000,2000,'2026-05-11',null,'002'],
            ['014','Aditi','Barua','ENG','Electrical Engineer','site',41000,6000,2000,'2026-01-11',null,'002'],
            ['015','Masud','Alam','ENG','Site Technician','site',26000,2000,1000,'2026-08-01',null,'005'],
        ];
        $emailByCode = [
            '001' => 'demo.nadia.hr@example.test',
            '002' => 'demo.imran.pm@example.test',
            '004' => 'demo.farhana.engineer@example.test',
            '005' => 'demo.arif.supervisor@example.test',
            '008' => 'demo.nafisa.accounts@example.test',
        ];
        $roleByCode = [
            '001' => 'hr_admin', '002' => 'project_manager', '004' => 'employee',
            '005' => 'employee', '008' => 'accounts',
        ];

        $staff = [];
        foreach ($rows as [$key, $first, $last, $dep, $designation, $shift, $basic, $house, $transport, $join, $role, $manager]) {
            $employeeCode = self::PREFIX . $key;
            $fullName = "$first $last";
            $email = $emailByCode[$key] ?? 'demo.staff'.$key.'@example.test';
            $user = null;
            if ($role !== null) {
                $user = User::where('email', $email)->first();
                if (! $user) {
                    $user = User::create([
                        'name' => $fullName, 'email' => $email,
                        'password' => Hash::make($password),
                    ]);
                    $user->assignRole($roleByCode[$key]);
                } elseif (! $user->hasRole($roleByCode[$key])) {
                    throw new RuntimeException("Demo login email {$email} is owned by another account. Refusing to change its role.");
                }
            }

            $existing = Employee::where('employee_code', $employeeCode)->first();
            if ($existing && $existing->user_id !== ($user?->id)) {
                throw new RuntimeException("Demo employee code {$employeeCode} is already used by a different account.");
            }
            $employee = $existing ?? Employee::create([
                'employee_code' => $employeeCode,
                'user_id' => $user?->id,
                'department_id' => $departments[$dep]->id,
                'designation_id' => $designations[$designation]->id,
                'work_schedule_id' => $schedules[$shift]->id,
                'first_name' => $first,
                'last_name' => $last,
                'phone' => sprintf('DEMO-PHONE-%04d', (int) $key),
                'email' => $email,
                'address' => 'Fictional address, Chattogram (demo only)',
                'joining_date' => $join,
                'employment_type' => $key === '015' ? 'contract' : 'full_time',
                'status' => 'active',
            ]);
            SalaryHistory::firstOrCreate([
                'employee_id' => $employee->id,
                'effective_from' => $join,
            ], [
                'basic_salary' => $basic,
                'house_allowance' => $house,
                'transport_allowance' => $transport,
                'other_allowance' => 0,
                'overtime_hourly_rate' => $shift === 'site' ? 280 : 200,
                'changed_by' => $admin->id,
            ]);
            $staff[$key] = $employee;
        }

        foreach ($rows as $row) {
            $key = $row[0];
            $managerKey = $row[11];
            if ($managerKey && $staff[$key]->reports_to_id === null) {
                $staff[$key]->update(['reports_to_id' => $staff[$managerKey]->id]);
            }
        }
        return $staff;
    }

    private function projects(): array
    {
        $rows = [
            ['001','Bayview Heights','Agrabad, Chattogram','ongoing','2025-08-01','2027-05-31','DEMO residential apartment tower; civil and finishing works'],
            ['002','Hillcrest Residency','Khulshi, Chattogram','ongoing','2026-01-15','2027-12-31','DEMO mid-rise residential construction and MEP works'],
            ['003','Seaside Corporate Tower','Patenga, Chattogram','planned','2027-02-01','2029-04-30','DEMO future commercial building for planning-stage tasks'],
            ['004','Greenfield Villas','Halishahar, Chattogram','completed','2024-02-01','2026-08-31','DEMO completed housing development; retains historical staff assignment'],
            ['005','Riverside Plaza','Chandgaon, Chattogram','on_hold','2025-10-01','2027-08-31','DEMO project paused pending design coordination'],
        ];
        $projects = [];
        foreach ($rows as [$key, $name, $location, $status, $start, $end, $description]) {
            $projects[$key] = Project::firstOrCreate(['project_code' => self::PREFIX . 'SITE-' . $key], [
                'name' => $name, 'location' => $location, 'status' => $status,
                'start_date' => $start, 'end_date' => $end, 'description' => $description,
            ]);
        }
        return $projects;
    }

    private function projectAssignments(array $staff, array $projects, User $admin): array
    {
        // project, employee, site role, assignment start, primary, assignment end
        $rows = [
            ['001','002','Project Manager','2025-08-01',true,null],
            ['002','002','Portfolio Manager','2026-01-15',false,null],
            ['001','003','Civil Engineer','2025-08-01',true,null],
            ['001','004','Site Engineer','2026-02-01',true,null],
            ['001','005','Site Supervisor','2025-08-01',true,null],
            ['001','007','Safety Officer','2026-01-10',true,null],
            ['001','009','Quantity Surveyor','2026-01-01',true,null],
            ['001','015','Site Technician','2026-08-01',true,null],
            ['002','013','Site Engineer','2026-05-11',true,null],
            ['002','014','Electrical Engineer','2026-02-01',true,null],
            ['002','006','Architect','2026-02-01',true,null],
            ['003','006','Design Consultant','2026-10-01',false,null],
            ['004','003','Completion Engineer','2025-09-01',false,'2026-08-31'],
            ['005','009','Quantity Surveyor','2025-12-10',false,'2026-05-31'],
        ];
        $primary = [];
        foreach ($rows as [$p, $e, $role, $start, $isPrimary, $end]) {
            ProjectAssignment::firstOrCreate([
                'project_id' => $projects[$p]->id,
                'employee_id' => $staff[$e]->id,
                'start_date' => $start,
            ], [
                'assigned_by' => $admin->id,
                'assignment_role' => $role,
                'end_date' => $end,
                'is_primary' => $isPrimary,
            ]);
            if ($isPrimary) $primary[$e] = $projects[$p]->id;
        }
        return $primary;
    }

    private function leaveRequests(array $staff, User $admin): void
    {
        $days = app(LeaveDaysCalculator::class);
        $types = LeaveType::whereIn('name', ['Annual Leave','Sick Leave','Casual Leave','Unpaid Leave'])->pluck('id', 'name');
        // Employee, type, start, end, status, reason
        $rows = [
            ['004','Sick Leave','2026-09-17','2026-09-17','approved','DEMO medical rest request'],
            ['011','Unpaid Leave','2026-09-22','2026-09-22','approved','DEMO personal leave without pay'],
            ['012','Casual Leave','2026-10-13','2026-10-14','pending','DEMO family commitment'],
            ['003','Annual Leave','2026-10-20','2026-10-21','pending','DEMO short personal trip'],
            ['007','Casual Leave','2026-09-23','2026-09-23','rejected','DEMO staffing cover unavailable'],
            ['008','Annual Leave','2026-09-09','2026-09-10','approved','DEMO planned annual leave'],
        ];
        foreach ($rows as [$emp, $type, $start, $end, $status, $reason]) {
            $employee = $staff[$emp];
            $count = $days->count($employee->loadMissing('workSchedule'), $start, $end);
            if ($count < 1) throw new RuntimeException("Sample leave {$start} is not a workday for {$emp}.");
            LeaveRequest::firstOrCreate([
                'employee_id' => $employee->id,
                'leave_type_id' => $types[$type],
                'start_date' => $start,
                'end_date' => $end,
            ], [
                'total_days' => $count,
                'reason' => $reason,
                'status' => $status,
                'approved_by' => in_array($status, ['approved','rejected'], true) ? $admin->id : null,
                'decided_at' => in_array($status, ['approved','rejected'], true) ? '2026-09-24 16:30:00' : null,
            ]);
        }
    }

    private function attendances(array $staff, array $primaryProjects, array $holidays, User $admin): void
    {
        // A complete previous payroll month and recent dates for attendance listing.
        $start = CarbonImmutable::parse('2026-09-01');
        $end = CarbonImmutable::parse('2026-10-10');
        $leaveByEmployee = LeaveRequest::where('status', 'approved')->get()
            ->groupBy('employee_id');
        foreach ($staff as $key => $employee) {
            $employee->loadMissing('workSchedule');
            $days = $employee->workSchedule->working_days;
            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $date = $day->toDateString();
                if (! in_array($day->dayOfWeekIso, $days, true) || in_array($date, $holidays, true)) continue;
                $isLeave = ($leaveByEmployee[$employee->id] ?? collect())->contains(
                    fn ($leave) => $date >= $leave->start_date->toDateString()
                        && $date <= $leave->end_date->toDateString()
                );
                $serial = (int) $key + (int) $day->format('d');
                $isAbsent = ! $isLeave && $serial % 29 === 0;
                $isLate = ! $isLeave && ! $isAbsent && $serial % 11 === 0;
                $status = $isLeave ? 'on_leave' : ($isAbsent ? 'absent' : ($isLate ? 'late' : 'present'));
                $begin = $employee->workSchedule->start_time; // HH:MM:SS string
                $finish = $employee->workSchedule->end_time;
                $in = $isLeave || $isAbsent ? null : "$date $begin";
                $out = $isLeave || $isAbsent ? null : "$date $finish";
                if ($isLate && $in !== null) {
                    $in = CarbonImmutable::parse($in)->addMinutes(18)->toDateTimeString();
                }
                $overtime = (! $isLeave && ! $isAbsent && $serial % 13 === 0) ? 60 : 0;
                Attendance::firstOrCreate([
                    'employee_id' => $employee->id,
                    'date' => $date,
                ], [
                    'project_id' => $primaryProjects[$key] ?? null,
                    'check_in' => $in,
                    'check_out' => $out,
                    'status' => $status,
                    'approval_status' => $date >= '2026-10-08' ? 'pending' : 'approved',
                    'approved_overtime_minutes' => $date >= '2026-10-08' ? 0 : $overtime,
                    'verified_by' => $date >= '2026-10-08' ? null : $admin->id,
                    'remarks' => $isLeave ? 'DEMO approved leave' : ($isAbsent ? 'DEMO attendance exception' : 'DEMO sample attendance'),
                ]);
            }
        }
    }

    private function tasks(array $staff, array $projects, User $admin): void
    {
        // All tasks are explicitly DEMO, with varied states for tables and badges.
        // project, employee, title, priority, due date, status, report
        $rows = [
            ['001','004','Foundation reinforcement inspection','high','2026-10-14','in_progress',null],
            ['001','005','Review labour safety briefing','medium','2026-10-12','submitted','Safety toolbox meeting completed and submitted for review.'],
            ['001','003','Prepare concrete quality checklist','high','2026-10-16','pending',null],
            ['001','007','Verify site PPE compliance','urgent','2026-10-09','revision_required','Site inspection report submitted.'],
            ['001','009','Update bill of quantities','medium','2026-10-19','in_progress',null],
            ['001','015','Check material storage labels','low','2026-10-08','approved','Material batches labelled.'],
            ['002','013','Review column shuttering','high','2026-10-15','in_progress',null],
            ['002','014','Inspect electrical conduit routes','medium','2026-10-21','pending',null],
            ['002','006','Update second-floor layout','medium','2026-10-11','submitted','Revised concept plan attached to site records.'],
            ['002','002','Weekly site review meeting','high','2026-10-18','pending',null],
            ['003','006','Draft preliminary space planning','medium','2026-10-25','pending',null],
            ['001','002','Approve site progress summary','urgent','2026-10-10','approved','Progress reviewed and circulated.'],
            [null,'001','Review onboarding checklist','low','2026-10-20','in_progress',null],
            [null,'012','Update employee contact directory','medium','2026-10-22','pending',null],
            [null,'008','Reconcile September payroll','high','2026-10-10','approved','Salary sheet reconciled.'],
            [null,'010','Prepare procurement comparison sheet','high','2026-10-17','in_progress',null],
            [null,'011','Complete office inventory register','low','2026-10-19','pending',null],
            [null,'008','Verify payment references','medium','2026-10-21','submitted','Payment reference list prepared for review.'],
        ];
        foreach ($rows as [$p, $e, $title, $priority, $due, $status, $report]) {
            $done = $status === 'approved';
            Task::firstOrCreate([
                'title' => 'DEMO - ' . $title,
                'assigned_to' => $staff[$e]->id,
                'project_id' => $p === null ? null : $projects[$p]->id,
            ], [
                'assigned_by' => $admin->id,
                'description' => 'Sample real-estate staff assignment for UI/workflow testing: ' . $title . '.',
                'priority' => $priority,
                'due_date' => $due,
                'status' => $status,
                'task_report' => $report,
                'review_feedback' => $status === 'revision_required' ? 'Please include a clear corrective-action summary.' : null,
                'reviewed_by' => in_array($status, ['approved','revision_required'], true) ? $admin->id : null,
                'completed_at' => $done ? '2026-10-09 16:00:00' : null,
            ]);
        }
    }

    private function payrolls(array $staff, User $admin): void
    {
        $calc = app(PayrollCalculator::class);
        $accountsUserId = $staff['008']->user_id;
        foreach ($staff as $key => $employee) {
            $values = $calc->calculate($employee->loadMissing('workSchedule'), 2026, 9);
            $statusId = ((int) $key - 1) % 5;
            $status = match ($statusId) {
                0 => 'draft', 1 => 'approved', 2 => 'paid', 3 => 'partially_paid', default => 'paid',
            };
            $payroll = Payroll::firstOrCreate([
                'employee_id' => $employee->id,
                'year' => 2026,
                'month' => 9,
            ], $values + [
                'status' => $status,
                'processed_by' => $accountsUserId,
                'approved_by' => $status === 'draft' ? null : $admin->id,
                'approved_at' => $status === 'draft' ? null : '2026-10-04 14:00:00',
            ]);
            // If payroll has been edited since seeding, never touch or re-pay it.
            if ($payroll->wasRecentlyCreated && in_array($status, ['paid','partially_paid'], true)) {
                $amount = Money::toPaisa($payroll->net_salary);
                $paid = $status === 'paid' ? $amount : intdiv($amount, 2);
                if ($paid <= 0) continue;
                PayrollPayment::create([
                    'payroll_id' => $payroll->id,
                    'amount' => Money::toDecimal($paid),
                    'payment_method' => 'bank_transfer',
                    'transaction_reference' => 'DEMO-PAY-202609-' . $key,
                    'payment_date' => '2026-10-07',
                    'recorded_by' => $accountsUserId,
                ]);
            }
        }
    }

    private function documents(array $staff, User $admin): void
    {
        $fixture = __DIR__ . '/fixtures/demo-employment-note.pdf';
        if (! is_file($fixture)) {
            throw new RuntimeException('Missing dummy PDF fixture: database/seeders/fixtures/demo-employment-note.pdf');
        }
        foreach (['001', '004', '008'] as $code) {
            $employee = $staff[$code];
            $path = 'employee-documents/' . $employee->id . '/DEMO-employment-note.pdf';
            if (! Storage::disk('local')->exists($path)) {
                if (! Storage::disk('local')->put($path, file_get_contents($fixture))) {
                    throw new RuntimeException('Could not write private demo document: ' . $path);
                }
            }
            EmployeeDocument::firstOrCreate([
                'employee_id' => $employee->id,
                'title' => 'DEMO - Sample Employment Note',
            ], [
                'document_type' => 'sample_employment_note',
                'file_path' => $path,
                'uploaded_by' => $admin->id,
            ]);
        }
    }
}
