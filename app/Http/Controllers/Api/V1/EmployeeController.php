<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        Access::require($request->user(), 'employees.view');
        return Access::scopeEmployees(
            Employee::query()->with('department:id,name','designation:id,name','workSchedule:id,name'),
            $request->user()
        )->when($request->filled('search'), fn ($q) => $q->where(fn ($inner) => $inner
            ->where('employee_code', 'like', '%'.$request->search.'%')
            ->orWhere('first_name','like','%'.$request->search.'%')
            ->orWhere('last_name','like','%'.$request->search.'%')))
            ->orderByDesc('id')->paginate(min(max((int)$request->input('per_page',20),1),100));
    }

    public function show(Request $request, Employee $employee)
    {
        abort_unless(Access::maySeeEmployee($request->user(), $employee), 403);
        return $employee->load('department','designation','workSchedule','projectAssignments.project');
    }

    private function rules(?Employee $employee = null, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';
        return [
            'employee_code' => [$required, 'string','max:25',Rule::unique('employees','employee_code')->ignore($employee?->id)],
            'department_id' => [$required,'exists:departments,id'],
            'designation_id' => [$required,'exists:designations,id'],
            'work_schedule_id' => [$required,'exists:work_schedules,id'],
            'reports_to_id' => ['nullable','exists:employees,id', Rule::notIn($employee ? [$employee->id] : [])],
            'first_name' => [$required,'string','max:80'],
            'last_name' => ['nullable','string','max:80'],
            'phone' => [$required,'string','max:30'],
            'email' => ['nullable','email','max:255',Rule::unique('employees','email')->ignore($employee?->id)],
            'address' => ['nullable','string','max:2000'],
            'joining_date' => [$required,'date_format:Y-m-d'],
            'employment_type' => [$required,Rule::in(['full_time','part_time','contract'])],
            'status' => ['sometimes',Rule::in(['active','on_leave','resigned','terminated'])],
            'photo' => ['sometimes','nullable','image','max:2048'],
        ];
    }

    public function store(Request $request)
    {
        Access::require($request->user(), 'employees.manage');
        $data = $request->validate($this->rules());
        unset($data['photo']);
        if ($request->hasFile('photo')) $data['photo_path'] = $request->file('photo')->store('employee-photos', 'public');
        return response()->json(Employee::create($data),201);
    }

    public function update(Request $request, Employee $employee)
    {
        Access::require($request->user(), 'employees.manage');
        $data = $request->validate($this->rules($employee, true));
        unset($data['photo']);
        if (array_key_exists('reports_to_id', $data) && $data['reports_to_id']) {
            $cursor = Employee::find($data['reports_to_id']);
            while ($cursor) {
                if ($cursor->id === $employee->id) {
                    throw ValidationException::withMessages(['reports_to_id' => 'Reporting hierarchy cannot form a cycle.']);
                }
                $cursor = $cursor->reports_to_id ? Employee::find($cursor->reports_to_id) : null;
            }
        }
        if ($request->hasFile('photo')) $data['photo_path'] = $request->file('photo')->store('employee-photos', 'public');
        $employee->update($data);
        if (in_array($employee->status,['resigned','terminated'],true) && $employee->user) {
            $employee->user->tokens()->delete();
        }
        return $employee->refresh();
    }

    public function destroy(Request $request, Employee $employee)
    {
        Access::require($request->user(), 'employees.manage');
        // Resignation is recorded, never hard-delete payroll/attendance history.
        $employee->update(['status'=>'resigned']);
        $employee->user?->tokens()->delete();
        return response()->json(['message'=>'Employee marked as resigned.']);
    }

    public function provisionAccount(Request $request, Employee $employee)
    {
        Access::require($request->user(), 'employees.manage');
        abort_if($employee->user_id, 422, 'Employee already has a login.');
        abort_unless($employee->status === 'active', 422, 'Only active employees can receive logins.');
        $data = $request->validate([
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:12','confirmed'],
        ]);
        return DB::transaction(function () use ($employee,$data) {
            $user = User::create([
                'name'=>trim($employee->first_name.' '.$employee->last_name),
                'email'=>$data['email'],
                'password'=>Hash::make($data['password']),
            ]);
            $user->assignRole('employee');
            $employee->update(['user_id'=>$user->id]);
            return response()->json(['message'=>'Employee account created.','user_id'=>$user->id],201);
        });
    }
}
