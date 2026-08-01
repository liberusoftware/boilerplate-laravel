<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('comment_mentions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('comment_id')->constrained('internal_comments')->cascadeOnDelete();
            $t->string('mentioned_ref')->index();
            $t->timestamp('notified_at')->nullable();
            $t->unique(['comment_id', 'mentioned_ref']);
        });
        Schema::create('comment_attachments', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('comment_id')->constrained('internal_comments')->cascadeOnDelete();
            $t->string('media_ref')->index();
            $t->timestamps();
            $t->unique(['comment_id', 'media_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_attachments');
        Schema::dropIfExists('comment_mentions');
    }
};
