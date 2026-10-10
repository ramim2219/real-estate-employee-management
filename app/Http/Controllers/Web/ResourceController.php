<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\WorkSchedule;
use App\Support\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResourceController extends Controller
{
    private function definition(string $resource): array
    {
        // Whitelist from config; never resolve arbitrary class names from URL.
        return config("rems_ui.resources.$resource") ?? abort(404);
    }

    private function allowed(Request $request, array $definition, string $action): void
    {
        abort_unless(isset($definition[$action]) && $request->user()->can($definition[$action]), 403);
    }

    private function controller(array $definition): object
    {
        return App::make($definition['controller']);
    }

    private function record(array $definition, int $id): object
    {
        return ($definition['model'])::query()->findOrFail($id);
    }

    private function authorizedRecord(Request $request, array $definition, int $id): object
    {
        $model = $this->record($definition, $id);
        // API show methods enforce per-record ownership and project scope.
        $this->controller($definition)->show($request, $model);
        return $model;
    }

    public function index(Request $request, string $resource): View
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'view');
        $records = $this->controller($definition)->index($request);
        abort_unless($records instanceof \Illuminate\Contracts\Pagination\Paginator, 500, 'Invalid listing response.');

        return view('rems.resources.index', compact('resource', 'definition', 'records'));
    }

    public function show(Request $request, string $resource, int $id): View
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'view');
        $record = $this->authorizedRecord($request, $definition, $id);
        if ($resource === 'employees') $record->load('department', 'designation', 'projectAssignments.project');
        if ($resource === 'payrolls') $record->load('employee', 'payments');
        if ($resource === 'tasks') $record->load('assignee', 'project');
        if ($resource === 'leave-requests') $record->load('employee', 'leaveType');

        return view('rems.resources.show', compact('resource', 'definition', 'record'));
    }

    public function create(Request $request, string $resource): View
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'create');
        abort_unless(isset($definition['store_method']), 405);
        $editing = false;
        $record = null;
        $lookups = $this->lookups($request, $definition);
        return view('rems.resources.form', compact('resource', 'definition', 'editing', 'record', 'lookups'));
    }

    public function edit(Request $request, string $resource, int $id): View
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'edit');
        abort_unless(isset($definition['update_method']), 405);
        $editing = true;
        $record = $this->authorizedRecord($request, $definition, $id);
        $lookups = $this->lookups($request, $definition);
        return view('rems.resources.form', compact('resource', 'definition', 'editing', 'record', 'lookups'));
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'create');
        abort_unless(isset($definition['store_method']), 405);
        try {
            App::call([$this->controller($definition), $definition['store_method']], ['request' => $request]);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 422) return back()->withErrors(['form' => $e->getMessage()])->withInput();
            throw $e;
        }
        return redirect()->route('rems.resources.index', $resource)->with('success', $definition['singular'].' saved.');
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'edit');
        abort_unless(isset($definition['update_method']), 405);
        $record = $this->authorizedRecord($request, $definition, $id);
        try {
            $this->controller($definition)->{$definition['update_method']}($request, $record);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 422) return back()->withErrors(['form' => $e->getMessage()])->withInput();
            throw $e;
        }
        return redirect()->route('rems.resources.show', [$resource, $id])->with('success', $definition['singular'].' updated.');
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->allowed($request, $definition, 'edit');
        abort_unless(isset($definition['delete_method']), 405);
        $record = $this->authorizedRecord($request, $definition, $id);
        $this->controller($definition)->{$definition['delete_method']}($request, $record);
        return redirect()->route('rems.resources.index', $resource)->with('success', 'Record archived or deactivated.');
    }

    private function lookups(Request $request, array $definition): array
    {
        $lookups = [];
        foreach ($definition['fields'] as $field => $item) {
            if (($item['type'] ?? '') !== 'lookup') continue;
            $source = $item['source'] ?? $field;
            $user = $request->user();
            $lookups[$field] = match ($source) {
                'department' => Department::orderBy('name')->pluck('name', 'id')->all(),
                'designation' => Designation::orderBy('name')->pluck('name', 'id')->all(),
                'work_schedule' => WorkSchedule::orderBy('name')->pluck('name', 'id')->all(),
                'leave_type' => LeaveType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
                'project' => Project::query()->when(! Access::isHr($user) && ! $user->hasRole('accounts'),
                    fn ($q) => $q->whereIn('id', Access::managedProjectIds($user)))->orderBy('name')->pluck('name','id')->all(),
                'employee' => $this->employeeOptions($user),
                'payroll' => Payroll::query()->whereIn('status', ['approved', 'partially_paid'])->orderByDesc('id')->get()
                    ->mapWithKeys(fn ($row) => [$row->id => "Payroll #{$row->id} — {$row->year}/{$row->month}"])->all(),
                default => [],
            };
        }
        return $lookups;
    }

    private function employeeOptions(\App\Models\User $user): array
    {
        $query = Employee::query();
        if (! Access::isHr($user) && ! $user->hasRole('accounts')) $query = Access::scopeEmployees($query, $user);
        return $query->orderBy('first_name')->limit(500)->get()
            ->mapWithKeys(fn ($row) => [$row->id => $row->employee_code.' · '.trim($row->first_name.' '.$row->last_name)])->all();
    }
}
