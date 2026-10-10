<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->restrictOnDelete();
            $table->decimal('amount',12,2);
            $table->enum('payment_method',['cash','bank_transfer','mobile_banking']);
            $table->string('transaction_reference',150)->nullable();
            $table->date('payment_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['payroll_id','payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');
    }
};
