<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * DOCUMENT TYPE
             *
             * Examples:
             * RESUME
             * EMPLOYMENT_CONTRACT
             * NBI_CLEARANCE
             * POLICE_CLEARANCE
             * MEDICAL_CERTIFICATE
             * BIRTH_CERTIFICATE
             * DIPLOMA
             * CERTIFICATE
             * GOVERNMENT_ID
             * OTHER
             */
            $table->string('document_type', 50);

            /*
             * DOCUMENT TITLE
             *
             * Examples:
             * Employment Contract 2026
             * NBI Clearance
             * College Diploma
             */
            $table->string('title', 150);

            /*
             * Optional document/reference number.
             */
            $table->string('document_number', 100)
                ->nullable();

            /*
             * FILE INFORMATION
             *
             * Store only the storage path/key.
             * Actual file stays in Laravel storage.
             */
            $table->string('file_path', 500);

            $table->string('original_filename', 255)
                ->nullable();

            $table->string('mime_type', 100)
                ->nullable();

            /*
             * Size in bytes.
             */
            $table->unsignedBigInteger('file_size')
                ->nullable();

            /*
             * DOCUMENT DATES
             */
            $table->date('issued_date')
                ->nullable();

            $table->date('expiry_date')
                ->nullable();

            /*
             * Used for documents such as:
             * NBI clearance
             * certifications
             * licenses
             */
            $table->boolean('has_expiry')
                ->default(false);

            /*
             * Optional notes.
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
             * INDEXES
             */
            $table->index(
                ['employee_id', 'document_type'],
                'idx_employee_documents_type'
            );

            $table->index(
                ['employee_id', 'is_active'],
                'idx_employee_documents_active'
            );

            $table->index(
                ['has_expiry', 'expiry_date'],
                'idx_employee_documents_expiry'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};