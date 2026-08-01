<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $t): void {
            $t->id();
            $t->string('owner_ref')->index();
            $t->text('url');
            $t->text('signing_secret');
            $t->json('events');
            $t->boolean('active')->default(true);
            $t->timestamp('rotated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('webhook_deliveries', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $t->uuid('event_id')->index();
            $t->string('event');
            $t->json('payload');
            $t->string('status')->default('pending')->index();
            $t->unsignedInteger('attempts')->default(0);
            $t->unsignedSmallInteger('response_status')->nullable();
            $t->text('response_excerpt')->nullable();
            $t->timestamp('next_attempt_at')->nullable()->index();
            $t->timestamps();
            $t->unique(['endpoint_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};
