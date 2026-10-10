<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            // Salary/allowances are snapshots; historical payslips must not change.
            $table->decimal('basic_salary',12,2);
            $table->decimal('allowances',12,2)->default(0);
            $table->decimal('overtime_pay',12,2)->default(0);
            $table->decimal('deductions',12,2)->default(0);
            $table->decimal('gross_salary',12,2)->default(0);
            $table->decimal('net_salary',12,2)->default(0);
            $table->enum('status',['draft','approved','partially_paid','paid'])->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id','year','month']);
            $table->index(['year','month','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
