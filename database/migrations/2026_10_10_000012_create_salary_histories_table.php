<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->decimal('basic_salary',12,2);
            $table->decimal('house_allowance',12,2)->default(0);
            $table->decimal('transport_allowance',12,2)->default(0);
            $table->decimal('other_allowance',12,2)->default(0);
            $table->decimal('overtime_hourly_rate',12,2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id','effective_from']);
            $table->index(['employee_id','effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_histories');
    }
};
