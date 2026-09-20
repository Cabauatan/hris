<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();

            /*
             * BASIC INFORMATION
             *
             * Examples:
             * REG
             * PROB
             * CONT
             * PART
             * PROJ
             */
            $table->string('code', 20)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
             * CONTRACT BEHAVIOR
             *
             * Example:
             *
             * Regular       = false
             * Probationary  = false
             * Contractual   = true
             * Project-Based = true
             */
            $table->boolean('requires_end_date')
                ->default(false);

            /*
             * STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * DISPLAY ORDER
             */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            /*
             * INDEX
             */
            $table->index(
                ['is_active', 'sort_order'],
                'idx_employment_types_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};