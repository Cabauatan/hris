<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * BANK INFORMATION
             *
             * Examples:
             * BDO
             * BPI
             * Metrobank
             * LandBank
             * Security Bank
             */
            $table->string('bank_name', 100);

            /*
             * Optional branch information.
             */
            $table->string('branch_name', 100)
                ->nullable();

            /*
             * ACCOUNT HOLDER
             */
            $table->string('account_name', 150);

            /*
             * Account numbers are identifiers,
             * therefore STRING instead of integer.
             */
            $table->string('account_number', 100);

            /*
             * Examples:
             * savings
             * checking
             * payroll
             */
            $table->string('account_type', 30)
                ->nullable();

            /*
             * PRIMARY PAYROLL ACCOUNT
             *
             * This is the account normally used
             * when generating payroll bank data.
             */
            $table->boolean('is_primary')
                ->default(false);

            /*
             * STATUS
             *
             * Allows HR to replace an old account
             * without immediately deleting the record.
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * Optional remarks.
             *
             * Example:
             * Payroll account issued by company.
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * Prevent accidental duplicate account
             * numbers for the same employee.
             */
            $table->unique(
                ['employee_id', 'bank_name', 'account_number'],
                'uq_employee_bank_account'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'is_active'],
                'idx_employee_bank_accounts_active'
            );

            $table->index(
                ['employee_id', 'is_primary'],
                'idx_employee_bank_accounts_primary'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};