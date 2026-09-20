<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * CONTACT NAME
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
             * Spouse
             * Parent
             * Sibling
             * Child
             * Relative
             * Friend
             * Guardian
             */
            $table->string('relationship', 50);

            /*
             * CONTACT NUMBERS
             */
            $table->string('mobile_number', 50);

            $table->string('alternate_number', 50)
                ->nullable();

            /*
             * EMAIL
             */
            $table->string('email', 150)
                ->nullable();

            /*
             * ADDRESS
             *
             * Optional because emergency contact
             * information may only contain a phone
             * number.
             */
            $table->string('address_line_1', 150)
                ->nullable();

            $table->string('address_line_2', 150)
                ->nullable();

            $table->string('barangay', 100)
                ->nullable();

            $table->string('city_municipality', 100)
                ->nullable();

            $table->string('province', 100)
                ->nullable();

            $table->string('postal_code', 20)
                ->nullable();

            $table->string('country', 100)
                ->default('Philippines');

            /*
             * PRIMARY CONTACT
             *
             * The application will ensure that normally
             * only one primary emergency contact exists
             * per employee.
             */
            $table->boolean('is_primary')
                ->default(false);

            /*
             * STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'is_active'],
                'idx_emergency_contacts_employee_active'
            );

            $table->index(
                ['employee_id', 'is_primary'],
                'idx_emergency_contacts_employee_primary'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_emergency_contacts');
    }
};