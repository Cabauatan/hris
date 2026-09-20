<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();

            /*
             * BASIC INFORMATION
             */
            $table->string('name', 150);
            $table->string('legal_name', 150)->nullable();

            $table->string('registration_number', 100)->nullable();
            $table->string('tin', 50)->nullable();

            /*
             * CONTACT INFORMATION
             */
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 150)->nullable();

            /*
             * ADDRESS
             */
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();

            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->string('country', 100)
                ->default('Philippines');

            /*
             * LOCALIZATION
             */
            $table->string('timezone', 100)
                ->default('Asia/Manila');

            $table->string('currency', 10)
                ->default('PHP');

            /*
             * BRANDING
             *
             * File path lang ang ise-save.
             * Hindi image binary.
             */
            $table->string('logo_path', 255)->nullable();

            /*
             * STATUS
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile');
    }
};