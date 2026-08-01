<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_deliveries', function (Blueprint $t): void {
            $t->id();
            $t->uuid('event_id');
            $t->string('event_name');
            $t->string('destination');
            $t->string('status')->index();
            $t->string('suppression_reason')->nullable();
            $t->unsignedInteger('attempts')->default(0);
            $t->json('response')->nullable();
            $t->timestamp('expires_at')->index();
            $t->timestamps();
            $t->unique(['event_id', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_deliveries');
    }
};
