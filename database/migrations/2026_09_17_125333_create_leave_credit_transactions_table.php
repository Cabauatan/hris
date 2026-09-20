<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_credit_transactions', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * LEAVE TYPE
             */
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * RELATED BALANCE RECORD
             */
            $table->foreignId('employee_leave_balance_id')
                ->constrained('employee_leave_balances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * RELATED LEAVE REQUEST
             *
             * Only populated when transaction came
             * from leave usage / reversal.
             */
            $table->foreignId('leave_request_id')
                ->nullable()
                ->constrained('leave_requests')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * TRANSACTION TYPE
             *
             * Suggested values:
             *
             * allocation
             * earned
             * adjustment
             * usage
             * reversal
             * carry_over
             */
            $table->string('transaction_type', 30);

            /*
             * TRANSACTION DATE
             */
            $table->date('transaction_date');

            /*
             * CREDIT MOVEMENT
             *
             * Positive = add credits
             * Negative = deduct credits
             *
             * Examples:
             *
             * +15.00 allocation
             *  +1.00 adjustment
             *  -2.50 leave usage
             *  +2.50 reversal
             */
            $table->decimal('days', 8, 2);

            /*
             * BALANCE BEFORE / AFTER
             *
             * Useful for history and reconciliation.
             */
            $table->decimal('balance_before', 8, 2);

            $table->decimal('balance_after', 8, 2);

            /*
             * DESCRIPTION
             *
             * Example:
             * 2026 annual VL allocation
             * Approved Leave Request #123
             * Manual adjustment
             */
            $table->string('description', 255)
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO CREATED THE TRANSACTION
             *
             * NULL allowed for automatic/system-generated
             * allocations and transactions.
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
                ['employee_id', 'leave_type_id', 'transaction_date'],
                'idx_leave_transactions_employee_type_date'
            );

            $table->index(
                ['employee_leave_balance_id', 'transaction_date'],
                'idx_leave_transactions_balance_date'
            );

            $table->index(
                ['transaction_type', 'transaction_date'],
                'idx_leave_transactions_type_date'
            );

            $table->index(
                'leave_request_id',
                'idx_leave_transactions_request'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_credit_transactions');
    }
};