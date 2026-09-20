<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * PAY TYPE
             *
             * monthly
             * daily
             * hourly
             *
             * Small-company V1 muna.
             */
            $table->string('pay_type', 20)
                ->default('monthly');

            /*
             * BASIC RATE
             *
             * Examples:
             *
             * Monthly employee:
             * 25000.00
             *
             * Daily employee:
             * 650.00
             *
             * Hourly employee:
             * 100.00
             */
            $table->decimal('basic_rate', 12, 2);

            /*
             * EFFECTIVITY
             *
             * Salary/rate becomes effective on this date.
             */
            $table->date('effective_from');

            /*
             * NULL means this compensation record
             * currently has no specified end date.
             */
            $table->date('effective_to')
                ->nullable();

            /*
             * CURRENT RATE
             *
             * Makes it easy to retrieve the employee's
             * current compensation.
             *
             * Application layer will ensure normally
             * only one current record per employee.
             */
            $table->boolean('is_current')
                ->default(true);

            /*
             * REASON FOR CHANGE
             *
             * Examples:
             *
             * Initial salary
             * Annual increase
             * Promotion
             * Salary adjustment
             */
            $table->string('change_reason', 255)
                ->nullable();

            /*
             * OPTIONAL NOTES
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO RECORDED THE COMPENSATION
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'is_current'],
                'idx_employee_compensations_current'
            );

            $table->index(
                ['employee_id', 'effective_from'],
                'idx_employee_compensations_effective'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};