<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            /*
             * DIVISION
             *
             * Nullable so a department may exist directly
             * under the company if needed.
             */
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('code', 20)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['division_id', 'is_active'],
                'idx_departments_division_active'
            );

            $table->index(
                ['is_active', 'sort_order'],
                'idx_departments_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};