<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('document_type', 100);
            $table->string('title', 200);
            // Save path only; file itself belongs on a PRIVATE storage disk.
            $table->string('file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id','document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
