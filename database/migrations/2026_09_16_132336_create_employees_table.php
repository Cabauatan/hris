<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
             * =====================================================
             * EMPLOYEE NUMBER
             * =====================================================
             *
             * Examples:
             * EMP-0001
             * EMP-2026-001
             * 000123
             */
            $table->string('employee_number', 50)
                ->unique();

            /*
             * =====================================================
             * USER ACCOUNT
             * =====================================================
             *
             * Nullable because not every employee needs
             * an HRIS login account.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * =====================================================
             * NAME
             * =====================================================
             */

            $table->string('first_name', 100);

            $table->string('middle_name', 100)
                ->nullable();

            $table->string('last_name', 100);

            $table->string('suffix', 20)
                ->nullable();

            /*
             * Optional preferred/nickname.
             */
            $table->string('preferred_name', 100)
                ->nullable();

            /*
             * =====================================================
             * BASIC PERSONAL INFORMATION
             * =====================================================
             */

            $table->date('birth_date')
                ->nullable();

            /*
             * male
             * female
             *
             * Keep as string so we can adjust options
             * later without changing the schema.
             */
            $table->string('sex', 20)
                ->nullable();

            $table->string('civil_status', 30)
                ->nullable();

            /*
             * =====================================================
             * CONTACT
             * =====================================================
             */

            $table->string('personal_email', 150)
                ->nullable();

            $table->string('company_email', 150)
                ->nullable();

            $table->string('mobile_number', 50)
                ->nullable();

            /*
             * =====================================================
             * CURRENT EMPLOYMENT
             * =====================================================
             */

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('position_id')
                ->nullable()
                ->constrained('positions')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('employment_type_id')
                ->nullable()
                ->constrained('employment_types')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('employee_status_id')
                ->nullable()
                ->constrained('employee_statuses')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * =====================================================
             * SUPERVISOR
             * =====================================================
             *
             * Self-referencing employee relationship.
             *
             * Example:
             *
             * Employee → Supervisor
             */
            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * =====================================================
             * EMPLOYMENT DATES
             * =====================================================
             */

            $table->date('hire_date');

            /*
             * Useful for probationary employees.
             */
            $table->date('regularization_date')
                ->nullable();

            /*
             * Useful for contractual/project employees.
             */
            $table->date('contract_end_date')
                ->nullable();

            /*
             * Actual separation date.
             *
             * Example:
             * resignation / termination / retirement.
             */
            $table->date('separation_date')
                ->nullable();

            /*
             * =====================================================
             * PROFILE
             * =====================================================
             */

            $table->string('photo_path', 255)
                ->nullable();

            /*
             * =====================================================
             * RECORD STATUS
             * =====================================================
             *
             * This is NOT the employment status.
             *
             * employee_status_id tells us whether the
             * employee is Active, Resigned, etc.
             *
             * is_active tells us whether this employee
             * record is enabled in the HRIS.
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * =====================================================
             * INDEXES
             * =====================================================
             */

            $table->index(
                ['department_id', 'employee_status_id'],
                'idx_employees_department_status'
            );

            $table->index(
                ['position_id', 'employee_status_id'],
                'idx_employees_position_status'
            );

            $table->index(
                ['employment_type_id', 'employee_status_id'],
                'idx_employees_type_status'
            );

            $table->index(
                ['last_name', 'first_name'],
                'idx_employees_name'
            );

            $table->index(
                'hire_date',
                'idx_employees_hire_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};