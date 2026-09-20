<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            /*
             * Machine-readable permission.
             *
             * Examples:
             *
             * employees.view
             * employees.create
             * employees.update
             * leave.request
             * leave.approve
             * payroll.process
             * payslips.view_own
             */
            $table->string('code', 100)->unique();

            $table->string('name', 150);

            /*
             * Used for grouping permissions in
             * the admin UI.
             *
             * Examples:
             * employees
             * attendance
             * leave
             * payroll
             */
            $table->string('module', 50);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['module', 'is_active'],
                'idx_permissions_module_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};