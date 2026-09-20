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

            /*
             * BASIC INFORMATION
             *
             * Examples:
             * OFFICE-5D
             * OFFICE-6D
             * NIGHT-5D
             */
            $table->string('code', 30)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
             * STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'idx_work_schedules_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};