<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_cto_balances', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             *
             * One CTO balance record per employee.
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * TOTAL CTO EARNED
             *
             * Example:
             * 960 minutes = 16 hours
             */
            $table->unsignedBigInteger('earned_minutes')
                ->default(0);

            /*
             * TOTAL CTO USED
             *
             * Approved CTO requests that have
             * consumed credits.
             */
            $table->unsignedBigInteger('used_minutes')
                ->default(0);

            /*
             * TOTAL EXPIRED CTO
             *
             * Credits that expired before use.
             */
            $table->unsignedBigInteger('expired_minutes')
                ->default(0);

            /*
             * MANUAL ADJUSTMENTS
             *
             * Signed because HR adjustments can
             * increase or decrease CTO balance.
             *
             * Examples:
             * +120
             * -60
             */
            $table->bigInteger('adjustment_minutes')
                ->default(0);

            /*
             * CURRENT AVAILABLE CTO BALANCE
             *
             * Stored for fast ESS/dashboard access.
             */
            $table->unsignedBigInteger('balance_minutes')
                ->default(0);

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * Only one aggregate CTO balance
             * per employee.
             */
            $table->unique(
                'employee_id',
                'uq_employee_cto_balance'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_cto_balances');
    }
};