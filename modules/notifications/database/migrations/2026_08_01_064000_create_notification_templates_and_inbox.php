<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $t): void {
            $t->id();
            $t->string('key');
            $t->string('locale', 12);
            $t->string('channel');
            $t->string('subject')->nullable();
            $t->text('body');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->unique(['key', 'locale', 'channel', 'version'], 'notification_template_unique');
        });
        Schema::create('notification_inbox', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('recipient_ref')->index();
            $t->string('type');
            $t->json('data');
            $t->timestamp('read_at')->nullable()->index();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_inbox');
        Schema::dropIfExists('notification_templates');
    }
};
