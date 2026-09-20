<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * ADDRESS TYPE
             *
             * current
             * permanent
             */
            $table->string('address_type', 20);

            /*
             * ADDRESS
             */
            $table->string('address_line_1', 150);

            $table->string('address_line_2', 150)
                ->nullable();

            /*
             * For Philippine addresses:
             *
             * Barangay
             * City / Municipality
             * Province
             */
            $table->string('barangay', 100)
                ->nullable();

            $table->string('city_municipality', 100);

            $table->string('province', 100)
                ->nullable();

            $table->string('postal_code', 20)
                ->nullable();

            $table->string('country', 100)
                ->default('Philippines');

            /*
             * STATUS
             *
             * Useful if we retain an old address later.
             */
            $table->boolean('is_current')
                ->default(true);

            $table->timestamps();

            /*
             * An employee should normally have only
             * one record for each address type.
             */
            $table->unique(
                ['employee_id', 'address_type'],
                'uq_employee_address_type'
            );

            $table->index(
                ['employee_id', 'is_current'],
                'idx_employee_addresses_current'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};