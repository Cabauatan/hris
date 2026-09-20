<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cto_earnings', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * SOURCE OVERTIME REQUEST
             *
             * Nullable because HR may later grant
             * CTO credits manually when necessary.
             */
            $table->foreignId('overtime_request_id')
                ->nullable()
                ->constrained('overtime_requests')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * DATE THE CTO WAS EARNED
             *
             * Usually related to the OT work date.
             */
            $table->date('earned_date');

            /*
             * OT MINUTES USED AS BASIS
             *
             * Example:
             * Approved/eligible OT = 120 minutes
             */
            $table->unsignedInteger('source_overtime_minutes')
                ->default(0);

            /*
             * MULTIPLIER USED AT THE TIME
             *
             * We store this so historical CTO earnings
             * remain understandable even if settings
             * change later.
             */
            $table->decimal('earning_multiplier', 6, 2)
                ->default(1.00);

            /*
             * CTO MINUTES EARNED
             *
             * Example:
             *
             * 120 OT minutes × 1.00
             * = 120 CTO minutes
             */
            $table->unsignedInteger('earned_minutes');

            /*
             * REMAINING MINUTES FROM THIS EARNING
             *
             * Important for expiration and usage.
             *
             * Example:
             *
             * Earned    240
             * Used      120
             * Remaining 120
             */
            $table->unsignedInteger('remaining_minutes');

            /*
             * EXPIRATION
             *
             * NULL means the credit does not expire.
             */
            $table->date('expires_on')
                ->nullable();

            /*
             * STATUS
             *
             * Suggested values:
             *
             * available
             * partially_used
             * consumed
             * expired
             * cancelled
             */
            $table->string('status', 30)
                ->default('available');

            /*
             * OPTIONAL DESCRIPTION / REASON
             *
             * Useful especially for manually
             * granted CTO credits.
             */
            $table->string('reason', 255)
                ->nullable();

            $table->text('remarks')
                ->nullable();

            /*
             * WHO CREATED / GRANTED THE CREDIT
             *
             * NULL allowed for system-generated
             * CTO earnings.
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
                'idx_cto_earnings_employee_status'
            );

            $table->index(
                ['employee_id', 'earned_date'],
                'idx_cto_earnings_employee_date'
            );

            $table->index(
                ['status', 'expires_on'],
                'idx_cto_earnings_status_expiry'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cto_earnings');
    }
};