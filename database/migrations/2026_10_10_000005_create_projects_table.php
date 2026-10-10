<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 30)->unique();
            $table->string('name', 180);
            $table->string('location', 255);
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planned','ongoing','on_hold','completed','cancelled'])->default('planned');
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
