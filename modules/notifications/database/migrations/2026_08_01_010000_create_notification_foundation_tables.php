<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->morphs('notifiable');
            $table->string('notification');
            $table->json('channels');
            $table->string('quiet_starts', 5)->nullable();
            $table->string('quiet_ends', 5)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
            $table->unique(['notifiable_type', 'notifiable_id', 'notification'], 'notification_preference_unique');
        });
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('notification');
            $table->string('channel');
            $table->string('recipient_ref')->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_preferences');
    }
};
