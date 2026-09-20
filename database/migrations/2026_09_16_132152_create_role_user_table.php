<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            /*
             * Prevent duplicate assignment.
             */
            $table->unique(
                ['user_id', 'role_id'],
                'uq_role_user'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};