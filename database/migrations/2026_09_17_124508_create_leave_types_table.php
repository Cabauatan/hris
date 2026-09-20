<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();

            /*
             * UNIQUE CODE
             *
             * Examples:
             * VL
             * SL
             * EL
             * ML
             * PL
             * UL
             */
            $table->string('code', 30)
                ->unique();

            /*
             * DISPLAY NAME
             *
             * Examples:
             * Vacation Leave
             * Sick Leave
             * Emergency Leave
             */
            $table->string('name', 100);

            $table->text('description')
                ->nullable();

            /*
             * PAID / UNPAID
             *
             * true  = paid leave
             * false = unpaid leave
             */
            $table->boolean('is_paid')
                ->default(true);

            /*
             * DOES THIS LEAVE USE LEAVE CREDITS?
             *
             * Example:
             *
             * Vacation Leave → true
             * Sick Leave     → true
             * Unpaid Leave   → false
             *
             * Some statutory/company leaves may also
             * be managed without the normal credit pool.
             */
            $table->boolean('deduct_from_balance')
                ->default(true);

            /*
             * ALLOW HALF-DAY REQUEST
             */
            $table->boolean('allow_half_day')
                ->default(true);

            /*
             * REQUIRE EMPLOYEE TO PROVIDE A REASON
             */
            $table->boolean('requires_reason')
                ->default(true);

            /*
             * REQUIRE SUPPORTING DOCUMENT
             *
             * Example:
             * Some medical or statutory leave types.
             */
            $table->boolean('requires_document')
                ->default(false);

            /*
             * ACTIVE STATUS
             *
             * Disable old leave types instead of
             * deleting historical definitions.
             */
            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['is_active', 'sort_order'],
                'idx_leave_types_active_sort'
            );

            $table->index(
                ['is_paid', 'is_active'],
                'idx_leave_types_paid_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};