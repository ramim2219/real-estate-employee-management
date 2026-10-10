<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->time('start_time');
            $table->time('end_time');
            // ISO weekday numbers: Monday=1, ..., Sunday=7; e.g. [1,2,3,4,5,6].
            $table->json('working_days');
            $table->unsignedSmallInteger('grace_period_minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
