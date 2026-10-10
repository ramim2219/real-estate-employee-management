<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_role', 100)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            // Preserve assignment history: same employee can return to a project later.
            $table->unique(['project_id', 'employee_id', 'start_date'], 'project_staff_start_unique');
            $table->index(['employee_id','end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};
