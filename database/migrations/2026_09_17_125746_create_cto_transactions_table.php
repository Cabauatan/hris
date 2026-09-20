<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cto_transactions', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * EMPLOYEE CTO BALANCE
             */
            $table->foreignId('employee_cto_balance_id')
                ->constrained('employee_cto_balances')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * RELATED CTO EARNING
             *
             * Populated when the transaction is tied
             * to a specific earned CTO credit.
             */
            $table->foreignId('cto_earning_id')
                ->nullable()
                ->constrained('cto_earnings')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * RELATED CTO REQUEST
             *
             * Usually populated for CTO usage
             * or reversal.
             */
            $table->foreignId('cto_request_id')
                ->nullable()
                ->constrained('cto_requests')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * TRANSACTION TYPE
             *
             * Suggested values:
             *
             * earning
             * usage
             * adjustment
             * expiration
             * reversal
             */
            $table->string('transaction_type', 30);

            /*
             * TRANSACTION DATE
             */
            $table->date('transaction_date');

            /*
             * CTO MOVEMENT IN MINUTES
             *
             * Positive:
             * +120 earning
             * +60 adjustment
             * +120 reversal
             *
             * Negative:
             * -120 usage
             * -60 expiration
             * -30 adjustment
             */
            $table->bigInteger('minutes');

            /*
             * RUNNING BALANCE
             */
            $table->unsignedBigInteger('balance_before');

            $table->unsignedBigInteger('balance_after');

            /*
             * DESCRIPTION
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
             * NULL allowed for automatic/system
             * generated transactions.
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
                ['employee_id', 'transaction_date'],
                'idx_cto_transactions_employee_date'
            );

            $table->index(
                ['employee_cto_balance_id', 'transaction_date'],
                'idx_cto_transactions_balance_date'
            );

            $table->index(
                ['transaction_type', 'transaction_date'],
                'idx_cto_transactions_type_date'
            );

        

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cto_transactions');
    }
};