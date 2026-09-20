<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_government_ids', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * GOVERNMENT ID TYPE
             *
             * Recommended codes:
             *
             * SSS
             * PHILHEALTH
             * PAGIBIG
             * TIN
             *
             * Other IDs may be added later.
             */
            $table->string('id_type', 30);

            /*
             * GOVERNMENT NUMBER
             *
             * Store as STRING, not integer.
             *
             * Government numbers may contain:
             * - leading zeroes
             * - hyphens
             * - other formatting characters
             */
            $table->string('id_number', 100);

            /*
             * Optional date information.
             *
             * Useful for IDs/documents that may have
             * issue or expiration dates.
             */
            $table->date('issued_date')
                ->nullable();

            $table->date('expiry_date')
                ->nullable();

            /*
             * Optional supporting document.
             *
             * Example:
             * employees/government-ids/sss-123.pdf
             *
             * Path only. Do not store the actual
             * file binary in this table.
             */
            $table->string('document_path', 255)
                ->nullable();

            /*
             * Optional remarks.
             */
            $table->text('remarks')
                ->nullable();

            /*
             * STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * One employee should normally have only
             * one active/master number per government
             * ID type in V1.
             */
            $table->unique(
                ['employee_id', 'id_type'],
                'uq_employee_government_id_type'
            );

            $table->index(
                ['id_type', 'id_number'],
                'idx_government_ids_type_number'
            );

            $table->index(
                ['employee_id', 'is_active'],
                'idx_government_ids_employee_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_government_ids');
    }
};