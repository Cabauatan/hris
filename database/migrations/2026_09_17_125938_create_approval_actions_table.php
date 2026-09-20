<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();

            /*
             * REQUEST TYPE
             *
             * Suggested values:
             *
             * leave
             * overtime
             * attendance_correction
             * cto
             *
             * Application layer will map these
             * to their corresponding request tables.
             */
            $table->string('request_type', 50);

            /*
             * REQUEST ID
             *
             * ID from the corresponding request table.
             *
             * Example:
             *
             * request_type = leave
             * request_id   = 25
             *
             * means:
             * leave_requests.id = 25
             */
            $table->unsignedBigInteger('request_id');

            /*
             * EMPLOYEE WHO OWNS THE REQUEST
             *
             * Stored directly for easier approval
             * inbox/history queries.
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * ACTION
             *
             * Suggested values:
             *
             * submitted
             * approved
             * rejected
             * cancelled
             */
            $table->string('action', 30);

            /*
             * USER WHO PERFORMED THE ACTION
             *
             * Examples:
             *
             * Employee submits request
             * Supervisor approves
             * HR rejects
             */
            $table->foreignId('acted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * ACTION DATE/TIME
             */
            $table->timestamp('acted_at');

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['request_type', 'request_id'],
                'idx_approval_actions_request'
            );

            $table->index(
                ['employee_id', 'acted_at'],
                'idx_approval_actions_employee_date'
            );

            $table->index(
                ['acted_by_user_id', 'acted_at'],
                'idx_approval_actions_user_date'
            );

            $table->index(
                ['request_type', 'action', 'acted_at'],
                'idx_approval_actions_type_action_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};