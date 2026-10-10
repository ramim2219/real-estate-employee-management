<?php

use App\Http\Controllers\Api\V1 as Api;
use App\Models as Models;

// UI metadata only. All data mutations are delegated to existing API controllers.
// Field array: label, type (text, number, date, datetime-local, time, textarea,
// select, lookup, file, checkbox, weekdays), source, required, options.
$required = fn (string $label, string $type = 'text') => ['label'=>$label, 'type'=>$type, 'required'=>true];
$optional = fn (string $label, string $type = 'text') => ['label'=>$label, 'type'=>$type];
$lookup = fn (string $label, string $source, bool $needed = true) => ['label'=>$label, 'type'=>'lookup', 'source'=>$source, 'required'=>$needed];
$select = fn (string $label, array $options, bool $needed = true) => ['label'=>$label, 'type'=>'select', 'options'=>$options, 'required'=>$needed];

$resources = [
    'departments' => [
        'title'=>'Departments','singular'=>'Department','icon'=>'diagram-3', 'model'=>Models\Department::class,'controller'=>Api\DepartmentController::class,
        'view'=>'departments.view','create'=>'departments.manage','edit'=>'departments.manage',
        'store_method'=>'store','update_method'=>'update','delete_method'=>'destroy',
        'columns'=>['name'=>'Name','code'=>'Code','is_active'=>'Active'],
        'fields'=>['name'=>$required('Department name'),'code'=>$required('Code'),'description'=>$optional('Description','textarea'),'is_active'=>$optional('Active','checkbox')],
    ],
    'designations' => [
        'title'=>'Designations','singular'=>'Designation','icon'=>'person-badge','model'=>Models\Designation::class,'controller'=>Api\DesignationController::class,
        'view'=>'designations.view','create'=>'designations.manage','edit'=>'designations.manage',
        'store_method'=>'store','update_method'=>'update','delete_method'=>'destroy',
        'columns'=>['name'=>'Title','is_active'=>'Active'],
        'fields'=>['name'=>$required('Designation title'),'description'=>$optional('Description','textarea'),'is_active'=>$optional('Active','checkbox')],
    ],
    'work-schedules' => [
        'title'=>'Work Schedules','singular'=>'Work Schedule','icon'=>'calendar-week','model'=>Models\WorkSchedule::class,'controller'=>Api\WorkScheduleController::class,
        'view'=>'work_schedules.view','create'=>'work_schedules.manage','edit'=>'work_schedules.manage',
        'store_method'=>'store','update_method'=>'update',
        'columns'=>['name'=>'Schedule','start_time'=>'Start','end_time'=>'End','grace_period_minutes'=>'Grace (min)'],
        'fields'=>['name'=>$required('Schedule name'),'start_time'=>$required('Start time','time'),'end_time'=>$required('End time','time'),'working_days'=>$required('Working days','weekdays'),'grace_period_minutes'=>['label'=>'Grace period (minutes)','type'=>'number','min'=>0,'max'=>120]],
    ],
    'employees' => [
        'title'=>'Employees','singular'=>'Employee','icon'=>'people','model'=>Models\Employee::class,'controller'=>Api\EmployeeController::class,
        'view'=>'employees.view','create'=>'employees.manage','edit'=>'employees.manage',
        'store_method'=>'store','update_method'=>'update','delete_method'=>'destroy',
        'columns'=>['employee_code'=>'Code','first_name'=>'First name','last_name'=>'Last name','department.name'=>'Department','designation.name'=>'Designation','status'=>'Status'],
        'fields'=>[
            'employee_code'=>$required('Employee code'), 'first_name'=>$required('First name'), 'last_name'=>$optional('Last name'),
            'department_id'=>$lookup('Department','department'), 'designation_id'=>$lookup('Designation','designation'),
            'work_schedule_id'=>$lookup('Work schedule','work_schedule'),'reports_to_id'=>$lookup('Reports to','employee',false),
            'phone'=>$required('Phone','tel'), 'email'=>$optional('Email','email'),'address'=>$optional('Address','textarea'),
            'joining_date'=>$required('Joining date','date'),
            'employment_type'=>$select('Employment type',['full_time'=>'Full time','part_time'=>'Part time','contract'=>'Contract']),
            'status'=>$select('Status',['active'=>'Active','on_leave'=>'On leave','resigned'=>'Resigned','terminated'=>'Terminated']),
            'photo'=>['label'=>'Employee photo','type'=>'file','accept'=>'image/png,image/jpeg'],
        ],
    ],
    'projects' => [
        'title'=>'Projects','singular'=>'Project','icon'=>'buildings','model'=>Models\Project::class,'controller'=>Api\ProjectController::class,
        'view'=>'projects.view','create'=>'projects.manage','edit'=>'projects.manage','store_method'=>'store','update_method'=>'update',
        'columns'=>['project_code'=>'Code','name'=>'Project name','location'=>'Location','status'=>'Status'],
        'fields'=>['project_code'=>$required('Project code'),'name'=>$required('Project name'),'location'=>$required('Location'), 'description'=>$optional('Description','textarea'),
          'start_date'=>$optional('Start date','date'),'end_date'=>$optional('End date','date'),
          'status'=>$select('Status',['planned'=>'Planned','ongoing'=>'Ongoing','on_hold'=>'On hold','completed'=>'Completed','cancelled'=>'Cancelled'])],
    ],
    'project-assignments' => [
        'title'=>'Site Assignments','singular'=>'Site Assignment','icon'=>'geo-alt','model'=>Models\ProjectAssignment::class,'controller'=>Api\ProjectAssignmentController::class,
        'view'=>'assignments.view','create'=>'assignments.manage','edit'=>'assignments.manage','store_method'=>'store','update_method'=>'update',
        'columns'=>['project.name'=>'Project','employee.first_name'=>'Employee','assignment_role'=>'Site role','start_date'=>'Start','end_date'=>'End'],
        'fields'=>['project_id'=>array_merge($lookup('Project','project'),['readonly_on_edit'=>true]),
          'employee_id'=>array_merge($lookup('Employee','employee'),['readonly_on_edit'=>true]),
          'assignment_role'=>$optional('Site role'),'start_date'=>array_merge($required('Start date','date'),['readonly_on_edit'=>true]),
          'end_date'=>$optional('End date','date'),'is_primary'=>$optional('Primary assignment','checkbox')],
    ],
    'attendances' => [
        'title'=>'Attendance','singular'=>'Attendance Record','icon'=>'calendar-check','model'=>Models\Attendance::class,'controller'=>Api\AttendanceController::class,
        'view'=>'attendances.view','create'=>'attendances.manage','edit'=>'attendances.manage','store_method'=>'store','update_method'=>'update',
        'columns'=>['employee.first_name'=>'Employee','date'=>'Date','status'=>'Status','approval_status'=>'Approval','check_in'=>'Check in','check_out'=>'Check out'],
        'fields'=>['employee_id'=>array_merge($lookup('Employee','employee'),['readonly_on_edit'=>true]),
          'project_id'=>array_merge($lookup('Project site','project',false),['readonly_on_edit'=>true]),
          'date'=>array_merge($required('Attendance date','date'),['readonly_on_edit'=>true]),
          'check_in'=>$optional('Check in','datetime-local'),'check_out'=>$optional('Check out','datetime-local'),
          'status'=>$select('Status',['present'=>'Present','late'=>'Late','absent'=>'Absent','half_day'=>'Half day','on_leave'=>'On leave']),
          'remarks'=>$optional('Remarks','textarea')],
    ],
    'holidays' => [
        'title'=>'Holidays','singular'=>'Holiday','icon'=>'calendar-event','model'=>Models\Holiday::class,'controller'=>Api\HolidayController::class,
        'view'=>'holidays.view','create'=>'holidays.manage','edit'=>'holidays.manage','store_method'=>'store','update_method'=>'update',
        'columns'=>['name'=>'Holiday','date'=>'Date','is_paid'=>'Paid'],
        'fields'=>['name'=>$required('Holiday name'),'date'=>$required('Date','date'),'is_paid'=>$optional('Paid holiday','checkbox')],
    ],
    'leave-types' => [
        'title'=>'Leave Types','singular'=>'Leave Type','icon'=>'calendar-heart','model'=>Models\LeaveType::class,'controller'=>Api\LeaveTypeController::class,
        'view'=>'leave_types.view','create'=>'leave_types.manage','edit'=>'leave_types.manage','store_method'=>'store','update_method'=>'update','delete_method'=>'destroy',
        'columns'=>['name'=>'Type','annual_allowance_days'=>'Annual days','is_paid'=>'Paid','is_active'=>'Active'],
        'fields'=>['name'=>$required('Leave type'),'annual_allowance_days'=>['label'=>'Annual days','type'=>'number','min'=>0,'max'=>366,'required'=>true],
          'is_paid'=>$optional('Paid leave','checkbox'),'is_active'=>$optional('Active','checkbox')],
    ],
    'leave-requests' => [
        'title'=>'Leave Requests','singular'=>'Leave Request','icon'=>'calendar2-week','model'=>Models\LeaveRequest::class,'controller'=>Api\LeaveRequestController::class,
        'view'=>'leaves.view','create'=>'leaves.create','store_method'=>'store',
        'columns'=>['employee.first_name'=>'Employee','leaveType.name'=>'Leave type','start_date'=>'From','end_date'=>'To','total_days'=>'Days','status'=>'Status'],
        'fields'=>['employee_id'=>array_merge($lookup('Employee','employee',false),['hr_only'=>true]),
          'leave_type_id'=>$lookup('Leave type','leave_type'),'start_date'=>$required('First day','date'),
          'end_date'=>$required('Last day','date'),'reason'=>$required('Reason','textarea')],
    ],
    'tasks' => [
        'title'=>'Tasks','singular'=>'Task','icon'=>'list-task','model'=>Models\Task::class,'controller'=>Api\TaskController::class,
        'view'=>'tasks.view','create'=>'tasks.manage','edit'=>'tasks.manage','store_method'=>'store','update_method'=>'update',
        'columns'=>['title'=>'Task','assignee.first_name'=>'Assignee','project.name'=>'Project','priority'=>'Priority','status'=>'Status','due_date'=>'Due date'],
        'fields'=>['title'=>$required('Task title'),'description'=>$required('Description','textarea'),
          'assigned_to'=>array_merge($lookup('Assignee','employee'),['readonly_on_edit'=>true]),
          'project_id'=>array_merge($lookup('Project','project',false),['readonly_on_edit'=>true]),
          'priority'=>$select('Priority',['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent']),
          'due_date'=>$optional('Due date','date')],
    ],
    'salary-histories' => [
        'title'=>'Salary Structure','singular'=>'Salary Revision','icon'=>'cash-coin','model'=>Models\SalaryHistory::class,'controller'=>Api\SalaryHistoryController::class,
        'view'=>'salaries.view','create'=>'salaries.manage','store_method'=>'store',
        'columns'=>['employee_id'=>'Employee ID','basic_salary'=>'Basic','house_allowance'=>'House','transport_allowance'=>'Transport','effective_from'=>'Effective from'],
        'fields'=>['employee_id'=>$lookup('Employee','employee'),
          'basic_salary'=>['label'=>'Basic salary','type'=>'number','step'=>'0.01','min'=>0,'required'=>true],
          'house_allowance'=>['label'=>'House allowance','type'=>'number','step'=>'0.01','min'=>0],
          'transport_allowance'=>['label'=>'Transport allowance','type'=>'number','step'=>'0.01','min'=>0],
          'other_allowance'=>['label'=>'Other allowance','type'=>'number','step'=>'0.01','min'=>0],
          'overtime_hourly_rate'=>['label'=>'Overtime hourly rate','type'=>'number','step'=>'0.01','min'=>0],
          'effective_from'=>$required('Effective date','date')],
    ],
    'payrolls' => [
        'title'=>'Payroll','singular'=>'Payroll','icon'=>'wallet2','model'=>Models\Payroll::class,'controller'=>Api\PayrollController::class,
        'view'=>'payrolls.view','create'=>'payrolls.manage','store_method'=>'generate',
        'columns'=>['employee.employee_code'=>'Employee code','year'=>'Year','month'=>'Month','net_salary'=>'Net salary','status'=>'Status'],
        'fields'=>['employee_id'=>$lookup('Employee','employee'),'year'=>['label'=>'Year','type'=>'number','min'=>2020,'max'=>2100,'required'=>true],
          'month'=>['label'=>'Month (1–12)','type'=>'number','min'=>1,'max'=>12,'required'=>true]],
    ],
    'payroll-payments' => [
        'title'=>'Salary Payments','singular'=>'Payment','icon'=>'credit-card','model'=>Models\PayrollPayment::class,'controller'=>Api\PayrollPaymentController::class,
        'view'=>'payments.view','create'=>'payments.manage','store_method'=>'store',
        'columns'=>['payroll_id'=>'Payroll ID','amount'=>'Amount','payment_method'=>'Method','payment_date'=>'Date'],
        'fields'=>['payroll_id'=>$lookup('Approved payroll','payroll'),
          'amount'=>['label'=>'Amount','type'=>'number','min'=>0.01,'step'=>'0.01','required'=>true],
          'payment_method'=>$select('Payment method',['cash'=>'Cash','bank_transfer'=>'Bank transfer','mobile_banking'=>'Mobile banking']),
          'transaction_reference'=>$optional('Transaction reference'),'payment_date'=>$required('Payment date','date')],
    ],
    'employee-documents' => [
        'title'=>'Employee Documents','singular'=>'Document','icon'=>'file-earmark-lock','model'=>Models\EmployeeDocument::class,'controller'=>Api\EmployeeDocumentController::class,
        'view'=>'documents.view','create'=>'documents.manage','store_method'=>'store',
        'columns'=>['employee_id'=>'Employee ID','title'=>'Document','document_type'=>'Type','created_at'=>'Uploaded'],
        'fields'=>['employee_id'=>$lookup('Employee','employee'), 'title'=>$required('Document title'),
          'document_type'=>$required('Document type'),'file'=>['label'=>'Choose private document','type'=>'file','required'=>true,'accept'=>'.pdf,.jpg,.jpeg,.png']],
    ],
];

return [
    'app_name' => 'EstateHR',
    'resources' => $resources,
    'groups' => [
        'People & Organization' => ['employees','departments','designations','work-schedules'],
        'Field Operations' => ['projects','project-assignments','attendances','tasks'],
        'Time Off' => ['leave-requests','leave-types','holidays'],
        'Finance' => ['salary-histories','payrolls','payroll-payments'],
        'Company Records' => ['employee-documents'],
    ],
];
