<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();

            /*
             * ANNOUNCEMENT
             */
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * EMPLOYEE
             *
             * We track the HR employee record,
             * not only the authentication user.
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * FIRST READ TIME
             */
            $table->timestamp('read_at');

            $table->timestamps();

            /*
             * One read record per employee
             * per announcement.
             */
            $table->unique(
                ['announcement_id', 'employee_id'],
                'uq_announcement_read_employee'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'read_at'],
                'idx_announcement_reads_employee_date'
            );

            $table->index(
                ['announcement_id', 'read_at'],
                'idx_announcement_reads_announcement_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};