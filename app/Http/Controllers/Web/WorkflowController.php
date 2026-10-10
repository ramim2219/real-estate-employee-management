<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeDocumentController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WorkflowController extends Controller
{
    public function handle(Request $request, string $action, ?int $id = null): RedirectResponse
    {
        // Explicit allowlist prevents arbitrary controller method invocation.
        $routes = [
            'check-in' => [AttendanceController::class, 'checkIn', null],
            'check-out' => [AttendanceController::class, 'checkOut', null],
            'attendance-review' => [AttendanceController::class, 'review', Attendance::class],
            'leave-review' => [LeaveRequestController::class, 'review', LeaveRequest::class],
            'leave-cancel' => [LeaveRequestController::class, 'cancel', LeaveRequest::class],
            'task-submit' => [TaskController::class, 'submit', Task::class],
            'task-review' => [TaskController::class, 'review', Task::class],
            'payroll-approve' => [PayrollController::class, 'approve', Payroll::class],
            'employee-account' => [EmployeeController::class, 'provisionAccount', Employee::class],
        ];
        abort_unless(isset($routes[$action]), 404);
        [$controller, $method, $model] = $routes[$action];
        abort_if($model && ! $id, 404);
        abort_if(! $model && $id, 404);
        try {
            if ($model) app($controller)->$method($request, $model::findOrFail($id));
            else app($controller)->$method($request);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 422) return back()->withErrors(['form' => $e->getMessage()])->withInput();
            throw $e;
        }
        return back()->with('success', 'Action completed successfully.');
    }

    public function download(Request $request, int $id)
    {
        // Existing API controller enforces owner/HR checks on private documents.
        return app(EmployeeDocumentController::class)->download($request, EmployeeDocument::findOrFail($id));
    }
}
