<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DesignationController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeDocumentController;
use App\Http\Controllers\Api\V1\HolidayController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveTypeController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PayrollPaymentController;
use App\Http\Controllers\Api\V1\ProjectAssignmentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SalaryHistoryController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class,'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class,'me']);
        Route::post('logout', [AuthController::class,'logout']);
        Route::get('dashboard', DashboardController::class);
        Route::post('me/check-in',[AttendanceController::class,'checkIn']);
        Route::post('me/check-out',[AttendanceController::class,'checkOut']);

        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('designations', DesignationController::class);
        Route::apiResource('work-schedules', WorkScheduleController::class)->parameters(['work-schedules'=>'workSchedule']);
        Route::apiResource('projects',ProjectController::class);
        Route::apiResource('project-assignments',ProjectAssignmentController::class)->parameters(['project-assignments'=>'projectAssignment']);
        Route::apiResource('holidays',HolidayController::class);
        Route::apiResource('leave-types',LeaveTypeController::class)->parameters(['leave-types'=>'leaveType']);
        Route::apiResource('employees',EmployeeController::class);
        Route::post('employees/{employee}/account',[EmployeeController::class,'provisionAccount']);
        Route::apiResource('attendances',AttendanceController::class)->only(['index','show','store','update','destroy']);
        Route::post('attendances/{attendance}/review',[AttendanceController::class,'review']);
        Route::apiResource('leave-requests',LeaveRequestController::class)->parameters(['leave-requests'=>'leaveRequest'])->only(['index','show','store']);
        Route::post('leave-requests/{leaveRequest}/review',[LeaveRequestController::class,'review']);
        Route::post('leave-requests/{leaveRequest}/cancel',[LeaveRequestController::class,'cancel']);
        Route::apiResource('tasks',TaskController::class);
        Route::post('tasks/{task}/submit',[TaskController::class,'submit']);
        Route::post('tasks/{task}/review',[TaskController::class,'review']);
        Route::apiResource('salary-histories',SalaryHistoryController::class)->parameters(['salary-histories'=>'salaryHistory'])->only(['index','show','store']);
        Route::post('payrolls/generate',[PayrollController::class,'generate']);
        Route::apiResource('payrolls',PayrollController::class)->only(['index','show']);
        Route::post('payrolls/{payroll}/approve',[PayrollController::class,'approve']);
        Route::apiResource('payroll-payments',PayrollPaymentController::class)->parameters(['payroll-payments'=>'payrollPayment'])->only(['index','show','store']);
        Route::apiResource('employee-documents',EmployeeDocumentController::class)->parameters(['employee-documents'=>'employeeDocument'])->only(['index','show','store']);
        Route::get('employee-documents/{employeeDocument}/download',[EmployeeDocumentController::class,'download']);
    });
});
