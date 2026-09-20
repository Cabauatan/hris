<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();

            /*
             * BASIC INFORMATION
             *
             * Examples:
             * HR-MGR
             * DEV
             * ACC
             * CASHIER
             */
            $table->string('code', 30)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
             * OPTIONAL DEFAULT DEPARTMENT
             *
             * Example:
             *
             * Software Developer → IT
             * HR Assistant       → HR
             *
             * Nullable because some positions may
             * be usable across departments.
             */
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * STATUS
             *
             * Do not delete positions that already
             * have employment history.
             */
            $table->boolean('is_active')->default(true);

            /*
             * DISPLAY ORDER
             */
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['department_id', 'is_active'],
                'idx_positions_department_active'
            );

            $table->index(
                ['is_active', 'sort_order'],
                'idx_positions_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};