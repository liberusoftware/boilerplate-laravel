<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_activities', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('subject_type');
            $t->string('subject_id');
            $t->string('actor_ref')->nullable();
            $t->string('event');
            $t->string('visibility');
            $t->json('properties');
            $t->timestamps();
            $t->index(['subject_type', 'subject_id', 'created_at']);
        });
        Schema::create('internal_comments', function (Blueprint $t): void {
            $t->id();
            $t->string('subject_type');
            $t->string('subject_id');
            $t->string('author_ref');
            $t->text('body');
            $t->string('visibility');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['subject_type', 'subject_id', 'created_at']);
        });
        Schema::create('activity_subscriptions', function (Blueprint $t): void {
            $t->id();
            $t->string('subject_type');
            $t->string('subject_id');
            $t->string('subscriber_ref');
            $t->timestamps();
            $t->unique(['subject_type', 'subject_id', 'subscriber_ref'], 'activity_subscription_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_subscriptions');
        Schema::dropIfExists('internal_comments');
        Schema::dropIfExists('timeline_activities');
    }
};
