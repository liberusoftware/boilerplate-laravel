<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('currency')->nullable();
                $table->string('default_language')->nullable();
                $table->string('address')->nullable();
                $table->string('country')->nullable();
                $table->string('email')->nullable();
                $table->string('phone_01')->nullable();
                $table->string('phone_02')->nullable();
                $table->string('phone_03')->nullable();
                $table->string('facebook')->nullable();
                $table->string('twitter')->nullable();
                $table->string('github')->nullable();
                $table->string('youtube')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
