<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();

            /*
             * ORIGINAL APPROVER
             *
             * User whose approval authority
             * is being delegated.
             */
            $table->foreignId('delegator_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * TEMPORARY / SUBSTITUTE APPROVER
             */
            $table->foreignId('delegate_user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * REQUEST TYPE
             *
             * NULL means delegation applies
             * to all supported request types.
             *
             * Suggested values:
             *
             * leave
             * overtime
             * attendance_correction
             * cto
             */
            $table->string('request_type', 50)
                ->nullable();

            /*
             * EFFECTIVITY PERIOD
             */
            $table->dateTime('effective_from');

            $table->dateTime('effective_to');

            /*
             * REASON
             *
             * Examples:
             *
             * On leave
             * Business trip
             * Temporary assignment
             */
            $table->string('reason', 255)
                ->nullable();

            /*
             * ACTIVE FLAG
             *
             * Allows HR/Admin to disable a
             * delegation before its end date.
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * WHO CREATED THE DELEGATION
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
                [
                    'delegator_user_id',
                    'is_active',
                    'effective_from',
                    'effective_to'
                ],
                'idx_approval_delegations_delegator'
            );

            $table->index(
                [
                    'delegate_user_id',
                    'is_active',
                    'effective_from',
                    'effective_to'
                ],
                'idx_approval_delegations_delegate'
            );

            $table->index(
                ['request_type', 'is_active'],
                'idx_approval_delegations_type_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_delegations');
    }
};