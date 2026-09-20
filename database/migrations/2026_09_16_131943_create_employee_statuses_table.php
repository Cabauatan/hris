<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();

            /*
             * BASIC INFORMATION
             *
             * Examples:
             * ACTIVE
             * ON_LEAVE
             * SUSPENDED
             * RESIGNED
             * TERMINATED
             */
            $table->string('code', 30)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
             * EMPLOYMENT STATE
             *
             * Determines whether an employee with this
             * status is still considered employed.
             *
             * Examples:
             *
             * Active          = true
             * On Leave        = true
             * Suspended       = true
             * Resigned        = false
             * Terminated      = false
             * Retired         = false
             * End of Contract = false
             */
            $table->boolean('is_employed')
                ->default(true);

            /*
             * WORK STATUS
             *
             * Determines whether the employee normally
             * participates in active work processing.
             *
             * Examples:
             *
             * Active     = true
             * On Leave   = false
             * Suspended  = false
             * Resigned   = false
             */
            $table->boolean('is_working')
                ->default(true);

            /*
             * STATUS
             *
             * This refers to whether THIS status option
             * can still be selected by HR.
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * DISPLAY ORDER
             */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'idx_employee_statuses_active_sort'
            );

            $table->index(
                ['is_employed', 'is_working'],
                'idx_employee_statuses_employment_state'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_statuses');
    }
};