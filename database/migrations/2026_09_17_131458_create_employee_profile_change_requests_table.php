<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profile_change_requests', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE REQUESTING THE CHANGE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * TYPE OF INFORMATION BEING CHANGED
             *
             * Suggested values:
             *
             * personal
             * contact
             * address
             * emergency_contact
             * government_id
             * bank_account
             */
            $table->string('change_type', 50);

            /*
             * TARGET RECORD
             *
             * Optional reference to the record
             * being changed.
             *
             * Examples:
             *
             * employee_addresses
             * employee_emergency_contacts
             * employee_government_ids
             * employee_bank_accounts
             *
             * NULL target_id may mean a new record
             * is being requested.
             */
            $table->string('target_type', 50)
                ->nullable();

            $table->unsignedBigInteger('target_id')
                ->nullable();

            /*
             * REQUESTED CHANGES
             *
             * Store only the fields the employee
             * is requesting to change.
             *
             * Example:
             *
             * {
             *   "mobile_number": "09xxxxxxxxx",
             *   "personal_email": "..."
             * }
             */
            $table->json('requested_changes');

            /*
             * EMPLOYEE'S REASON
             */
            $table->text('reason')
                ->nullable();

            /*
             * SUPPORTING DOCUMENT
             *
             * Example:
             * proof of address
             * updated government document
             * bank document
             *
             * Store in private storage.
             */
            $table->string('document_path', 500)
                ->nullable();

            /*
             * STATUS
             *
             * Suggested values:
             *
             * pending
             * approved
             * rejected
             * cancelled
             */
            $table->string('status', 20)
                ->default('pending');

            /*
             * REVIEW INFORMATION
             */
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('reviewed_at')
                ->nullable();

            $table->text('review_remarks')
                ->nullable();

            /*
             * CANCELLATION
             */
            $table->timestamp('cancelled_at')
                ->nullable();

            $table->string('cancellation_reason', 255)
                ->nullable();

            /*
             * USER WHO CREATED THE REQUEST
             *
             * Usually the employee's linked user.
             * Keeping this explicit also allows
             * authorized HR-assisted creation.
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
                ['employee_id', 'status'],
                'idx_profile_changes_employee_status'
            );

            $table->index(
                ['status', 'created_at'],
                'idx_profile_changes_status_created'
            );

            $table->index(
                ['target_type', 'target_id'],
                'idx_profile_changes_target'
            );

            $table->index(
                ['change_type', 'status'],
                'idx_profile_changes_type_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profile_change_requests');
    }
};