<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            /*
             * USER WHO PERFORMED THE ACTION
             *
             * NULL is allowed for system-generated
             * actions such as queue jobs or
             * scheduled processes.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * ACTION
             *
             * Suggested values:
             *
             * created
             * updated
             * deleted
             * approved
             * rejected
             * cancelled
             * finalized
             * published
             * revoked
             * login
             * logout
             */
            $table->string('action', 50);

            /*
             * MODULE
             *
             * Examples:
             *
             * employee
             * attendance
             * leave
             * cto
             * payroll
             * announcement
             * settings
             * security
             */
            $table->string('module', 50);

            /*
             * TARGET RECORD
             *
             * Examples:
             *
             * auditable_type = employee
             * auditable_id   = 125
             *
             * auditable_type = payroll_run
             * auditable_id   = 50
             *
             * No FK because the target may belong
             * to different tables.
             */
            $table->string('auditable_type', 100);

            $table->unsignedBigInteger('auditable_id')
                ->nullable();

            /*
             * HUMAN-READABLE DESCRIPTION
             *
             * Example:
             *
             * "Updated employee contact information."
             */
            $table->string('description', 500)
                ->nullable();

            /*
             * BEFORE / AFTER VALUES
             *
             * Store only relevant changed fields,
             * not necessarily the entire model.
             */
            $table->json('old_values')
                ->nullable();

            $table->json('new_values')
                ->nullable();

            /*
             * REQUEST CONTEXT
             *
             * Useful for security investigation.
             */
            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            /*
             * OPTIONAL REQUEST ID
             *
             * Useful for correlating multiple
             * audit entries created by one
             * HTTP request/process.
             */
            $table->string('request_id', 100)
                ->nullable();

            /*
             * AUDIT EVENT TIME
             *
             * Explicit event timestamp rather than
             * relying only on created_at.
             */
            $table->timestamp('occurred_at');

            /*
             * Audit logs should normally never be
             * updated, but timestamps are useful
             * for standard Laravel handling.
             */
            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['auditable_type', 'auditable_id', 'occurred_at'],
                'idx_audit_logs_target'
            );

            $table->index(
                ['user_id', 'occurred_at'],
                'idx_audit_logs_user_date'
            );

            $table->index(
                ['module', 'action', 'occurred_at'],
                'idx_audit_logs_module_action'
            );

            $table->index(
                ['request_id'],
                'idx_audit_logs_request'
            );

            $table->index(
                ['occurred_at'],
                'idx_audit_logs_occurred'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};