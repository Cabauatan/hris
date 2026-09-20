<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cto_settings', function (Blueprint $table) {
            $table->id();

            /*
             * ENABLE / DISABLE CTO FEATURE
             */
            $table->boolean('is_enabled')
                ->default(true);

            /*
             * OT MINUTES REQUIRED BEFORE AN EMPLOYEE
             * CAN EARN CTO.
             *
             * Example:
             * 60 = minimum 1 hour eligible OT
             */
            $table->unsignedInteger('minimum_eligible_minutes')
                ->default(60);

            /*
             * CTO EARNING MULTIPLIER
             *
             * Examples:
             *
             * 1.00:
             * 2 hours eligible OT = 2 hours CTO
             *
             * 1.50:
             * 2 hours eligible OT = 3 hours CTO
             *
             * Actual company policy determines this.
             */
            $table->decimal('earning_multiplier', 6, 2)
                ->default(1.00);

            /*
             * CTO REQUEST INCREMENT
             *
             * Example:
             * 30 = CTO requests must normally be
             * in 30-minute increments.
             */
            $table->unsignedInteger('request_increment_minutes')
                ->default(30);

            /*
             * MINIMUM CTO USAGE
             *
             * Example:
             * 60 = minimum 1 hour per CTO request.
             */
            $table->unsignedInteger('minimum_request_minutes')
                ->default(60);

            /*
             * OPTIONAL MAXIMUM BALANCE
             *
             * NULL = no configured maximum.
             */
            $table->unsignedInteger('maximum_balance_minutes')
                ->nullable();

            /*
             * CREDIT EXPIRATION
             *
             * NULL = CTO credits do not automatically
             * expire.
             *
             * Example:
             * 90 = earned CTO expires after 90 days.
             */
            $table->unsignedInteger('expiry_days')
                ->nullable();

            /*
             * ALLOW CTO ON HALF-DAY BASIS
             *
             * Useful for ESS request UI later.
             */
            $table->boolean('allow_half_day')
                ->default(true);

            /*
             * REQUIRE APPROVED OT BEFORE CTO
             * CREDIT CAN BE EARNED.
             */
            $table->boolean('require_approved_overtime')
                ->default(true);

            /*
             * OPTIONAL NOTES / POLICY DESCRIPTION
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cto_settings');
    }
};