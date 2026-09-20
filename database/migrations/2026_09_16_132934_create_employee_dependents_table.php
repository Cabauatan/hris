<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * NAME
             */
            $table->string('first_name', 100);

            $table->string('middle_name', 100)
                ->nullable();

            $table->string('last_name', 100);

            $table->string('suffix', 20)
                ->nullable();

            /*
             * RELATIONSHIP
             *
             * Examples:
             * spouse
             * child
             * parent
             * sibling
             * other
             */
            $table->string('relationship', 30);

            /*
             * BASIC INFORMATION
             */
            $table->date('birth_date')
                ->nullable();

            $table->string('sex', 20)
                ->nullable();

            /*
             * DEPENDENCY STATUS
             *
             * Indicates whether this person is currently
             * declared as a dependent of the employee.
             */
            $table->boolean('is_dependent')
                ->default(true);

            /*
             * BENEFICIARY FLAG
             *
             * Basic marker only.
             * Detailed benefit allocation can be added
             * later if the HRIS grows.
             */
            $table->boolean('is_beneficiary')
                ->default(false);

            /*
             * CONTACT INFORMATION
             *
             * Useful particularly for spouse/parent/
             * adult dependents.
             */
            $table->string('mobile_number', 50)
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            /*
             * RECORD STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'is_dependent'],
                'idx_employee_dependents_status'
            );

            $table->index(
                ['employee_id', 'relationship'],
                'idx_employee_dependents_relationship'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_dependents');
    }
};