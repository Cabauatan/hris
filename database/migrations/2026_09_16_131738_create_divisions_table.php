<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'idx_divisions_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};