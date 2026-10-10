<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            // Null = office / HQ attendance, not a construction site.
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->enum('status', ['present','late','absent','half_day','on_leave'])->default('present');
            $table->enum('approval_status', ['pending','approved','rejected'])->default('pending');
            $table->unsignedInteger('approved_overtime_minutes')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            // MVP rule: one attendance row per employee per calendar day.
            $table->unique(['employee_id','date']);
            $table->index(['date','approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
