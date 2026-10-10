<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('employees')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->enum('priority',['low','medium','high','urgent'])->default('medium');
            $table->date('due_date')->nullable();
            $table->enum('status',['pending','in_progress','submitted','approved','revision_required'])->default('pending');
            $table->text('task_report')->nullable();
            $table->text('review_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to','status','due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
