<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 25)->unique();
            // Not all staff need a login account.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('designation_id')->constrained('designations')->restrictOnDelete();
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->restrictOnDelete();
            // Self-reference: who supervises this employee?
            $table->foreignId('reports_to_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->date('joining_date');
            $table->enum('employment_type', ['full_time','part_time','contract'])->default('full_time');
            $table->enum('status', ['active','on_leave','resigned','terminated'])->default('active');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
